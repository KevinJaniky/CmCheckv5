<?php

namespace App\Twig;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction("isVideoOrImage", $this->isVideoOrImage(...)),
            new TwigFunction("orderMedia", $this->orderMedia(...)),
            new TwigFunction("isPaidUser", $this->isPaidUser(...)),
        ];
    }

    private function isVideoOrImage($fileUrl):string
    {
        $ch = curl_init($fileUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);
        if (strpos($contentType, 'image') !== false) {
            return 'image';
        }
        if (strpos($contentType, 'video') !== false) {
            return 'video';
        }
        return 'other';
    }
    private function orderMedia(array $mediasName): array
    {
        sort($mediasName);
        return $mediasName;
    }
    private function isPaidUser(User $user): bool
    {
        $workspace = $user->getWorkspace();
        $subscription = $this->entityManager->getRepository(Subscription::class)->findOneBy(['workspace' => $workspace]);
        if($subscription){
            return $subscription->getType() !== 'free';
        }
        return false;
    }


}