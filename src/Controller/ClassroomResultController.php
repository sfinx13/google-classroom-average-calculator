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
}
