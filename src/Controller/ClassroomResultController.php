<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ClassroomResultFilterQuery;
use App\Entity\ClassroomResult;
use App\Entity\Enum\Topic;
use App\Repository\ClassroomResultRepository;
use App\Service\Bulletin\BulletinGoogleDocGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class ClassroomResultController extends AbstractController
{
    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/', name: 'app_classroom_results')]
    public function index(
        ClassroomResultRepository $classroomResultRepository,
        #[MapQueryString] ?ClassroomResultFilterQuery $filterQuery,
    ): Response {
        $filterQuery ??= new ClassroomResultFilterQuery();
        $results = $classroomResultRepository->findByFilters(
            $filterQuery->getStartDate(),
            $filterQuery->getEndDate(),
            $filterQuery->getOrderBy()
        );

        return $this->render('classroom_result/index.html.twig', [
            'results' => $results,
            'topic_scales' => Topic::scales(),
            'current_sort' => $filterQuery->sort,
            'current_trimester' => $filterQuery->trimester,
            'start_date' => $filterQuery->startDate,
            'end_date' => $filterQuery->endDate,
        ]);
    }

    #[Route('/bulletin/{id}', name: 'app_classroom_result_bulletin')]
    public function generateBulletin(
        ClassroomResult $classroomResult,
        BulletinGoogleDocGenerator $bulletinGenerator,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        $queryParams = $request->query->all();

        if (null === $classroomResult->getAppreciation() || '' === trim($classroomResult->getAppreciation())) {
            $this->addFlash('error', sprintf('L\'appréciation est obligatoire pour générer le bulletin de %s.', $classroomResult->getStudent()?->getFullname()));

            return $this->redirectToRoute('app_classroom_results', $queryParams);
        }

        if ($classroomResult->getGoogleDocBulletinLink()) {
            $this->addFlash('success', sprintf('Le bulletin de %s est déjà disponible.', $classroomResult->getStudent()?->getFullname()));

            return $this->redirectToRoute('app_classroom_results', $queryParams);
        }

        try {
            $result = $bulletinGenerator->generate($classroomResult);
            $classroomResult->setGoogleDocBulletinLink($result['url']);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le bulletin de %s a bien été généré.', $classroomResult->getStudent()?->getFullname()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la génération du bulletin : '.$e->getMessage());
        }

        return $this->redirectToRoute('app_classroom_results', $queryParams);
    }

    #[Route('/result/{id}/appreciation', name: 'app_classroom_result_appreciation', methods: ['POST'])]
    public function saveAppreciation(
        ClassroomResult $classroomResult,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        $queryParams = $request->query->all();
        unset($queryParams['_token']);

        if (!$this->isCsrfTokenValid('save-appreciation-'.$classroomResult->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_classroom_results', $queryParams);
        }

        $appreciation = $request->request->get('appreciation');
        $classroomResult->setAppreciation($appreciation);
        $entityManager->flush();

        $this->addFlash('success', sprintf('L’appréciation de %s a bien été enregistrée.', $classroomResult->getStudent()?->getFullname()));

        return $this->redirectToRoute('app_classroom_results', $queryParams);
    }

    #[Route('/bulletin/{id}/delete', name: 'app_classroom_result_bulletin_delete', methods: ['POST'])]
    public function deleteBulletin(
        ClassroomResult $classroomResult,
        BulletinGoogleDocGenerator $bulletinGenerator,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        $queryParams = $request->query->all();
        unset($queryParams['_token']);

        if (!$this->isCsrfTokenValid('delete-bulletin-'.$classroomResult->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_classroom_results', $queryParams);
        }

        $link = $classroomResult->getGoogleDocBulletinLink();
        if (!$link) {
            $this->addFlash('error', 'Aucun bulletin à supprimer.');

            return $this->redirectToRoute('app_classroom_results', $queryParams);
        }

        try {
            // Extraire l'ID du document à partir du lien
            // Format: https://docs.google.com/document/d/{ID}/edit
            if (preg_match('/\/d\/(.+)\/edit/', $link, $matches)) {
                $documentId = $matches[1];
                $bulletinGenerator->delete($documentId);
            }

            $classroomResult
                ->setGoogleDocBulletinLink(null)
                ->setAppreciation(null);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Le bulletin de %s a été supprimé.', $classroomResult->getStudent()?->getFullname()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression du bulletin : '.$e->getMessage());
        }

        return $this->redirectToRoute('app_classroom_results', $queryParams);
    }
}
