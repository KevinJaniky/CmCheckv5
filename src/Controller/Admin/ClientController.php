<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class ClientController extends AbstractController
{
    #[Route('/admin/client', name: 'app_admin_client')]
    public function index(EntityManagerInterface $entityManager,#[CurrentUser] $user): Response
    {
        $clients = $entityManager->getRepository(Client::class)->findBy(['workspace' => $user->getWorkspace()]);

        return $this->render('admin/client/index.html.twig', [
            'clients' => $clients,
        ]);
    }

    #[Route('/admin/client/create', name: 'app_admin_client_create')]
    public function create(Request $request,#[CurrentUser] $user,EntityManagerInterface $entityManager): Response
    {
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy(['workspace' => $user->getWorkspace()]);
        $nbClient = $entityManager->getRepository(Client::class)->count(['workspace' => $user->getWorkspace()]);

        if($nbClient >= $subscription->getNbClient()){
            $this->addFlash('error', 'Vous avez atteint le nombre maximum de client pour votre abonnement.');
            return $this->redirectToRoute('app_admin_client');
        }


        if($request->isMethod('POST')) {
            $client = new Client();
            $client->setWorkspace($user->getWorkspace());
            $client->setSociete($request->request->get('societe'));
            $client->setNom($request->request->get('nom'));
            $client->setEmail($request->request->get('email'));
            $entityManager->persist($client);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a bien été créé');
            return $this->redirectToRoute('app_admin_client_edit',[
                'id' => $client->getId()
            ]);
        }

        return $this->render('admin/client/form.html.twig', [
        ]);
    }

    #[Route('/admin/client/{id}/edit', name: 'app_admin_client_edit')]
    public function edit(Request $request,#[CurrentUser] $user,EntityManagerInterface $entityManager, Client $client): Response
    {
        if($client->getWorkspace() !== $user->getWorkspace()) {
            throw $this->createAccessDeniedException();
        }

        if($request->isMethod('POST')) {
            $client->setSociete($request->request->get('societe'));
            $client->setNom($request->request->get('nom'));
            $client->setEmail($request->request->get('email'));
            $entityManager->persist($client);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a bien été modifié');
            return $this->redirectToRoute('app_admin_client');
        }

        return $this->render('admin/client/form.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/admin/client/{id}/delete', name: 'app_admin_client_delete')]
    public function delete(Request $request,#[CurrentUser] $user,EntityManagerInterface $entityManager, Client $client): Response
    {
        if($client->getWorkspace() !== $user->getWorkspace()) {
            throw $this->createAccessDeniedException();
        }

        //TODO : Supprimer Toutes les publications

        $entityManager->remove($client);
        $entityManager->flush();

        $this->addFlash('success', 'Le client a bien été supprimé');
        return $this->redirectToRoute('app_admin_client');
    }

    #[Route('/admin/client/{id}/notify', name: 'app_admin_client_notify')]
    public function notify(Request $request,#[CurrentUser] $user,EntityManagerInterface $entityManager, Client $client,MailerInterface $mailer): Response
    {
        if($client->getWorkspace() !== $user->getWorkspace()) {
            throw $this->createAccessDeniedException();
        }

        $email = (new TemplatedEmail())
            ->from('community@cmcheck.fr')
            ->to($client->getEmail())
            ->subject('AtomikCMCheck - ' . $user->getNom() .' '.$user->getPrenom(). ' à besoin de votre validation')
            ->htmlTemplate('email/notify_client.html.twig')
            ->context([
                'host' => 'https://' . $_SERVER['HTTP_HOST'],
                'pathPublic' => $this->generateUrl('app_public', ['token' => $client->getToken()])
            ]);

        $mailer->send($email);
        $this->addFlash('success', 'Le client a bien été notifié');
        return $this->redirectToRoute('app_admin_client');
    }
}

