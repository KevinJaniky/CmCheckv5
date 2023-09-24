<?php

namespace App\Service;

use App\Entity\User;

class LogSnagService
{

    public function __construct(private string $logSnagToken)
    {

    }

    public function addRegister(User $user)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.logsnag.com/v1/log',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"project":"cmcheck","event":"New user was registered","user_id":"' . $user->getId() . '","icon":"🥳","notify":true,"channel":"register"}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer 8c8defc2b2ed0e5ee1b2baee4badb264'
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
    }

    public function addSubscription(User $user)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.logsnag.com/v1/log',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{"project":"cmcheck","event":"User has upgraded to advanced plan","user_id":"'.$user->getId().'","icon":"🤑","notify":true,"channel":"subscription"}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer 8c8defc2b2ed0e5ee1b2baee4badb264'
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
    }

    public function pushUser($plan, User $user)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.logsnag.com/v1/identify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{"project":"cmcheck","user_id":"' . $user->getId() . '","properties":{"email":"' . $user->getEmail() . '","plan":"' . $plan . '"}}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer 8c8defc2b2ed0e5ee1b2baee4badb264'
            ),
        ));
        curl_exec($curl);
        curl_close($curl);
    }
}