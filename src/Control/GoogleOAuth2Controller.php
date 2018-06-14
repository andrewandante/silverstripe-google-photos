<?php

namespace AndrewAndante\GooglePhotos\Control;

use AndrewAndante\GooglePhotos\Model\Account;
use AndrewAndante\GooglePhotos\Model\Album;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\SiteConfig\SiteConfig;

class GoogleOAuth2Controller extends Controller
{
    private static $url_segment = 'googleauth';

    /**
     * @param HTTPRequest $request
     */
    public function index($request)
    {
        $siteconfig = SiteConfig::current_site_config();
        if (!$siteconfig->GoogleOAuth2ClientID || !$siteconfig->GoogleOAuth2ClientSecret) {
            $this->httpError(500, "No GoogleOAuth2 credentials provided");
        }

        $provider = new Google([
            'clientId'     => $siteconfig->GoogleOAuth2ClientID,
            'clientSecret' => $siteconfig->GoogleOAuth2ClientSecret,
            'redirectUri'  => Director::absoluteURL(self::Link()),
        ]);

        if (!empty($request->getVar('error'))) {

            // Got an error, probably user denied access
            return 'Got error: ' . htmlspecialchars($request->getVar('error'), ENT_QUOTES, 'UTF-8');

        } elseif (empty($request->getVar('code'))) {

            // If we don't have an authorization code then get one
            $authUrl = $provider->getAuthorizationUrl([
                'scope' => [
                    'https://www.googleapis.com/auth/photoslibrary.readonly',
                    'email',
                    'openid',
                    'profile',
                ]
            ]);
            $request->getSession()->set('oauth2state', $provider->getState());
            return $this->redirect($authUrl);

        } elseif (empty($request->getVar('state')) || ($request->getVar('state') !== $request->getSession()->get('oauth2state'))) {

            // State is invalid, possible CSRF attack in progress
            $request->getSession()->clear('oauth2state');
            $this->httpError(400, 'Invalid state');

        } else {

            // Try to get an access token (using the authorization code grant)
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->getVar('code')
            ]);

            try {
                // We got an access token, let's now get the owner details
                $owner = $provider->getResourceOwner($token);
                $account = $this->findOrCreateAccount($owner);
                $account->OAuthToken = $token->getToken();
                $account->OAuthRefreshToken = $token->getRefreshToken();
                $account->write();

                $albums = [];
                $response = null;
                $client = new Client();
                do {
                    $uri = 'https://photoslibrary.googleapis.com/v1/albums';
                    if ($response && $response['nextPageToken']) {
                        $uri .= '?' . http_build_query(['pageToken' => $response['nextPageToken']]);
                    }
                    $albumsRequest = new Request(
                        'get',
                        $uri,
                        [
                            'Authorization' => 'Bearer ' . $token->getToken(),
                            'Content-Type' => 'application/json'
                        ]
                    );
                    $guzzly = $client->send($albumsRequest);
                    $response = json_decode($guzzly->getBody(), true);
                    $albums = array_merge($albums, $response['albums']);
                } while (isset($response['nextPageToken']));

                $albumIDs = $account->Albums()->column('GoogleID');
                foreach ($albums as $album) {
                    if (!in_array($album['id'], $albumIDs)) {
                        $this->addAlbumForAccount($album, $account);
                    }
                }
                $account->write();

            } catch (\Exception $e) {

                // Failed to get user details
                $error = 'Something went wrong';
                if (Director::isDev()) {
                    $error .= ': ' . $e->__toString();
                }
                return $error;

            }

        }
        return $this->redirect($account->getCMSLink());
    }

    /**
     * @param GoogleUser $owner
     * @return Account
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function findOrCreateAccount($owner)
    {
        if ($account = Account::get_one(Account::class, ['OwnerID' => $owner->getId()])) {
            return $account;
        }

        $account = Account::create();
        $account->OwnerEmail = $owner->getEmail();
        $account->OwnerID = $owner->getId();
        $account->OwnerDisplayName = $owner->getName();

        return $account;
    }

    public function addAlbumForAccount($album, $account)
    {
            $newAlbum = Album::create();
            $newAlbum->GoogleID = $album['id'];
            $newAlbum->ProductURL = $album['productUrl'];
            $newAlbum->Title = isset($album['title']) ? $album['title'] : 'Untitled';
            $newAlbum->write();

            $account->Albums()->add($newAlbum);
    }
}
