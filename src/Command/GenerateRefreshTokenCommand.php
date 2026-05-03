<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Google\GoogleClientFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:google:generate-refresh-token',
    description: 'Génère un Refresh Token Google Classroom à partir du Client ID et Secret du .env',
)]
class GenerateRefreshTokenCommand extends Command
{
    public function __construct(
        private readonly ?GoogleClientFactory $googleClientFactory = null,
    ) {
        parent::__construct();
    }

    /**
     * @throws \JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $client = $this->googleClientFactory->create();
        $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $client->setPrompt('select_account consent');

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
