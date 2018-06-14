<?php

namespace AndrewAndante\GooglePhotos\Model;

use AndrewAndante\GooglePhotos\Admin\AlbumAdmin;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;

class Account extends DataObject
{
    private static $db = [
        'OwnerID' => 'Varchar',
        'OwnerDisplayName' => 'Varchar',
        'OwnerEmail' => 'Varchar',
        'OAuthToken' => 'Varchar',
        'OAuthRefreshToken' => 'Varchar',
    ];

    private static $singular_name = 'Account';

    private static $table_name = 'GooglePhotos_Account';

    private static $summary_fields = [
        'OwnerID' => 'ID',
        'OwnerDisplayName' => 'Name',
        'OwnerEmail' => 'Email',
    ];

    private static $has_many = [
        'Albums' => Album::class,
    ];

    public function getCMSLink()
    {
        return Controller::join_links(
            Director::baseURL(),
            AlbumAdmin::singleton()->Link(),
            str_replace('\\', '-', self::class),
            'EditForm',
            'field',
            str_replace('\\', '-', self::class),
            'item',
            $this->ID,
            'edit'
        );
    }

}
