<?php

namespace App\Service;

use App\Entity\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NoteService
{
    private Logger $logger;

    public function __construct(
        private EntityManagerInterface $em,
        private NoteRepository         $noteRep
    )
    {
        $this->logger = new Logger('NoteLogger');
        $this->logger->pushHandler(new RotatingFileHandler(__DIR__ . '/../../var/log/note.log', 30));
    }

    /**
     * @param string $title
     * @param string $text
     * @return Note
     */
    public function createNote(string $title, string $text): Note
    {
        $note = new Note();
        try {
            $note
                ->setText($text)
                ->setTitle($title)
                ->setRegDate(new \DateTime())
                ->setUpdateDate(new \DateTime());

            $this->em->persist($note);
            $this->em->flush();

            $this->logger->info('Succesed created Note');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $note = null;
        }

        return $note;
    }

    public function foundAllNotes(string $searchText, string $order): array
    {
        $allNotes = $this->noteRep->findNotes($searchText, $order);
        $prepareArrayNotes = [];
        foreach ($allNotes as $note) {
            /**@var Note $note */
            $prepareArrayNotes[] = [
                'id' => $note->getId(),
                'title' => $note->getTitle(),
                'text' => $note->getText(),
                'regDate' => $note->getRegDate()->format('Y-m-d H:i:s')
            ];
        }

        return $prepareArrayNotes;
    }

    public function updateNote(string $id, string $title, string $text): Note|null
    {
        $note = $this->noteRep->find($id);
        try {
            $note
                ->setText($text)
                ->setTitle($title)
                ->setUpdateDate(new \DateTime());

            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $note;
    }

    public function deleteNote(string $noteId): string
    {
        $note = $this->noteRep->find($noteId);
        try {
            $this->em->remove($note);
            $this->em->flush();
            return 'Succesed delete';
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return 'Failed delete';
    }

    public function saveUploadsNotes(UploadedFile $file): string
    {
        $prepareNotes = $this->getAllDataNotes($file);
        /**TODO Перед созданием объектов необходимо сделать валидацию*/
        return $this->createsNotes($prepareNotes);
    }

    private function getAllDataNotes(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();

        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $data = [];
        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cell = $worksheet->getCellByColumnAndRow($col, $row);
                $cellValue = $cell->getValue();
                $rowData[] = $cellValue;
            }

            $data[] = $rowData;
        }
        return $data;
    }

    private function createsNotes(array $prepareNotes): string
    {
        $counter = 0 ;
        //Тут надо как то по другому сделать  flush , если ошибка то выйдет из цикла
        try {
            foreach ($prepareNotes as $prepareNote) {
                $note = new Note();

                $note
                    ->setText($prepareNote[0])
                    ->setTitle($prepareNote[1])
                    ->setUpdateDate(new \DateTime())
                    ->setUpdateDate(new \DateTime());

                $this->em->persist($note);
                $counter++;
                if ($counter % 100 === 0){
                    $this->em->flush();
                }
            }
            $this->em->flush();

            return 'Succesed created notes';
        } catch (\Exception $e){
            $this->logger->error($e->getMessage());
        }

        return 'Failed created notes';
    }

}