<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Log;
use App\Entity\Publication;
use App\Service\CustomerSecurityService;
use App\Service\StateService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/customer_portal/public')]
class PublicController extends AbstractController
{
    #[Route('/', name: 'app_public')]
    public function index(Request $request, CustomerSecurityService $customerSecurityService, EntityManagerInterface $entityManager): Response
    {
        $client = $customerSecurityService->checkAccess($request->query->get('token'));
        if (empty($client)) {
            throw $this->createAccessDeniedException();
        }

        $publications = $entityManager->getRepository(Publication::class)->findBy([
            'client' => $client,
            'state' => 'check'
        ], [
            'publishedAt' => 'DESC'
        ]);

        return $this->render('public/index.html.twig', [
            'publications' => $publications,
            'client' => $client,
            'page' => 'check'
        ]);
    }

    #[Route('/all', name: 'app_public_all')]
    public function list(Request $request, CustomerSecurityService $customerSecurityService, EntityManagerInterface $entityManager): Response
    {
        $client = $customerSecurityService->checkAccess($request->query->get('token'));
        if (empty($client)) {
            throw $this->createAccessDeniedException();
        }

        $publications = $entityManager->getRepository(Publication::class)->findBy([
            'client' => $client
        ], [
            'publishedAt' => 'DESC'
        ]);

        $publications = array_filter($publications, function ($publication) {
            return $publication->getState() !== 'draft';
        });

        return $this->render('public/index.html.twig', [
            'publications' => $publications,
            'client' => $client,
            'page' => 'all'
        ]);
    }

    #[Route('/validated', name: 'app_public_validated')]
    public function validated(Request $request, CustomerSecurityService $customerSecurityService, EntityManagerInterface $entityManager): Response
    {
        $client = $customerSecurityService->checkAccess($request->query->get('token'));
        if (empty($client)) {
            throw $this->createAccessDeniedException();
        }

        $publications = $entityManager->getRepository(Publication::class)->findBy([
            'client' => $client,
            'state' => 'validate'
        ], [
            'publishedAt' => 'DESC'
        ]);

        $publications = array_filter($publications, function ($publication) {
            return $publication->getState() !== 'draft';
        });

        return $this->render('public/index.html.twig', [
            'publications' => $publications,
            'client' => $client,
            'page' => 'all'
        ]);
    }

    #[Route('/publication/{id}', name: 'app_public_only_one')]
    public function onlyOne($id, Request $request, CustomerSecurityService $customerSecurityService, EntityManagerInterface $entityManager): Response
    {
        $client = $customerSecurityService->checkAccess($request->query->get('token'));
        if (empty($client)) {
            throw $this->createAccessDeniedException();
        }

        $publications = $entityManager->getRepository(Publication::class)->findBy([
            'client' => $client,
            'id' => $id
        ], [
            'publishedAt' => 'DESC'
        ]);

        return $this->render('public/index.html.twig', [
            'publications' => $publications,
            'client' => $client,
            'page' => 'all'
        ]);
    }

    #[Route('/{token}/action/{id}', name: 'app_public_action')]
    public function action($token, Publication $publication, Request $request, CustomerSecurityService $customerSecurityService, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        $client = $customerSecurityService->checkAccess($token);
        if (empty($client)) {
            throw $this->createAccessDeniedException();
        }

        $comment = $request->request->get('commentaire');
        $action = $request->request->get('action');
        $oldAction = $publication->getState();
        $workspace = $client->getWorkspace();

        if (!empty($comment)) {
            $commentaire = new Commentaire();
            $commentaire->setPublication($publication);
            $commentaire->setComment($comment);
            $entityManager->persist($commentaire);

            $log = new Log();
            $log->setPublication($publication);
            $log->setMessage($publication->getClient()->getSociete().' a ajouté un commentaire');
            $log->setWorkspace($workspace);
            $entityManager->persist($log);
        }


        $publication->setState($action);
        $entityManager->persist($publication);

        $logAction = new Log();
        $logAction->setPublication($publication);
        $logAction->setMessage($publication->getClient()->getSociete().' a changer le status de '.StateService::getState($oldAction).' en '.StateService::getState($action));
        $logAction->setWorkspace($workspace);
        $entityManager->persist($logAction);

        $entityManager->flush();


        $publicationCount = $entityManager->getRepository(Publication::class)->count([
            'client' => $client,
            'state' => 'check'
        ]);

        if($publicationCount == 0){
            $email = (new TemplatedEmail())
                ->from('community@cmcheck.fr')
                ->to($client->getWorkspace()->getUsers()[0]->getEmail())
                ->subject('AtomikCMCheck - ' . $client->getNom() . ' a validé toutes ses publications pour la sociéte ' . $client->getSociete())
                ->htmlTemplate('email/client_had_validate.html.twig')
                ->context([
                    'host' => 'https://' . $_SERVER['HTTP_HOST'] . '/',
                    'clientName' => $client->getSociete()
                ]);
            $mailer->send($email);
        }




        return $this->json([
            'success' => true
        ]);
    }
}
