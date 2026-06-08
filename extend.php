<?php

use Flarum\Extend;
use Ernestdefoe\OgImage\Content\AddOgMetaTags;

return [
    (new Extend\Frontend('forum'))
        ->content(AddOgMetaTags::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/locale'),

    (new Extend\Settings())
        ->default('ernestdefoe-og-image.default_image', '')
        ->default('ernestdefoe-og-image.fb_app_id', ''),
];
