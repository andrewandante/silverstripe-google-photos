<?php

namespace AndrewAndante\GooglePhotos\Admin;

use AndrewAndante\GooglePhotos\Model\Account;
use AndrewAndante\GooglePhotos\Model\Album;
use SilverStripe\Admin\ModelAdmin;

class AlbumAdmin extends ModelAdmin
{
    private static $managed_models = [
        Account::class,
        Album::class,
    ];

    private static $url_segment = 'google-photos';

    private static $menu_title = 'Google Photos';
}
