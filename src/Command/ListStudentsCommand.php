<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Google\GoogleClassroomService;
use App\Service\Manager\StudentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:classroom:list-students',
    description: 'Liste les élèves d\'un cours Google Classroom spécifique.',
)]
class ListStudentsCommand extends Command
{
    public function __construct(
        private readonly GoogleClassroomService $googleClassroomService,
        private readonly StudentManager $studentManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('courseId', InputArgument::REQUIRED, 'L\'identifiant du cours (courseId)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $courseId = $input->getArgument('courseId');

        try {
            $course = $this->googleClassroomService->getCourse($courseId);
            if (!$course) {
                $io->error(sprintf('Le cours avec l\'ID "%s" n\'a pas été trouvé.', $courseId));

                return Command::FAILURE;
            }

            $io->title(sprintf('Élèves du cours : %s (%s)', $course->name, $course->id));

            $students = $this->googleClassroomService->listStudents($courseId);

            if (empty($students)) {
                $io->warning('Aucun élève trouvé dans ce cours.');

                return Command::SUCCESS;
            }

            $table = new Table($output);
            $table->setHeaders(['ID', 'Nom complet', 'Email']);

            foreach ($students as $student) {
                $this->studentManager->createFrom($student, $courseId);
                $table->addRow([$student->id, $student->name, $student->email]);
            }

            $table->render();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Une erreur est survenue lors de la récupération des élèves : %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
