<?php

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Log;
use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class HomeController extends AbstractController
{
    #[Route('/admin/home', name: 'app_admin_home')]
    public function index(EntityManagerInterface $entityManager, #[CurrentUser] $user): Response
    {
        $logs = $entityManager->getRepository(Log::class)->findBy(['workspace' => $user->getWorkspace()], ['createdAt' => 'DESC'], 20);

        $nbClient = $entityManager->getRepository(Client::class)->count([
            'workspace' => $user->getWorkspace()
        ]);
        $nbPublication = $entityManager->getRepository(Publication::class)->count([
            'workspace' => $user->getWorkspace()
        ]);
        $nbPublicationToRework = $entityManager->getRepository(Publication::class)->count([
            'workspace' => $user->getWorkspace(),
            'state' => 'rework'
        ]);


        return $this->render('admin/home/index.html.twig', [
            'logs' => $logs,
            'nbClient' => $nbClient,
            'nbPublication' => $nbPublication,
            'nbPublicationToRework' => $nbPublicationToRework
        ]);
    }
}
