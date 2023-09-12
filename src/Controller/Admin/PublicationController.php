<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\Subscription;
use App\Service\FileUploaderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class PublicationController extends AbstractController
{
    #[Route('/admin/publication', name: 'app_admin_publication')]
    public function index(EntityManagerInterface $entityManager, Request $request, #[CurrentUser] $user): Response
    {
        $publications = $entityManager->getRepository(Publication::class)->findBy(['workspace' => $user->getWorkspace()]);
        return $this->render('admin/publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[Route('/admin/publication/create', name: 'app_admin_publication_create')]
    public function create(EntityManagerInterface $entityManager, Request $request, #[CurrentUser] $user, FileUploaderService $fileUploaderService): Response
    {
        $clients = $entityManager->getRepository(Client::class)->findBy(['workspace' => $user->getWorkspace()]);
        $subscription = $entityManager->getRepository(Subscription::class)->findOneBy(['workspace' => $user->getWorkspace()]);
        $nbPublication = $entityManager->getRepository(Publication::class)->count(['workspace' => $user->getWorkspace()]);

        if ($nbPublication >= $subscription->getNbPublication()) {
            $this->addFlash('error', 'Vous avez atteint le nombre maximum de publication pour votre abonnement.');
            return $this->redirectToRoute('app_admin_publication');
        }

        if ($request->isMethod('POST')) {
            $publication = new Publication();
            $publication->setWorkspace($user->getWorkspace());
            $publication->setClient($entityManager->getRepository(Client::class)->find($request->request->get('client')));

            $publishDate = explode('/', $request->request->get('publish_at'));
            $reformatedDate = $publishDate[1] . '/' . $publishDate[0] . '/' . $publishDate[2] . ' ' . $request->request->get('publish_hour') . ':00';
            $dateTime = new \DateTime();
            $dateTime->setTimestamp(strtotime($reformatedDate));
            $publication->setPublishedAt($dateTime);

            $publication->setState($request->request->get('submit_type'));
            $publication->setSocial($request->request->get('social'));

            $entityManager->persist($publication);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_publication_edit', [
                'id' => $publication->getId()
            ]);

        }

        return $this->render('admin/publication/form.html.twig', [
            'clients' => $clients,
            'create' => true
        ]);
    }


    #[Route('/admin/publication/{id}/edit', name: 'app_admin_publication_edit')]
    public function edit(EntityManagerInterface $entityManager, Request $request, #[CurrentUser] $user, FileUploaderService $fileUploaderService, Publication $publication): Response
    {
        if ($publication->getClient()->getWorkspace() != $user->getWorkspace()) {
            throw new AccessDeniedException();
        }

        $clients = $entityManager->getRepository(Client::class)->findBy(['workspace' => $user->getWorkspace()]);
        $commentaires = $entityManager->getRepository(Commentaire::class)->findBy(['publication' => $publication], ['createdAt' => 'DESC']);

        if ($request->isMethod('POST')) {
            $publication->setClient($entityManager->getRepository(Client::class)->find($request->request->get('client')));

            $publishDate = explode('/', $request->request->get('publish_at'));
            $reformatedDate = $publishDate[1] . '/' . $publishDate[0] . '/' . $publishDate[2] . ' ' . $request->request->get('publish_hour') . ':00';
            $dateTime = new \DateTime();
            $dateTime->setTimestamp(strtotime($reformatedDate));
            $publication->setPublishedAt($dateTime);

            $publication->setState($request->request->get('submit_type'));
            $publication->setContent($request->request->get('content'));
            $publication->setSocial($request->request->get('social'));

            $text = strip_tags($request->request->get('content'));
            $content = preg_replace("/&#?[a-z0-9]{2,8}(;)?/i","",html_entity_decode($text) );
            $publication->setSummary($content);

            if(!empty($request->files->get('media'))){
                $medias = $fileUploaderService->uploadMultipleFiles($request->files->get('media'), $publication->getClient());
                $publication->setMedia($medias);
            }

            $entityManager->persist($publication);
            $entityManager->flush();

            $this->addFlash('success', 'La publication a bien été sauvegardée');
            return $this->redirectToRoute('app_admin_publication');
        }

        return $this->render('admin/publication/form.html.twig', [
            'clients' => $clients,
            'publication' => $publication,
            'commentaires' => $commentaires
        ]);
    }


    #[Route('/admin/publication/{id}/delete', name: 'app_admin_publication_delete')]
    public function delete(EntityManagerInterface $entityManager, Request $request, #[CurrentUser] $user, FileUploaderService $fileUploaderService, Publication $publication): Response
    {
        if ($publication->getClient()->getWorkspace() != $user->getWorkspace()) {
            throw new AccessDeniedException();
        }

        $commentaires = $entityManager->getRepository(Commentaire::class)->findBy(['publication' => $publication]);
        foreach ($commentaires as $commentaire) {
            $entityManager->remove($commentaire);
        }
        $entityManager->remove($publication);
        $entityManager->flush();

        $this->addFlash('success', 'La publication a bien été supprimée');
        return $this->redirectToRoute('app_admin_publication');
    }


    #[Route('/admin/publication/{id}/auto_save', name: 'app_admin_publication_auto_save')]
    public function autoSave(EntityManagerInterface $entityManager, Request $request, #[CurrentUser] $user, FileUploaderService $fileUploaderService, Publication $publication): JsonResponse
    {
        if ($publication->getClient()->getWorkspace() != $user->getWorkspace()) {
            throw new AccessDeniedException();
        }

        $publication->setContent($request->request->get('content'));
        $text = strip_tags($request->request->get('content'));
        $content = preg_replace("/&#?[a-z0-9]{2,8}(;)?/i","",html_entity_decode($text) );
        $publication->setSummary($content);

        $entityManager->persist($publication);
        $entityManager->flush();

        return $this->json([
            'success' => true
        ]);
    }

    #[Route('/admin/publication/bulkDelete', name: 'app_admin_publication_bulk_delete')]
    public function bulkDelete(EntityManagerInterface $entityManager, Request $request, #[CurrentUser] $user): JsonResponse
    {

        if(empty($request->get('ids'))){
            return $this->json([
                'success' => false
            ]);
        }

        foreach ($request->get('ids') as $id) {
            $publication = $entityManager->getRepository(Publication::class)->find($id);
            if ($publication->getClient()->getWorkspace() != $user->getWorkspace()) {
                throw new AccessDeniedException();
            }
            $commentaires = $entityManager->getRepository(Commentaire::class)->findBy(['publication' => $publication]);
            foreach ($commentaires as $commentaire) {
                $entityManager->remove($commentaire);
            }
            $entityManager->remove($publication);
        }

        $this->addFlash('success', 'Les publications ont bien été supprimées');
        $entityManager->flush();

        return $this->json([
            'success' => true
        ]);
    }


}
