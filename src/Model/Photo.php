<?php

namespace AndrewAndante\GooglePhotos\Model;

use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;

class Photo extends DataObject
{
    private static $table_name = 'GooglePhotos_Photo';

    private static $db = [
        'GoogleID' => 'Varchar',
        'Description' => 'Text',
        'BaseURL' => 'Varchar',
        'ProductURL' => 'Varchar',
        'CreationTime' => 'Datetime',
    ];

    private static $has_one = [
        'LocalFile' => Image::class,
        'Album' => Album::class,
    ];
}
