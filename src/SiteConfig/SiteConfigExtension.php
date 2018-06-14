<?php

namespace AndrewAndante\GooglePhotos\SiteConfig;

use AndrewAndante\GooglePhotos\Control\GoogleOAuth2Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

class SiteConfigExtension extends DataExtension
{
    private static $db = [
        'GoogleOAuth2ClientID' => 'Varchar',
        'GoogleOAuth2ClientSecret' => 'Varchar',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $oauth2Link = GoogleOAuth2Controller::singleton()->Link();
        $fields->addFieldsToTab('Root.GoogleAuth', [
            HeaderField::create('GoogleOAuth2Header', 'Google OAuth2 Credentials'),
            TextField::create('GoogleOAuth2ClientID', 'Client ID'),
            TextField::create('GoogleOAuth2ClientSecret', 'Client Secret'),
            LiteralField::create('AuthenticateGoogle', sprintf('<a href="%s">Authenticate with Google</a>', $oauth2Link))
        ]);
    }
}
