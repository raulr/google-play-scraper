<?php

include __DIR__.'/../vendor/autoload.php';

use Raulr\GooglePlayScraper\Scraper;

$scraper = new Scraper();

$collection = isset($argv[1]) ? $argv[1] : 'topselling_free';
$category = isset($argv[2]) ? $argv[2] : 'SOCIAL';
$apps = $scraper->getList($collection, $category);
var_export($apps);
