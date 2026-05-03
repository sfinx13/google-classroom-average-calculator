<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Google\GoogleClassroomService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:classroom:list-courses',
    description: 'Liste les cours Google Classroom disponibles.',
)]
class ListCoursesCommand extends Command
{
    public function __construct(
        private readonly GoogleClassroomService $client,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $courses = $this->client->listCourses();

            if (empty($courses)) {
                $io->warning('Aucun cours trouvé.');

                return Command::SUCCESS;
            }

            $table = new Table($output);
            $table->setHeaders(['ID', 'Nom du cours']);

            foreach ($courses as $course) {
                $table->addRow([$course->id, $course->name]);
            }

            $table->render();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Une erreur est survenue lors de la récupération des cours : %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
