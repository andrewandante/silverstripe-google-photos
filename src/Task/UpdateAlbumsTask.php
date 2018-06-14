<?php

namespace AndrewAndante\GooglePhotos\Task;

use AndrewAndante\GooglePhotos\Model\Account;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Provider\Google;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;

class UpdateAlbumsTask extends BuildTask
{
    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param HTTPRequest $request
     * @return
     */
    public function run($request)
    {
        foreach (Account::get() as $account) {
            echo "Syncing account " . $account->Title . PHP_EOL;

            foreach ($account->Albums() as $album) {

                if (!$album->Sync) {
                    continue;
                }

                try {
                    // We got an access token, let's now get the owner details
                    $items = [];
                    $response = null;
                    $client = new Client();
                    do {
                        $bodyArray = [
                            'pageSize' => 100,
                            'albumId' => $album->GoogleID,
                            'filter' => [
                                'mediaTypeFilter' => [
                                    'mediaTypes' => ['PHOTO']
                                ]
                            ]
                        ];
                        if ($response && $response['nextPageToken']) {
                            $bodyArray['pageToken'] = $response['nextPageToken'];
                        }
                        $itemsRequest = new Request(
                            'post',
                            'https://photoslibrary.googleapis.com/v1/mediaItems:search',
                            [
                                'Authorization' => 'Bearer ' . $account->OAuthToken,
                                'Content-Type' => 'application/json'
                            ],
                            Convert::array2json($bodyArray)
                        );
                        $guzzly = $client->send($itemsRequest);
                        $response = json_decode($guzzly->getBody(), true);
                        $items = array_merge($items, $response['mediaItems']);
                    } while (isset($response['nextPageToken']));



                } catch (\Exception $e) {

                    // Failed to get user details
                    $error = 'Something went wrong';
                    if (Director::isDev()) {
                        $error .= ': ' . $e->__toString();
                    }
                    return $error;

                }
            }
        }
    }
}
