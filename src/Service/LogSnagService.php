<?php

namespace App\Service;
use App\Entity\User;

class LogSnagService {

    public function __construct(private string $logSnagToken)
    {

    }

    public function addEvent($eventName,$emoji,$channel,User $user){
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
            CURLOPT_POSTFIELDS => json_encode([
                'project' => 'cmcheck',
                "channel" => $channel,
                'event' => $eventName,
                'user_id' => $user->getId(),
                'notify' => true,
                'icon' => $emoji
            ]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->logSnagToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }
    public function createUser($plan,User $user){
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
            CURLOPT_POSTFIELDS => json_encode([
                'project' => 'cmcheck',
                'user_id' => $user->getId(),
                'properties' => [
                    'email' => $user->getEmail(),
                    'name' => $user->getPrenom().' '.$user->getNom(),
                    'plan' => $plan ?? 'free'
                ],
            ]),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer '.$this->logSnagToken
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }
}