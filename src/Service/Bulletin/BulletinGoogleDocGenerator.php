<?php

declare(strict_types=1);

namespace App\Service\Bulletin;

use App\Client\GoogleClassroomClient;
use App\Dto\ClassroomResultFilterQuery;
use App\Entity\ClassroomResult;
use Google\Service\Docs\BatchUpdateDocumentRequest;
use Google\Service\Docs\Request;
use Google\Service\Drive\DriveFile;
use Google\Service\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BulletinGoogleDocGenerator
{
    public function __construct(
        private readonly GoogleClassroomClient $googleClient,
        private readonly BulletinPlaceholderMapper $placeholderMapper,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(GOOGLE_BULLETIN_TEMPLATE_DOCUMENT_ID)%')]
        private readonly string $templateId,
        #[Autowire('%env(GOOGLE_BULLETIN_OUTPUT_FOLDER_ID)%')]
        private readonly string $outputFolderId,
    ) {
    }

    /**
     * @return array{id: string, url: string}
     *
     * @throws Exception
     */
    public function generate(ClassroomResult $classroomResult): array
    {
        $placeholders = $this->placeholderMapper->map($classroomResult);
        $studentName = $classroomResult->getStudent()?->getFullname();

        $periodLabel = 'Bulletin';
        $startDateStr = $classroomResult->getStartDate()->format('Y-m-d');
        foreach (ClassroomResultFilterQuery::TRIMESTERS as $trimester => $dates) {
            if ($dates['start'] === $startDateStr) {
                $periodLabel = $trimester;
                break;
            }
        }

        $fileName = sprintf('Bulletin - %s - %s', $studentName, $periodLabel);
        $this->logger->info(sprintf('Generating bulletin for %s', $studentName));

        $driveService = $this->googleClient->getDriveService();
        $copy = new DriveFile([
            'name' => $fileName,
            'parents' => [$this->outputFolderId],
        ]);

        $generatedFile = $driveService->files->copy($this->templateId, $copy);
        $documentId = $generatedFile->getId();

        $requests = [];
        foreach ($placeholders as $placeholder => $value) {
            $requests[] = new Request([
                'replaceAllText' => [
                    'containsText' => [
                        'text' => $placeholder,
                        'matchCase' => true,
                    ],
                    'replaceText' => $value,
                ],
            ]);
        }

        if (!empty($requests)) {
            $batchUpdateRequest = new BatchUpdateDocumentRequest([
                'requests' => $requests,
            ]);
            $this->googleClient->getDocsService()->documents->batchUpdate($documentId, $batchUpdateRequest);
        }

        return [
            'id' => $documentId,
            'url' => sprintf('https://docs.google.com/document/d/%s/edit', $documentId),
        ];
    }
}
