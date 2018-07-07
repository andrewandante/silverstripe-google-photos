<?php

namespace AndrewAndante\GooglePhotos\Model;

use AndrewAndante\GooglePhotos\Admin\AlbumAdmin;
use AndrewAndante\GooglePhotos\Task\UpdateAlbumsTask;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Forms\LiteralField;
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

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $taskLink = '/dev/tasks/AndrewAndante-GooglePhotos-Task-UpdateAlbumsTask';
        $fields->addFieldToTab('Root.Main',
            LiteralField::create('SyncAlbums', sprintf('<a href="%s">Sync albums?</a>', $taskLink))
        );
        return $fields;
    }

    public function getTitle()
    {
        return sprintf("#%s %s %s",
            $this->ID,
            $this->OwnerDisplayName ?: '',
            $this->OwnerEmail ? sprintf("(%s)", $this->OwnerEmail) : ''
        );
    }

}
