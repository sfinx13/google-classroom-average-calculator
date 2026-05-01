<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\ClassroomResultFilterQuery;
use App\Entity\Enum\Topic;
use App\Repository\ClassroomResultRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
