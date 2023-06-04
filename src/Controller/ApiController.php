<?php

namespace App\Controller;

use App\Entity\Note;
use App\Service\NoteService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Route('/api', name: 'api:')]
class ApiController extends AbstractController
{
    private ?Request $request;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly NoteService  $noteService
    ) {
        $this->request = $this->requestStack->getCurrentRequest();
    }

    #[Route('/create-note', name: 'create_note', methods: "post")]
    public function createNote(): JsonResponse
    {
        $data = json_decode($this->request->getContent(), true);
        $title = $data['title'] ?? null;
        $text = $data['text'] ?? null;
        if (!$title || !$text) {
            return new JsonResponse('error title or text');
        }

        $note = $this->noteService->createNote($title, $text);
        if ($note instanceof Note) {
            return new JsonResponse('Succesed create Note');
        }

        return new JsonResponse('Failed create Note', 500);
    }

    #[Route('/search-notes', name: 'search-notes', methods: "post")]
    public function searchNotes()
    {
        $data = json_decode($this->request->getContent(), true);
        $textSearch = $data['searchText'] ?? null;
        $order = $data['order'] ?? null;
        if (!$textSearch || !$order) {
            return new JsonResponse('text or order empty');
        }

        $allNotes = $this->noteService->foundAllNotes($textSearch, $order);

        return new JsonResponse($allNotes);
    }

    #[Route('/update-notes', name: 'update-notes', methods: "PATCH")]
    public function updateNote(): JsonResponse
    {
        $data = json_decode($this->request->getContent(), true);
        $text = $data['text'] ?? null;
        $title = $data['title'] ?? null;
        $noteId = $data['noteId'] ?? null;
        if (!$text || !$title || !$noteId) {
            return new JsonResponse('empty edit parameters', 500);
        }

        $note = $this->noteService->updateNote($noteId, $title, $text);
        if ($note instanceof Note) {
            return new JsonResponse('Succesed update note');
        }

        return new JsonResponse("Failed update note");
    }

    #[Route('/delete-notes', name: 'delete-notes', methods: "DELETE")]
    public function deleteNote(): JsonResponse
    {
        $data = json_decode($this->request->getContent(), true);
        $noteId = $data['noteId'] ?? null;
        if (!$noteId) {
            return new JsonResponse('empty id note', 500);
        }

        $note = $this->noteService->deleteNote($noteId);

        return new JsonResponse($note);
    }

    #[Route('/upload-notes', name: 'upload-notes', methods: "POST")]
    public function uploadNotes(): Response
    {
        $file = $this->request->files->get('file');
        if ($file instanceof UploadedFile && $file->guessExtension() === 'xlsx') {
            $response = $this->noteService->saveUploadsNotes($file);
            return new JsonResponse($response);

        }
        return new JsonResponse('error file');
    }
}
