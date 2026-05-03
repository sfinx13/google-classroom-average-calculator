<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\Course;
use App\Dto\CourseWork;
use App\Dto\CourseWorkSubmission;
use App\Dto\Student as StudentDto;
use App\Dto\Topic;
use App\Exception\NoGradesFoundException;
use App\Exception\StudentNotFoundException;
use App\Repository\StudentRepository;
use App\Service\Google\GoogleClassroomService;
use App\Service\Manager\ClassroomResultManager;
use App\Service\StudentAverageCalculator;
use Google\Service\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class ClassroomGradeServiceTest extends TestCase
{
    /**
     * @throws NoGradesFoundException
     * @throws StudentNotFoundException
     * @throws Exception
     * @throws \DateMalformedStringException|ExceptionInterface
     */
    public function testComputeStudentAverage(): void
    {
        $client = $this->createMock(GoogleClassroomService::class);
        $resultManager = $this->createStub(ClassroomResultManager::class);
        $studentRepository = $this->createStub(StudentRepository::class);
        $logger = $this->createStub(LoggerInterface::class);

        $courseId = 'course123';
        $studentId = 'student@example.com';

        $client
            ->expects($this->once())
            ->method('getCourse')
            ->with($courseId)
            ->willReturn(new Course($courseId, 'Math Course'));

        $client
            ->expects($this->once())
            ->method('getStudentIdByName')
            ->with($courseId, $studentId)
            ->willReturn(new StudentDto($studentId, 'John Doe', 'john.doe@example.com'));

        $client
            ->expects($this->once())
            ->method('getTopics')
            ->with($courseId)
            ->willReturn([
                new Topic('topic1', 'Grammaire'),
                new Topic('topic2', 'Lecture'),
            ]);

        $client
            ->expects($this->once())
            ->method('getCourseWorks')
            ->with($courseId)
            ->willReturn([
                new CourseWork('as1', 'Devoir 1', 'topic1', 20.0, new \DateTimeImmutable('2025-01-10')),
                new CourseWork('as2', 'Devoir 2', 'topic1', 20.0, new \DateTimeImmutable('2025-01-15')),
                new CourseWork('as3', 'Lecture 1', 'topic2', 20.0, new \DateTimeImmutable('2025-02-10')),
            ]);

        $client
            ->expects($this->exactly(3))
            ->method('getCourseWorkSubmissions')
            ->willReturnMap([
                [$courseId, 'as1', $studentId, [new CourseWorkSubmission('sub1', 'as1', 14.0)]],
                [$courseId, 'as2', $studentId, [new CourseWorkSubmission('sub2', 'as2', 15.0)]],
                [$courseId, 'as3', $studentId, [new CourseWorkSubmission('sub3', 'as3', 12.0)]],
            ]);

        $service = new StudentAverageCalculator($client, $resultManager, $studentRepository, $logger);
        $result = $service->calculate($studentId, $courseId, new \DateTimeImmutable('2025-01-01'), new \DateTimeImmutable('2025-12-31'));

        $this->assertEquals($studentId, $result->studentName);
        $this->assertCount(2, $result->subjects);

        $subjectAverages = [];
        foreach ($result->subjects as $sa) {
            $subjectAverages[$sa->subjectName] = $sa->average;
        }

        $this->assertEquals(29.0, $subjectAverages['Grammaire']);
        $this->assertEquals(12.0, $subjectAverages['Lecture']);
        $this->assertEquals(13.666666666666668, $result->globalAverage);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     * @throws NoGradesFoundException
     * @throws StudentNotFoundException|ExceptionInterface
     */
    public function testComputeStudentAverageWithDateFilter(): void
    {
        $client = $this->createMock(GoogleClassroomService::class);
        $resultManager = $this->createStub(ClassroomResultManager::class);
        $studentRepository = $this->createStub(StudentRepository::class);
        $logger = $this->createStub(LoggerInterface::class);

        $courseId = 'course123';
        $studentId = 'student@example.com';

        $client
            ->expects($this->once())
            ->method('getCourse')
            ->with($courseId)
            ->willReturn(new Course($courseId, 'Math Course'));

        $client
            ->expects($this->once())
            ->method('getStudentIdByName')
            ->with($courseId, $studentId)
            ->willReturn(new StudentDto($studentId, $studentId, $studentId));

        $client
            ->expects($this->once())
            ->method('getTopics')
            ->with($courseId)
            ->willReturn([
                new Topic('topic1', 'Grammaire'),
            ]);

        $client
            ->expects($this->once())
            ->method('getCourseWorks')
            ->with($courseId)
            ->willReturn([
                new CourseWork('as1', 'T2 - Devoir 1', 'topic1', 20.0, new \DateTimeImmutable('2025-01-10')),
                new CourseWork('as2', 'T2 - Devoir 2', 'topic1', 20.0, new \DateTimeImmutable('2025-02-15')),
            ]);

        $client
            ->expects($this->once())
            ->method('getCourseWorkSubmissions')
            ->willReturnMap([
                [$courseId, 'as1', $studentId, [new CourseWorkSubmission('sub1', 'as1', 14.0)]],
                [$courseId, 'as2', $studentId, [new CourseWorkSubmission('sub2', 'as2', 15.0)]],
            ]);

        $service = new StudentAverageCalculator($client, $resultManager, $studentRepository, $logger);

        $startDate = new \DateTimeImmutable('2025-01-01');
        $endDate = new \DateTimeImmutable('2025-01-31');

        $result = $service->calculate($studentId, $courseId, $startDate, $endDate);

        $this->assertCount(1, $result->subjects);
        $this->assertEquals('Grammaire', $result->subjects[0]->subjectName);
        $this->assertEquals(28.0, $result->subjects[0]->average);
    }
}
