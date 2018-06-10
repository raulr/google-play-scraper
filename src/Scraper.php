<?php

namespace Raulr\GooglePlayScraper;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Symfony\Component\DomCrawler\Crawler;
use Raulr\GooglePlayScraper\Exception\RequestException;
use Raulr\GooglePlayScraper\Exception\NotFoundException;

/**
 * @author Raul Rodriguez <raul@raulr.net>
 */
class Scraper
{
    const BASE_URL = 'https://play.google.com';

    protected $client;
    protected $delay = 1000;
    protected $lastRequestTime;
    protected $lang = 'en';
    protected $country = 'us';

    public function __construct(GuzzleClientInterface $guzzleClient = null)
    {
        $this->client = new Client();
        if ($guzzleClient) {
            $this->client->setClient($guzzleClient);
        }
    }

    public function setDelay($delay)
    {
        $this->delay = intval($delay);
    }

    public function getDelay()
    {
        return $this->delay;
    }

    public function setDefaultLang($lang)
    {
        $this->lang = $lang;
    }

    public function getDefaultLang()
    {
        return $this->lang;
    }

    public function setDefaultCountry($country)
    {
        $this->country = $country;
    }

    public function getDefaultCountry()
    {
        return $this->country;
    }

    public function getCategories()
    {
        $crawler = $this->request('apps', array(
            'hl' => 'en',
            'gl' => 'us',
        ));

        $collections = $crawler
            ->filter('.child-submenu-link')
            ->reduce(function ($node) {
                return strpos($node->attr('href'), '/store/apps') === 0;
            })
            ->each(function ($node) {
                $href = $node->attr('href');
                $hrefParts = explode('/', $href);
                $collection = end($hrefParts);
                $collection = preg_replace('/\?.*$/', '', $collection);

                return $collection;
            });
        $collections = array_unique($collections);

        return $collections;
    }

    public function getCollections()
    {
        return array(
            'topselling_free',
            'topselling_paid',
            'topselling_new_free',
            'topselling_new_paid',
            'topgrossing',
            'movers_shakers',
        );
    }

