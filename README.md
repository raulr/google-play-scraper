Google Play Scraper
===================

[![Build Status](https://travis-ci.org/raulr/google-play-scraper.svg?branch=master)](https://travis-ci.org/raulr/google-play-scraper)

A PHP scraper to get app data from Google Play.

Installation
------------

Add `raulr/google-play-scraper` as a require dependency in your `composer.json` file:

```sh
$ composer require raulr/google-play-scraper
```

Usage
-----

First create a `Scraper` instance.

```php
use Raulr\GooglePlayScraper\Scraper;

$scraper = new Scraper();
```

There are several methods to configure the default behavior:

* `setDelay($delay)`: Sets the delay in milliseconds between requests to Google Play site.
* `setDefaultLang($lang)`: Sets the default language for all requests. `$lang` must be an [ISO_639-1](https://en.wikipedia.org/wiki/ISO_639-1) two letter language code. If not set, the default language is `en`.
* `setDefaultCountry($country)`: Sets the default country for all requests. `$country` must be an [ISO_3166-1](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) two letter country code. If not set, the default country is `us`.

### getApp

Gets app information given its ID.

#### Parameters

* `$id`: Google Play app identifier.
* `$lang`: (optional, defaults to `null`): Overrides the default language.
* `$country`: (optional, defaults to `null`): Overrides the default country.

#### Example

```php
$app = $scraper->getApp('com.mojang.minecraftpe');
```

Returns:

```php
array (
  'id' => 'com.mojang.minecraftpe',
  'url' => 'https://play.google.com/store/apps/details?id=com.mojang.minecraftpe',
  'image' => 'https://lh3.googleusercontent.com/30koN0eGl-LHqvUZrCj9HT4qVPQdvN508p2wuhaWUnqKeCp6nrs9QW8v6IVGvGNauA=w300',
  'title' => 'Minecraft: Pocket Edition',
  'author' => 'Mojang',
  'author_link' => 'https://play.google.com/store/apps/developer?id=Mojang',
  'categories' => array (
    'Arcade',
    'Creativity',
  ),
  'price' => '$6.99',
  'screenshots' => array (
    'https://lh3.googleusercontent.com/VkLE0e0EDuRID6jdTE97cC8BomcDReJtZOem9Jlb14jw9O7ytAGvE-2pLqvoSJ7w3IdK=h310',
    'https://lh3.googleusercontent.com/28b1vxJQe916wOaSVB4CmcnDujk8M2SNaCwqtQ4cUS0wYKYn9kCYeqxX0uyI2X-nQv0=h310',
    // [...]
  ),
  'description' => 'Our latest free update includes the Nether and all its inhabitants[...]',
  'description_html' => 'Our latest free update includes the Nether and all its inhabitants[...]',
  'rating' => 4.4726405143737793,
  'votes' => 1136962,
  'last_updated' => 'October 22, 2015',
  'size' => 'Varies with device',
  'downloads' => '10,000,000 - 50,000,000',
  'version' => 'Varies with device',
  'supported_os' => 'Varies with device',
  'content_rating' => 'Everyone 10+',
  'whatsnew' => 'Build, explore and survive on the go with Minecraft: Pocket Edition[...]',
  'video_link' => 'https://www.youtube.com/embed/D2Z9oKTzzrM?ps=play&vq=large&rel=0&autohide=1&showinfo=0&autoplay=1',
  'video_image' => 'https://i.ytimg.com/vi/D2Z9oKTzzrM/hqdefault.jpg',
)
```

The following fields may contain a `null` value: `price`, `size`, `downloads`, `version`, `whatsnew`, `video_link` and `video_image`. The `price` being `null` means the app is free.

### getApps

Gets information for multiple apps given their IDs.

#### Parameters

* `$ids`: Array of Google Play app identifiers.
* `$lang`: (optional, defaults to `null`): Overrides the default language.
* `$country`: (optional, defaults to `null`): Overrides the default country.

#### Example

```php
$app = $scraper->getApps(array(
    'com.mojang.minecraftpe',
    'com.google.android.youtube',
));
```

### getCategories

Returns an array with the existing categories in Google Play.

#### Example

```php
use Raulr\GooglePlayScraper\Scraper;

$scraper = new Scraper();
$categories = $scraper->getCategories();
```
Returns:

```php
array (
  'BOOKS_AND_REFERENCE',
  'BUSINESS',
  'COMICS',
  'COMMUNICATION',
  'EDUCATION',
  'ENTERTAINMENT',
  'FINANCE',
  'HEALTH_AND_FITNESS',
  'LIBRARIES_AND_DEMO',
  'LIFESTYLE',
  'APP_WALLPAPER',
  'MEDIA_AND_VIDEO',
  'MEDICAL',
  'MUSIC_AND_AUDIO',
  'NEWS_AND_MAGAZINES',
  'PERSONALIZATION',
  'PHOTOGRAPHY',
  'PRODUCTIVITY',
  'SHOPPING',
  'SOCIAL',
  'SPORTS',
  'TOOLS',
  'TRANSPORTATION',
  'TRAVEL_AND_LOCAL',
  'WEATHER',
  'APP_WIDGETS',
  'GAME_ACTION',
  'GAME_ADVENTURE',
  'GAME_ARCADE',
  'GAME_BOARD',
  'GAME_CARD',
  'GAME_CASINO',
  'GAME_CASUAL',
  'GAME_EDUCATIONAL',
  'GAME_MUSIC',
  'GAME_PUZZLE',
  'GAME_RACING',
  'GAME_ROLE_PLAYING',
  'GAME_SIMULATION',
  'GAME_SPORTS',
  'GAME_STRATEGY',
  'GAME_TRIVIA',
  'GAME_WORD',
  'FAMILY',
  'FAMILY_ACTION',
  'FAMILY_BRAINGAMES',
  'FAMILY_CREATE',
  'FAMILY_EDUCATION',
  'FAMILY_MUSICVIDEO',
  'FAMILY_PRETEND',
)
```

### getCollections

Returns an array with the existing collections in Google Play.

#### Example

```php
$collections = $scraper->getCollections();
```
Returns:

```php
array (
  'topselling_free',
  'topselling_paid',
  'topselling_new_free',
  'topselling_new_paid',
  'topgrossing',
  'movers_shakers',
)
```

### getList

Retrieves a list of Google Play apps given a collection and optionally filtered by category.

#### Parameters

* `$collection`: Google Play collection to retrieve. See [getCollections](#getcollections) for possible values.
* `$category`: (optional, defaults to `null`) Filter request by this category. See [getCategories](#getcategories) for possible values.
* `$lang`: (optional, defaults to `null`): Overrides the default language.
* `$country`: (optional, defaults to `null`): Overrides the default country.

#### Example

```php
$apps = $scraper->getList('topselling_free', 'SOCIAL');
```

Returns:

```php
array (
  array (
    'id' => 'com.facebook.katana',
    'url' => 'https://play.google.com/store/apps/details?id=com.facebook.katana',
    'title' => 'Facebook',
    'image' => 'https://lh3.googleusercontent.com/ZZPdzvlpK9r_Df9C3M7j1rNRi7hhHRvPhlklJ3lfi5jk86Jd1s0Y5wcQ1QgbVaAP5Q=w340',
    'author' => 'Facebook',
    'rating' => 3.9888803958892822,
    'price' => null,
  ),
  array (
    'id' => 'com.snapchat.android',
    'url' => 'https://play.google.com/store/apps/details?id=com.snapchat.android',
    'title' => 'Snapchat',
    'image' => 'https://lh4.ggpht.com/vdK_CsMSsJoYvJpYgaj91fiJ1T8rnSHHbXL0Em378kQaaf_BGyvUek2aU9z2qbxJCAFV=w340',
    'author' => 'Snapchat Inc',
    'rating' => 3.8660063743591309,
    'price' => null,
  ),
  // [...]
)
```

### getDetailList

Same as [getList](#getlist) but returning full detail app data. An additional request is made for every app from the list in order to get its details.

### getListChunk

Retrieves a chunk of a Google Play app list.

#### Parameters

* `$collection`: Google Play collection to retrieve. See [getCollections](#getcollections) for possible values.
* `$category`: (optional, defaults to `null`) Filter request by this category. See [getCategories](#getcategories) for possible values.
* `$start`: (optional, defaults to `0`): Starting index. Must be a value between `0` and `500`.
* `$num`: (optional, defaults to `60`): Amount of apps to retrieve. Must be a value between `0` and `120`.
* `$lang`: (optional, defaults to `null`): Overrides the default language.
* `$country`: (optional, defaults to `null`): Overrides the default country.

#### Example

```php
$apps = $scraper->getListChunk('topselling_free', 'SOCIAL', 20, 80);
```

### getDetailListChunk

Same as [getListChunk](#getlistchunk) but returning full detail app data. An additional request is made for every app from the list in order to get its details.

### getSearch

Retrieves a list of Google Play apps given a search query and optionally filtered by price and rating.

#### Parameters

* `$query`: Search query.
* `$price`: (optional, defaults to `all`) Filter request by price. Possible values: `all`, `free`, `paid`.
* `$rating`: (optional, defaults to `all`) Filter request by rating. Possible values: `all`, `4+`.
* `$lang`: (optional, defaults to `null`): Overrides the default language.
* `$country`: (optional, defaults to `null`): Overrides the default country.

#### Example

```php
$apps = $scraper->getSearch('unicorns', 'free', '4+');
```

### getDetailSearch

Same as [getSearch](#getsearch) but returning full detail app data. An additional request is made for every app from the search result in order to get its details.
