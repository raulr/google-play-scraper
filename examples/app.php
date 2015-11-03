<?php

include __DIR__.'/../vendor/autoload.php';

use Raulr\GooglePlayScraper\Scraper;

$scraper = new Scraper();

$id = isset($argv[1]) ? $argv[1] : 'com.google.android.youtube';
$app = $scraper->getApp($id);
var_export($app);
