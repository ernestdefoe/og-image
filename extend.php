<?php

use Flarum\Extend;
use Ernestdefoe\OgImage\Content\AddOgMetaTags;

return [
    (new Extend\Frontend('forum'))
        ->content(AddOgMetaTags::class),

    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/less/admin.less'),

    (new Extend\Settings())
        ->default('ernestdefoe-og-image.default_image', ''),
];
