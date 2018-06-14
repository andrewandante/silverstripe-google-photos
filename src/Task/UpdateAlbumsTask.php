<?php

namespace AndrewAndante\GooglePhotos\Task;

use AndrewAndante\GooglePhotos\Model\Account;
use League\OAuth2\Client\Provider\Google;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataList;

class UpdateAlbumsTask extends BuildTask
{
    /**
     * @var DataList
     */
    private $accounts;

    /**
     * @var DataList
     */
    private $albums;
    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner
     *
     * @param HTTPRequest $request
     * @return
     */
    public function run($request)
    {
        $this->accounts = Account::get();
        foreach ($this->accounts as $account) {
            echo "Syncing account " . $account->Title . PHP_EOL;
            $provider = new Google([
                'clientId' => $account->ClientID,
                'clientSecret' => $account->ClientSecret,
                'redirectUri' => 'http://vm.vm/',
            ]);

//            var_dump($provider);
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->param('code')
            ]);
            var_dump($token);

            foreach ($account->Albums() as $album) {
                $this->albums->add($album);
            }

        }

    }
}
