<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\Workspace;
use App\Form\RegistrationFormType;
use App\Security\SecurityAuthenticator;
use App\Service\LogSnagService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request                     $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface  $userAuthenticator,
        SecurityAuthenticator       $authenticator,
        EntityManagerInterface      $entityManager,
        LogSnagService              $logSnagService
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $entityManager->persist($user);

            $workspace = new Workspace();
            $workspace->addUser($user);
            $entityManager->persist($workspace);

            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setType('free');
            $subscription->setWorkspace($workspace);
            $subscription->setNbClient(1);
            $subscription->setNbPublication(10);

            $entityManager->persist($subscription);
            $entityManager->flush();
            // do anything else you need here, like send an email
            $logSnagService->addRegister($user);
            $logSnagService->pushUser('free', $user);

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
