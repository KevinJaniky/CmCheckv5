<?php

namespace App\Service;

use App\Entity\User;
use Stripe\Stripe;

class StripeService {
    public function __construct(
        private string $stripeSecretKey,
        private string $stripePublicKey,
        private string $domainUrl
    )
    {
        Stripe::setApiKey($stripeSecretKey);
    }

    public function createCheckoutSession(User $user)
    {
        $checkout_session = \Stripe\Checkout\Session::create([
            'line_items' => [
                [
                    'price' => 'price_1NjKoHHsBneQwXNdUhtxPvs7',
                    'quantity' => 1,
                ]
            ],
            'customer_email' => $user->getEmail(),
            'billing_address_collection' => 'required',
            'mode' => 'subscription',
            'success_url' => $this->domainUrl . '/admin/subscription/paiement/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->domainUrl . '/admin/subscription/paiement/cancel',
            'automatic_tax' => [
                'enabled' => true,
            ],
            'allow_promotion_codes' => true,
            'phone_number_collection' => [
                'enabled' => true,
            ],
            'tax_id_collection' => [
                'enabled' => true,
            ]
        ]);

        return $checkout_session->url;
    }

    public function retrieveCheckoutSession(string $sessionId)
    {
        return \Stripe\Checkout\Session::retrieve($sessionId);
    }
    public function retrieveSubscription(string $subscriptionId)
    {
        return \Stripe\Subscription::retrieve($subscriptionId);
    }

    public function openPortal(string $customerId)
    {
        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $customerId,
            'return_url' => $this->domainUrl . '/admin/subscription',
        ]);

        return $session->url;
    }
}