    public function getApp($id, $lang = null, $country = null)
    {
        $lang = $lang === null ? $this->lang : $lang;
        $country = $country === null ? $this->country : $country;

        $params = array(
            'id' => $id,
            'hl' => $lang,
            'gl' => $country,
        );
        $crawler = $this->request(array('apps', 'details'), $params);

        $info = array(
            'id' => null,
            'url' => null,
            'image' => null,
            'title' => null,
            'author' => null,
            'author_link' => null,
            'categories' => array(),
            'price' => null,
            'screenshots' => array(),
            'description' => null,
            'description_html' => null,
            'rating' => 0.0,
            'votes' => 0,
            'last_updated' => null,
            'size' => null,
            'downloads' => null,
            'version' => null,
            'supported_os' => null,
            'content_rating' => null,
            'whatsnew' => null,
            'video_link' => null,
            'video_image' => null,
        );

        $info['id'] = $id;
        $info['url'] = $crawler->filter('link[rel="alternate"]')->first()->attr('href');
        $info['image'] = $this->getAbsoluteUrl($crawler->filter('[itemprop="image"]')->attr('src'));
        $info['title'] = $crawler->filter('[itemprop="name"] > span')->text();
        $authorNode = $crawler->filter('a.hrTbp.R8zArc')->first();
        if ($authorNode->count()) {
            $info['author'] = $authorNode->text();
            $info['author_link'] = $this->getAbsoluteUrl($authorNode->attr('href'));
        }
        $info['categories'] = $crawler->filter('[itemprop="genre"]')->each(function ($node) {
            return $node->text();
        });
        $priceNode = $crawler->filter('[itemprop="offers"] > [itemprop="price"]');
        if ($priceNode->count()) {
            $price = $priceNode->attr('content');
            $info['price'] = $price == '0' ? null : $price;
        }
        $info['screenshots'] = $crawler->filter('[data-screenshot-item-index]')->each(function ($node) {
            return $this->getAbsoluteUrl($node->filter('img')->attr('src'));
        });
        $desc = $this->cleanDescription($crawler->filter('[itemprop="description"] > content > div'));
        $info['description'] = $desc['text'];
        $info['description_html'] = $desc['html'];
        $ratingNode = $crawler->filter('.BHMmbe');
        if ($ratingNode->count()) {
            $info['rating'] = floatval(str_replace(',', '.', $ratingNode->text()));
        }
        $votesNode = $crawler->filter('.EymY4b > span[aria-label]');
        if ($votesNode->count()) {
            $info['votes'] = intval(str_replace(array(',', '.', ' '), '', $votesNode->text()));
        }
        $extraInfoNodes = $crawler->filter('.hAyfc > .htlgb');
        if ($extraInfoNodes->count() && $extraInfoNodes->first()->filter('div > img:first-child')->count()) {
            $startingExtraInfoNode = 1; // Skip family library node.
        } else {
            $startingExtraInfoNode = 0;
        }
        $extraInfoNodes->slice($startingExtraInfoNode, $startingExtraInfoNode + 6)->each(function ($node) use (&$info) {
            $nodeText = $node->text();
            $nodeText = str_replace("\xc2\xa0", ' ', $nodeText); // convert non breaking to normal space
            if (is_null($info['last_updated']) && preg_match('/20\d\d/', $nodeText)) {
                $info['last_updated'] = $nodeText;
            } elseif (is_null($info['size']) && preg_match('/^[\d,\. ]+[MG]$/', $nodeText)) {
                $info['size'] = $nodeText;
            } elseif (is_null($info['downloads']) && preg_match('/^[\d,\.  ]+\+$/', $nodeText)) {
                $info['downloads'] = $nodeText;
            } elseif (is_null($info['version']) && preg_match('/^[\d\.]+$/', $nodeText)) {
                $info['version'] = $nodeText;
            } elseif (is_null($info['supported_os']) && preg_match('/^(\d+\.)+\d+.+$/', $nodeText)) {
                $info['supported_os'] = $nodeText;
            } elseif (is_null($info['content_rating']) && $node->filter('div > .htlgb > div')->count()) {
                $info['content_rating'] = $node->filter('div > .htlgb > div')->first()->text();
            }
        });
        $whatsneNode = $crawler->filter('[itemprop="description"] > content')->eq(1);
        if ($whatsneNode->count()) {
            $whatsnew = $this->cleanDescription($whatsneNode);
            $info['whatsnew'] = $whatsnew['text'];
        }
        $videoNode = $crawler->filter('.MSLVtf.NIc6yf');
        if ($videoNode->count()) {
            $info['video_link'] = $this->getAbsoluteUrl($videoNode->filter('[data-trailer-url]')->attr('data-trailer-url'));
            $info['video_image'] = $this->getAbsoluteUrl($videoNode->filter('img')->attr('src'));
        }

        return $info;
    }

    public function getApps($ids, $lang = null, $country = null)
    {
        $ids = (array) $ids;
        $apps = array();

        foreach ($ids as $id) {
            $apps[$id] = $this->getApp($id, $lang, $country);
        }

        return $apps;
    }

    public function getListChunk($collection, $category = null, $start = 0, $num = 60, $lang = null, $country = null)
    {
        $lang = $lang === null ? $this->lang : $lang;
        $country = $country === null ? $this->country : $country;

        if (!is_int($start)) {
            throw new \InvalidArgumentException('"start" must be an integer');
        }
        if ($start < 0 || $start > 500) {
            throw new \RangeException('"start" must be a number between 0 and 500');
        }
        if (!is_int($num)) {
            throw new \InvalidArgumentException('"num" must be an integer');
        }
        if ($num < 0 || $num > 120) {
            throw new \RangeException('"num" must be a number between 0 and 120');
        }

        $path = array('apps');
        if ($category) {
            array_push($path, 'category', $category);
        }
        array_push($path, 'collection', $collection);
        $params = array(
            'hl' => $lang,
            'gl' => $country,
            'start' => $start,
            'num' => $num,
        );
        $crawler = $this->request($path, $params);

        return $this->parseAppList($crawler);
    }

