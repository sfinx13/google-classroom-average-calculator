<?php

declare(strict_types=1);

namespace App\Service;

use App\Client\GoogleClassroomClient;
use App\Dto\CourseWork;
use App\Dto\Student;
use App\Dto\StudentAverage;
use App\Dto\SubjectAverage;
use App\Dto\Topic;
use App\Entity\Enum\Topic as TopicEnum;
use App\Exception\NoGradesFoundException;
use App\Exception\StudentNotFoundException;
use App\Repository\StudentRepository;
use Google\Service\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class StudentAverageCalculator
{
    public function __construct(
        private readonly GoogleClassroomClient $client,
        private readonly ClassroomResultManager $classroomResultManager,
        private readonly StudentRepository $studentRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws NoGradesFoundException
     * @throws Exception
     * @throws StudentNotFoundException
     * @throws \DateMalformedStringException
     * @throws ExceptionInterface
     */
    public function calculate(
        string $studentName,
        string $courseId,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
        ?callable $onProgress = null,
    ): StudentAverage {
        $startDate = $startDate ?? new \DateTimeImmutable('1970-01-01');
        $endDate = $endDate ?? new \DateTimeImmutable('2099-12-31');

        $this->logger->info(sprintf(
            'Computing average for student %s in course %s for period: %s - %s',
            $studentName,
            $courseId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        ));

        $studentEntity = $this->studentRepository->findOneBy([
            'fullname' => $studentName,
            'googleClassroomId' => $courseId,
        ]) ?? $this->studentRepository->findOneBy([
            'email' => $studentName,
            'googleClassroomId' => $courseId,
        ]);

        if ($studentEntity) {
            $studentAverageCached = $this->classroomResultManager->retrieveStudentAverageBy($studentEntity, $startDate, $endDate);
            if ($studentAverageCached) {
                $this->logger->info(sprintf('Returning cached result for student %s', $studentName));
                if ($onProgress) {
                    $onProgress(1, 1);
                }

                return $studentAverageCached;
            }
        }

        $course = $this->client->getCourse($courseId);
        if (!$course) {
            throw new StudentNotFoundException(sprintf('Course %s not found', $courseId));
        }

        $student = $this->client->getStudentIdByName($courseId, $studentName);
        if (null === $student) {
            throw new StudentNotFoundException(sprintf('Student %s not found in course %s', $studentName, $courseId));
        }
        $this->logger->info(sprintf('Resolved student %s to ID %s', $student->name, $student->id));

        $topics = $this->client->getTopics($courseId);
        $courseWorks = $this->client->getCourseWorks($courseId);

        $filteredCourseWorks = $this->filterCourseWorks($courseWorks, $startDate, $endDate);

        if (empty($filteredCourseWorks)) {
            throw new NoGradesFoundException(sprintf('No courseWorks found for dates (%s - %s)', $startDate->format('Y-m-d'), $endDate->format('Y-m-d')));
        }

        if ($onProgress) {
            $onProgress(0, count($filteredCourseWorks));
        }

        $gradesByTopic = $this->normalizeGradesByTopic($filteredCourseWorks, $courseId, $student, $topics, $onProgress);
        if (empty($gradesByTopic)) {
            throw new NoGradesFoundException('No grades found for the specified student and period');
        }

        $topicsAverage = $this->computeAverageByTopic($gradesByTopic, $topics);
        $totalAverage = 0.0;
        $totalMaxPoints = 0.0;
        foreach ($topicsAverage as $topicAverage) {
            $totalAverage += $topicAverage->average;
            $totalMaxPoints += $topicAverage->maxPoints;
        }

        $globalAverage = ($totalAverage / $totalMaxPoints) * 20;

        $studentAverage = new StudentAverage(
            $studentName,
            $topicsAverage,
            $globalAverage,
            $totalAverage,
            $totalMaxPoints
        );

        if ($studentEntity) {
            try {
                $this->classroomResultManager->createFrom($studentEntity, $startDate, $endDate, $studentAverage);
                $this->logger->info(sprintf('Persisted result for student %s', $studentName));
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Failed to persist result: %s', $e->getMessage()));
            }
        }

        return $studentAverage;
    }

    /**
     * @param CourseWork[] $courseWorks
     *
     * @return CourseWork[]
     */
    private function filterCourseWorks(
        array $courseWorks,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
    ): array {
        return array_filter($courseWorks, function ($courseWork) use ($startDate, $endDate) {
            if (!$courseWork->creationTime) {
                return false;
            }

            return $courseWork->creationTime >= $startDate && $courseWork->creationTime <= $endDate;
        });
    }

    /**
     * @param CourseWork[] $filteredCourseWorks
     * @param Topic[]      $topics
     *
     * @return array<string, float[]>
     *
     * @throws Exception
     */
    private function normalizeGradesByTopic(
        array $filteredCourseWorks,
        string $courseId,
        Student $student,
        array $topics,
        ?callable $onProgress = null,
    ): array {
        $gradesByTopic = [];
        $topicNames = [];
        foreach ($topics as $topic) {
            $topicNames[$topic->id] = $topic->name;
        }
        $topicNames['unknown'] = 'Autre';

        $current = 0;
        foreach ($filteredCourseWorks as $courseWork) {
            $submissions = $this->client->getCourseWorkSubmissions($courseId, $courseWork->id, $student->id);

            foreach ($submissions as $submission) {
                if (null !== $submission->assignedGrade) {
                    $topicId = $courseWork->topicId ?? 'unknown';

                    $subjectName = $topicNames[$topicId] ?? 'Autre';
                    $targetScale = TopicEnum::scales()[$subjectName] ?? 20;
                    $grade = $submission->assignedGrade;
                    if ($courseWork->maxPoints > 0) {
                        $grade = ($grade / $courseWork->maxPoints) * $targetScale;
                    }

                    $gradesByTopic[$topicId][] = $grade;
                }
            }

            ++$current;
            if ($onProgress) {
                $onProgress($current, count($filteredCourseWorks));
            }
        }

        return $gradesByTopic;
    }

    /**
     * @param array<string, float[]> $gradesByTopic
     * @param Topic[]                $topics
     *
     * @return array<SubjectAverage>
     */
    private function computeAverageByTopic(array $gradesByTopic, array $topics): array
    {
        $subjectAverages = [];
        $topicNames = [];
        foreach ($topics as $topic) {
            $topicNames[$topic->id] = $topic->name;
        }
        $topicNames['unknown'] = 'Autre';
        foreach ($gradesByTopic as $topicId => $grades) {
            $average = array_sum($grades) / count($grades);
            $subjectName = $topicNames[$topicId] ?? 'Inconnu';
            $maxPoints = TopicEnum::scales()[$subjectName] ?? 20;

            $subjectAverages[] = new SubjectAverage(
                $subjectName,
                $average,
                (float) $maxPoints
            );
        }

        return $subjectAverages;
    }
}
