<?php

namespace App\Service;

use App\Entity\Note;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class NoteService
{
    private Logger $logger;
    public function __construct(
        private EntityManagerInterface $em,
        private NoteRepository $noteRep
    ) {
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
        }catch (\Exception $e){
            $this->logger->error($e->getMessage());
            $note = null;
        }

        return $note;
    }

    public function foundAllNotes(string $searchText, string $order): array
    {
        $allNotes = $this->noteRep->findNotes($searchText, $order);
        $prepareArrayNotes = [];
        foreach ($allNotes as $note){
            /**@var Note $note*/
            $prepareArrayNotes[] = [
                'id' => $note->getId(),
                'title' => $note->getTitle(),
                'text' => $note->getText(),
                'regDate' => $note->getRegDate()->format('Y-m-d H:i:s')
            ];
        }

        return $prepareArrayNotes;
    }

    public function updateNote(string $id , string $title, string $text): Note|null
    {
        $note = $this->noteRep->find($id);
        try {
            $note
                ->setText($text)
                ->setTitle($title)
                ->setUpdateDate(new \DateTime());

            $this->em->flush();
        }catch (\Exception $e){
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
        }catch (\Exception $e){
            $this->logger->error($e->getMessage());
        }
        return 'Failed delete';
    }

}