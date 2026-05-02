<?php

declare(strict_types=1);

namespace App\Client;

use App\Dto\Course;
use App\Dto\CourseWork;
use App\Dto\CourseWorkSubmission;
use App\Dto\Student;
use App\Dto\Topic;
use Google\Client;
use Google\Service\Classroom;
use Google\Service\Docs;
use Google\Service\Drive;
use Google\Service\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GoogleClassroomClient
{
    private Classroom $service;
    private Drive $driveService;
    private Docs $docsService;

    /**
     * @throws \JsonException
     */
    public function __construct(
        Client $client,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(GOOGLE_CLIENT_ID)%')]
        string $clientId,
        #[Autowire('%env(GOOGLE_CLIENT_SECRET)%')]
        string $clientSecret,
        #[Autowire('%env(GOOGLE_REFRESH_TOKEN)%')]
        string $refreshToken,
    ) {
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');

        $client->addScope(Classroom::CLASSROOM_COURSES_READONLY);
        $client->addScope(Classroom::CLASSROOM_TOPICS_READONLY);
        $client->addScope(Classroom::CLASSROOM_ROSTERS_READONLY);
        $client->addScope(Classroom::CLASSROOM_PROFILE_EMAILS);
        $client->addScope(Docs::DOCUMENTS);
        $client->addScope(Drive::DRIVE);
        $client->addScope('https://www.googleapis.com/auth/classroom.student-submissions.students.readonly');

        $token = $client->fetchAccessTokenWithRefreshToken($refreshToken);

        if (!isset($token['access_token'])) {
            throw new \RuntimeException(sprintf('Unable to fetch Google access token: %s', json_encode($token, JSON_THROW_ON_ERROR)));
        }

        $client->setAccessToken($token);
        $this->service = new Classroom($client);
        $this->driveService = new Drive($client);
        $this->docsService = new Docs($client);
    }

    public function getDriveService(): Drive
    {
        return $this->driveService;
    }

    public function getDocsService(): Docs
    {
        return $this->docsService;
    }

    public function getCourse(string $courseId): ?Course
    {
        $this->logger->info(sprintf('Fetching course %s', $courseId));
        try {
            $course = $this->service->courses->get($courseId);

            return new Course($course->getId(), $course->getName());
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Error fetching course %s: %s', $courseId, $e->getMessage()));

            return null;
        }
    }

    /**
     * @return Course[]
     *
     * @throws Exception
     */
    public function listCourses(): array
    {
        $this->logger->info('Listing courses');
        $response = $this->service->courses->listCourses();
        $courses = [];

        if ($response->getCourses()) {
            foreach ($response->getCourses() as $course) {
                $courses[] = new Course($course->getId(), $course->getName());
            }
        }

        return $courses;
    }

    /**
     * @return Topic[]
     *
     * @throws Exception
     */
    public function getTopics(string $courseId): array
    {
        $this->logger->info(sprintf('Fetching topics for course %s', $courseId));
        $response = $this->service->courses_topics->listCoursesTopics($courseId);
        $topics = [];

        if ($response->getTopic()) {
            foreach ($response->getTopic() as $topic) {
                $topics[] = new Topic($topic->getTopicId(), $topic->getName());
            }
        }

        return $topics;
    }

    /**
     * @return CourseWork[]
     *
     * @throws Exception
     * @throws \DateMalformedStringException
     */
    public function getCourseWorks(string $courseId): array
    {
        $this->logger->info(sprintf('Fetching assignments for course %s', $courseId));
        $response = $this->service->courses_courseWork->listCoursesCourseWork($courseId);
        $assignments = [];

        if ($response->getCourseWork()) {
            foreach ($response->getCourseWork() as $courseWork) {
                $assignments[] = new CourseWork(
                    $courseWork->getId(),
                    $courseWork->getTitle(),
                    $courseWork->getTopicId(),
                    (float) $courseWork->getMaxPoints(),
                    $courseWork->getCreationTime() ? new \DateTimeImmutable($courseWork->getCreationTime()) : null
                );
            }
        }

        return $assignments;
    }

    /**
     * @return Student[]
     *
     * @throws Exception
     */
    public function listStudents(string $courseId): array
    {
        $this->logger->info(sprintf('Listing students for course %s', $courseId));
        $response = $this->service->courses_students->listCoursesStudents($courseId);
        $students = [];

        if ($response->getStudents()) {
            foreach ($response->getStudents() as $student) {
                $profile = $student->getProfile();
                $students[] = new Student(
                    $student->getUserId(),
                    $profile?->getName()?->getFullName() ?? 'Inconnu',
                    $profile?->getEmailAddress() ?? ''
                );
            }
        }

        return $students;
    }

    /**
     * @throws Exception
     */
    public function getStudentIdByName(string $courseId, string $name): ?Student
    {
        $response = $this->service->courses_students->listCoursesStudents($courseId);
        if ($response->getStudents()) {
            foreach ($response->getStudents() as $student) {
                $profile = $student->getProfile();
                if (!$profile) {
                    continue;
                }

                $fullName = $profile->getName()?->getFullName();
                $email = $profile->getEmailAddress();

                if (($fullName && 0 === strcasecmp($fullName, $name)) || ($email && 0 === strcasecmp($email, $name))) {
                    return new Student(
                        $student->getUserId(),
                        $fullName,
                        $email
                    );
                }
            }
        }

        return null;
    }

    /**
     * @return CourseWorkSubmission[]
     *
     * @throws Exception
     */
    public function getCourseWorkSubmissions(string $courseId, string $courseWorkId, string $userId): array
    {
        $this->logger->info(sprintf('Fetching submissions for course %s, assignment %s, user %s', $courseId, $courseWorkId, $userId));
        $response = $this->service->courses_courseWork_studentSubmissions->listCoursesCourseWorkStudentSubmissions(
            $courseId,
            $courseWorkId,
            ['userId' => $userId]
        );
        $submissions = [];

        if ($response->getStudentSubmissions()) {
            foreach ($response->getStudentSubmissions() as $submission) {
                $submissions[] = new CourseWorkSubmission(
                    $submission->getId(),
                    $submission->getCourseWorkId(),
                    null !== $submission->getAssignedGrade() ? (float) $submission->getAssignedGrade() : null
                );
            }
        }

        return $submissions;
    }
}
