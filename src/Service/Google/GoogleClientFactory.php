<?php

declare(strict_types=1);

namespace App\Service\Google;

use Google\Client;
use Google\Service\Classroom;
use Google\Service\Docs;
use Google\Service\Drive;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GoogleClientFactory
{
    public function __construct(
        #[Autowire('%env(GOOGLE_CLIENT_ID)%')]
        private string $clientId,
        #[Autowire('%env(GOOGLE_CLIENT_SECRET)%')]
        private string $clientSecret,
        #[Autowire('%env(GOOGLE_REFRESH_TOKEN)%')]
        private string $refreshToken,
    ) {
    }

    /**
     * @throws \JsonException
     */
    public function create(bool $fetchAccessTokenWithRefreshToken = true): Client
    {
        $client = new Client();

        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setAccessType('offline');

        $client->addScope(Classroom::CLASSROOM_COURSES_READONLY);
        $client->addScope(Classroom::CLASSROOM_TOPICS_READONLY);
        $client->addScope(Classroom::CLASSROOM_ROSTERS_READONLY);
        $client->addScope(Classroom::CLASSROOM_PROFILE_EMAILS);
        $client->addScope(Docs::DOCUMENTS);
        $client->addScope(Drive::DRIVE);
        $client->addScope('https://www.googleapis.com/auth/classroom.student-submissions.students.readonly');

        if ($fetchAccessTokenWithRefreshToken) {
            $token = $client->fetchAccessTokenWithRefreshToken($this->refreshToken);

            if (!isset($token['access_token'])) {
                throw new \RuntimeException(sprintf('Unable to fetch Google access token: %s', json_encode($token, JSON_THROW_ON_ERROR)));
            }

            $client->setAccessToken($token);
        }

        return $client;
    }
}
