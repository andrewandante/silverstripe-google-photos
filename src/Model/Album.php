<?php

namespace AndrewAndante\GooglePhotos\Model;

use SilverStripe\ORM\DataObject;

class Album extends DataObject
{
    private static $db = [
        'Title' => 'Varchar',
        'GoogleID' => 'Varchar',
        'ProductURL' => 'Varchar',
        'Sync' => 'Boolean(0)'
    ];

    private static $table_name = 'GooglePhotos_Album';

    private static $summary_fields =  [
        'Title',
        'Sync.Nice' => 'Synced?',
        'ProductURL' => 'URL',
    ];

    private static $has_one = [
        'Account' => Account::class,
    ];

    private static $has_many = [
        'Photos' => Photo::class,
    ];
}
