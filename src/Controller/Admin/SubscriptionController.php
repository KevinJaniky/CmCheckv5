<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Subscription;
use App\Service\LogSnagService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SubscriptionController extends AbstractController
{
    #[Route('/admin/subscription', name: 'app_admin_subscription')]
    public function index(#[CurrentUser] $user, EntityManagerInterface $entityManager): Response
    {
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy(['user' => $user]);

        return $this->render('admin/subscription/index.html.twig', [
            'subscription' => $subscription,
        ]);
    }

    #[Route('/admin/subscription/upgrade', name: 'app_admin_subscription_upgrade')]
    public function upgrade(#[CurrentUser] $user, EntityManagerInterface $entityManager,StripeService $stripeService): Response
    {
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy(['user' => $user]);
        if($subscription->getType() !== 'free') {
            $this->addFlash('error', 'Vous avez déjà un abonnement premium');
            return $this->redirectToRoute('app_admin_subscription');
        }

        $stripeUrl = $stripeService->createCheckoutSession($user);
        return $this->redirect($stripeUrl);
    }

    #[Route('/admin/subscription/portal', name: 'app_admin_subscription_portal')]
    public function portal(#[CurrentUser] $user, EntityManagerInterface $entityManager,StripeService $stripeService): Response
    {
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy(['user' => $user]);
        if($subscription->getType() === 'free') {
            $this->addFlash('error', 'Vous n\'avez pas d\'abonnement');
            return $this->redirectToRoute('app_admin_subscription');
        }

        $url = $stripeService->openPortal($subscription->getCustomerId());
        return $this->redirect($url);

    }

    #[Route('/admin/subscription/paiement/success', name: 'app_admin_subscription_paiement_success')]
    public function success(#[CurrentUser] $user, EntityManagerInterface $entityManager,Request $request,StripeService $stripeService,LogSnagService $logSnagService): Response
    {
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy(['user' => $user]);

        $session = $stripeService->retrieveCheckoutSession($request->query->get('session_id'));
        if (empty($session)) {
            return $this->redirectToRoute('app_admin_subscription_paiement_cancel');
        }
        if (empty($session->payment_status) || $session->payment_status != 'paid') {
            return $this->redirectToRoute('app_admin_subscription_paiement_cancel');
        }

        $customerId = $session->customer;

        $subscription->setType('avance');
        $subscription->setCustomerId($customerId);
        $subscription->setNbClient(10);
        $subscription->setNbPublication(200);

        $entityManager->persist($subscription);
        $entityManager->flush();

        $logSnagService->addSubscription($user);
        $logSnagService->pushUser('avance', $user);

        $this->addFlash('success','Votre abonnement a été mis à jour');
        return $this->redirectToRoute('app_admin_subscription');

    }

    #[Route('/admin/subscription/paiement/cancel', name: 'app_admin_subscription_paiement_cancel')]
    public function cancel(#[CurrentUser] $user, EntityManagerInterface $entityManager): Response
    {
        $this->addFlash('error','Une erreur est survenue lors du paiement. Contactez le support si le problème persiste');
        return $this->redirectToRoute('app_admin_subscription');
    }
}
