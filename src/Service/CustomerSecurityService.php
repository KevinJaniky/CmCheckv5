<?php

namespace App\Service;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;

class CustomerSecurityService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function checkAccess($token): Client|bool
    {
        if(empty($token)) return false;
        $client = $this->entityManager->getRepository(Client::class)->findOneBy([
            'token' => $token
        ]);

        if ($client === null) return false;

        return $client;
    }
}