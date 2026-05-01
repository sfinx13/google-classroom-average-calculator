<?php

declare(strict_types=1);

namespace App\Command;

use Google\Client;
use Google\Service\Classroom;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:google:generate-refresh-token',
    description: 'Génère un Refresh Token Google Classroom à partir du Client ID et Secret du .env',
)]
class GenerateRefreshTokenCommand extends Command
{
    public function __construct(
        #[Autowire('%env(GOOGLE_CLIENT_ID)%')]
        private readonly string $clientId,
        #[Autowire('%env(GOOGLE_CLIENT_SECRET)%')]
        private readonly string $clientSecret,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->clientId || !$this->clientSecret) {
            $io->error('GOOGLE_CLIENT_ID ou GOOGLE_CLIENT_SECRET manquant dans le .env');

            return Command::FAILURE;
        }

        $client = new Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $client->addScope(Classroom::CLASSROOM_COURSES_READONLY);
        $client->addScope(Classroom::CLASSROOM_TOPICS_READONLY);
        $client->addScope(Classroom::CLASSROOM_ROSTERS_READONLY);
        $client->addScope(Classroom::CLASSROOM_PROFILE_EMAILS);
        $client->addScope('https://www.googleapis.com/auth/classroom.student-submissions.students.readonly');

        $authUrl = $client->createAuthUrl();

        $io->section('Étape 1 : Autorisation');
        $io->text('Ouvrez l\'URL suivante dans votre navigateur pour autoriser l\'application :');
        $io->note($authUrl);

        $authCode = $io->ask('Étape 2 : Entrez le code d\'autorisation fourni par Google');

        if (!$authCode) {
            $io->error('Le code d\'autorisation est obligatoire.');

            return Command::FAILURE;
        }

        try {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            if (isset($accessToken['error'])) {
                $io->error('Erreur lors de la récupération du token : '.($accessToken['error_description'] ?? $accessToken['error']));

                return Command::FAILURE;
            }

            if (!isset($accessToken['refresh_token'])) {
                $io->warning('Attention : Aucun Refresh Token n\'a été retourné. Si vous avez déjà autorisé cette application, révoquez les accès dans votre compte Google ou essayez avec un autre compte.');
            } else {
                $io->success('Refresh Token généré avec succès !');
                $io->info('Ajoutez la ligne suivante à votre fichier .env :');
                $io->writeln(sprintf('GOOGLE_REFRESH_TOKEN=%s', $accessToken['refresh_token']));
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Une erreur est survenue : '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
