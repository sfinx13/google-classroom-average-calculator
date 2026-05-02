<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\NoGradesFoundException;
use App\Exception\StudentNotFoundException;
use App\Service\StudentAverageCalculator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[AsCommand(
    name: 'app:classroom:compute-average',
    description: 'Calcule la moyenne d\'un élève sur Google Classroom pour une période donnée.',
)]
class ComputeAverageCommand extends Command
{
    public function __construct(
        private readonly StudentAverageCalculator $studentAverageCalculator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('student', InputArgument::REQUIRED, 'Nom ou email de l\'élève')
            ->addOption('courseId', null, InputOption::VALUE_REQUIRED, 'ID du cours Google Classroom')
            ->addOption('start-date', null, InputOption::VALUE_OPTIONAL, 'Date de début (Y-m-d)')
            ->addOption('end-date', null, InputOption::VALUE_OPTIONAL, 'Date de fin (Y-m-d)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $student = $input->getArgument('student');
        $courseId = $input->getOption('courseId');
        $startDateOption = $input->getOption('start-date');
        $endDateOption = $input->getOption('end-date');

        if (!$courseId) {
            $io->error('L\'option --courseId est obligatoire.');

            return Command::FAILURE;
        }

        if ($startDateOption && !$endDateOption) {
            $io->error('L\'option --end-date est obligatoire si --start-date est fournie.');

            return Command::FAILURE;
        }

        if (!$startDateOption && $endDateOption) {
            $io->error('L\'option --start-date est obligatoire si --end-date est fournie.');

            return Command::FAILURE;
        }

        $startDate = null;
        $endDate = null;

        if ($startDateOption) {
            try {
                $startDate = new \DateTimeImmutable($startDateOption);
                $endDate = new \DateTimeImmutable($endDateOption);
                $endDate = $endDate->setTime(23, 59, 59);
            } catch (\Exception $e) {
                $io->error('Format de date invalide. Utilisez Y-m-d.');

                return Command::FAILURE;
            }
        }

        try {
            $progressBar = null;
            $onProgress = function (int $current, int $total) use ($io, &$progressBar) {
                if (null === $progressBar) {
                    $progressBar = $io->createProgressBar($total);
                    $progressBar->start();
                }
                $progressBar->setProgress($current);

                if ($current === $total) {
                    $progressBar->finish();
                    $io->newLine(2);
                }
            };

            $studentAverage = $this->studentAverageCalculator->calculate($student, $courseId, $startDate, $endDate, $onProgress);
            $io->title(sprintf('Élève : %s', $studentAverage->studentName));

            $table = new Table($output);
            $table->setHeaders(['Matières', 'Moyenne']);

            foreach ($studentAverage->subjects as $subject) {
                $table->addRow([
                    $subject->subjectName,
                    sprintf('%s/%s', number_format($subject->average, 1), $subject->maxPoints),
                ]);
            }

            $table->render();

            $io->newLine();
            $io->text(sprintf(
                'Total : %s/%s',
                number_format($studentAverage->globalTotalAverage, 2),
                $studentAverage->globalTotalPoints
            ));
            $io->newLine();
            $io->text(sprintf(
                'Moyenne générale : %s/20',
                number_format($studentAverage->globalAverage, 2)
            ));

            return Command::SUCCESS;
        } catch (StudentNotFoundException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        } catch (NoGradesFoundException $e) {
            $io->warning($e->getMessage());

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Une erreur est survenue : %s', $e->getMessage()));

            return Command::FAILURE;
        } catch (ExceptionInterface $e) {
            $io->error(sprintf('Une erreur est survenue : %s', $e->getMessage()));
        }

        return Command::FAILURE;
    }
}