    public function getList($collection, $category = null, $lang = null, $country = null)
    {
        $lang = $lang === null ? $this->lang : $lang;
        $country = $country === null ? $this->country : $country;
        $start = 0;
        $num = 60;
        $apps = array();
        $appsChunk = array();

        do {
            $appsChunk = $this->getListChunk($collection, $category, $start, $num, $lang, $country);
            $apps = array_merge($apps, $appsChunk);
            $start += $num;
        } while (count($appsChunk) == $num && $start <= 500);

        return $apps;
    }

    public function getDetailListChunk($collection, $category = null, $start = 0, $num = 60, $lang = null, $country = null)
    {
        $apps = $this->getListChunk($collection, $category, $start, $num, $lang, $country);
        $ids = array_map(function ($app) {
            return $app['id'];
        }, $apps);

        return $this->getApps($ids);
    }

    public function getDetailList($collection, $category = null, $lang = null, $country = null)
    {
        $apps = $this->getList($collection, $category, $lang, $country);
        $ids = array_map(function ($app) {
            return $app['id'];
        }, $apps);

        return $this->getApps($ids);
    }

    public function getSearch($query, $price = 'all', $rating = 'all', $lang = null, $country = null)
    {
        $lang = $lang === null ? $this->lang : $lang;
        $country = $country === null ? $this->country : $country;
        $priceValues = array(
            'all' => null,
            'free' => 1,
            'paid' => 2,
        );
        $ratingValues = array(
            'all' => null,
            '4+' => 1,
        );

        if (!is_string($query) || empty($query)) {
            throw new \InvalidArgumentException('"query" must be a non empty string');
        }

        if (array_key_exists($price, $priceValues)) {
            $price = $priceValues[$price];
        } else {
            throw new \InvalidArgumentException('"price" must contain one of the following values: '.implode(', ', array_keys($priceValues)));
        }

        if (array_key_exists($rating, $ratingValues)) {
            $rating = $ratingValues[$rating];
        } else {
            throw new \InvalidArgumentException('"rating" must contain one of the following values: '.implode(', ', array_keys($ratingValues)));
        }

        $apps = array();
        $path = array('search');
        $params = array(
            'q' => $query,
            'c' => 'apps',
            'hl' => $lang,
            'gl' => $country,
        );
        if ($price) {
            $params['price'] = $price;
        }
        if ($rating) {
            $params['rating'] = $rating;
        }

        do {
            $crawler = $this->request($path, $params);
            $apps = array_merge($apps, $this->parseAppList($crawler));
            unset($params['pagTok']);
            foreach ($crawler->filter('script') as $scriptNode) {
                if (preg_match('/\\\x22(GAE.+?)\\\x22/', $scriptNode->textContent, $matches)) {
                    $params['pagTok'] = preg_replace('/\\\\\\\u003d/', '=', $matches[1]);
                    break;
                }
            }
        } while (array_key_exists('pagTok', $params));

        return $apps;
    }

    public function getDetailSearch($query, $price = 'all', $rating = 'all', $lang = null, $country = null)
    {
        $apps = $this->getSearch($query, $price, $rating, $lang, $country);
        $ids = array_map(function ($app) {
            return $app['id'];
        }, $apps);

        return $this->getApps($ids);
    }

    protected function request($path, array $params = array())
    {
        // handle delay
        if (!empty($this->delay) && !empty($this->lastRequestTime)) {
            $currentTime = microtime(true);
            $delaySecs = $this->delay / 1000;
            $delay = max(0, $delaySecs - $currentTime + $this->lastRequestTime);
            usleep($delay * 1000000);
        }
        $this->lastRequestTime = microtime(true);

        if (is_array($path)) {
            $path = implode('/', $path);
        }
        $path = ltrim($path, '/');
        $path = rtrim('/store/'.$path, '/');
        $url = self::BASE_URL.$path;
        $query = http_build_query($params);
        if ($query) {
            $url .= '?'.$query;
        }
        $crawler = $this->client->request('GET', $url);
        $status_code = $this->client->getResponse()->getStatus();
        if ($status_code == 404) {
            throw new NotFoundException('Requested resource not found');
        } elseif ($status_code != 200) {
            throw new RequestException(sprintf('Request failed with "%d" status code', $status_code), $status_code);
        }

        return $crawler;
    }

