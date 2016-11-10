<?php

include __DIR__.'/../vendor/autoload.php';

use Raulr\GooglePlayScraper\Scraper;

$scraper = new Scraper();

$query = isset($argv[1]) ? $argv[1] : 'unicorns';
$apps = $scraper->getSearch($query);
var_export($apps);
