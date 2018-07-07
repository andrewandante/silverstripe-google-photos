<?php

namespace AndrewAndante\GooglePhotos\Task;

use AndrewAndante\GooglePhotos\Model\Account;
use AndrewAndante\GooglePhotos\Model\Photo;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use League\OAuth2\Client\Provider\Google;
use SilverStripe\Assets\Image;
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

                    $error = 'Something went wrong';
                    if (Director::isDev()) {
                        $error .= ': ' . $e->__toString();
                    }
                    return $error;

                }

                foreach ($items as $item) {
                    $photo = $this->getPhoto($album, $item);

                    $dirpath = sprintf('assets/%s/%s/', Convert::raw2htmlid($album->Title), $photo->GoogleID);
                    if (!is_dir($dirpath)) {
                        mkdir($dirpath, 0777, true);
                    }
                    try {
                        $client->request('GET', $photo->BaseURL . '=w16383-h16383',
                            ['sink' => $dirpath . substr($item['mediaMetadata']['creationTime'], 0, 10) . '.png']);
                        $file = Image::create();
                        $file->setFromLocalFile($dirpath . 'original.png');
                        $file->write();
                        $photo->LocalFile = $file;
                        $photo->write();
                    } catch (\Exception $e) {
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

    protected function getPhoto($album, $item)
    {
        $photo = Photo::get()->filter(['GoogleID' => $item['id']])->first();
        if (!$photo) {
            $photo = Photo::create();
        }
        $photo->GoogleID = $item['id'];
        $photo->BaseURL = $item['baseUrl'];
        $photo->ProductURL = $item['productUrl'];
        $photo->CreationTime = $item['mediaMetadata']['creationTime'];
        $photo->Description = isset($item['description']) ? $item['description'] : 'No description provided';
        $photo->AlbumID = $album->ID;
        return $photo;
    }
}