    protected function getAbsoluteUrl($url)
    {
        $urlParts = parse_url($url);
        $baseParts = parse_url(self::BASE_URL);
        $absoluteParts = array_merge($baseParts, $urlParts);

        $absoluteUrl = $absoluteParts['scheme'].'://'.$absoluteParts['host'];
        if (isset($absoluteParts['path'])) {
            $absoluteUrl .= $absoluteParts['path'];
        } else {
            $absoluteUrl .= '/';
        }
        if (isset($absoluteParts['query'])) {
            $absoluteUrl .= '?'.$absoluteParts['query'];
        }
        if (isset($absoluteParts['fragment'])) {
            $absoluteUrl .= '#'.$absoluteParts['fragment'];
        }

        return $absoluteUrl;
    }

    protected function parseAppList(Crawler $crawler)
    {
        return $crawler->filter('.card')->each(function ($node) {
            $app = array();
            $app['id'] = $node->attr('data-docid');
            $app['url'] = self::BASE_URL.$node->filter('a')->attr('href');
            $app['title'] = $node->filter('a.title')->attr('title');
            $app['image'] = $this->getAbsoluteUrl($node->filter('img.cover-image')->attr('data-cover-large'));
            $app['author'] = $node->filter('a.subtitle')->attr('title');
            $ratingNode = $node->filter('.current-rating');
            if (!$ratingNode->count()) {
                $rating = 0.0;
            } elseif (preg_match('/\d+(\.\d+)?/', $node->filter('.current-rating')->attr('style'), $matches)) {
                $rating = floatval($matches[0]) * 0.05;
            } else {
                throw new \RuntimeException('Error parsing rating');
            }
            $app['rating'] = $rating;
            $priceNode = $node->filter('.display-price');
            if (!$priceNode->count()) {
                $price = null;
            } elseif (!preg_match('/\d/', $priceNode->text())) {
                $price = null;
            } else {
                $price = $priceNode->text();
            }
            $app['price'] = $price;

            return $app;
        });
    }

    protected function cleanDescription(Crawler $descriptionNode)
    {
        $descriptionNode->filter('a')->each(function ($node) {
            $domElement = $node->getNode(0);
            $href = $domElement->getAttribute('href');
            while (strpos($href, 'https://www.google.com/url?q=') === 0) {
                $parts = parse_url($href);
                parse_str($parts['query'], $query);
                $href = $query['q'];
            }
            $domElement->setAttribute('href', $href);
        });
        $html = $descriptionNode->html();
        $text = trim($this->convertHtmlToText($descriptionNode->getNode(0)));

        return array(
            'html' => $html,
            'text' => $text,
        );
    }

    protected function convertHtmlToText(\DOMNode $node)
    {
        if ($node instanceof \DOMText) {
            $text = preg_replace('/\s+/', ' ', $node->wholeText);
        } else {
            $text = '';

            foreach ($node->childNodes as $childNode) {
                $text .= $this->convertHtmlToText($childNode);
            }

            switch ($node->nodeName) {
                case 'h1':
                case 'h2':
                case 'h3':
                case 'h4':
                case 'h5':
                case 'h6':
                case 'p':
                case 'ul':
                case 'div':
                    $text = "\n\n".$text."\n\n";
                    break;
                case 'li':
                    $text = '- '.$text."\n";
                    break;
                case 'br':
                    $text = $text."\n";
                    break;
            }

            $text = preg_replace('/\n{3,}/', "\n\n", $text);
        }

        return $text;
    }
}
