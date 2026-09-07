<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Services\NoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NoteController extends Controller
{
    public function __construct(
        protected NoteService $noteService
    ) {}

    /**
     * Get Paginated Notes List.
     * GET /api/notes?page=1&limit=10&category=Work
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->query('limit') ?? $request->query('per_page') ?? 10);
        $category = $request->query('category');

        $paginator = $this->noteService->getPaginatedNotes($perPage, $category);

        return response()->json([
            'status' => 'success',
            'message' => 'Notes retrieved successfully',
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ], 200);
    }

    /**
     * Create a new note.
     * POST /api/notes
     */
    public function store(StoreNoteRequest $request): JsonResponse
    {
        try {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();

            $note = $this->noteService->createNote($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Note created successfully with AI vector embedding indexed',
                'data' => $note,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Error creating note: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create note: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single note details.
     * GET /api/notes/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $note = $this->noteService->getNoteById($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Note retrieved successfully',
                'data' => $note,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Note with ID {$id} not found.",
            ], 404);
        }
    }

    /**
     * Update an existing note.
     * PUT/PATCH /api/notes/{id}
     */
    public function update(UpdateNoteRequest $request, int $id): JsonResponse
    {
        try {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();

            $note = $this->noteService->updateNote($id, $validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Note updated successfully',
                'data' => $note,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Note with ID {$id} not found.",
            ], 404);
        } catch (\Throwable $e) {
            Log::error("Error updating note {$id}: ".$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update note: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a note (Soft Delete).
     * DELETE /api/notes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->noteService->deleteNote($id);

            return response()->json([
                'status' => 'success',
                'message' => "Note with ID {$id} deleted successfully.",
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Note with ID {$id} not found.",
            ], 404);
        }
    }

    /**
     * AI-Powered Semantic Search.
     * GET /api/notes/search?q=query or POST /api/notes/search
     */
    public function search(Request $request): JsonResponse
    {
        $query = (string) ($request->query('q') ?? $request->input('q') ?? $request->input('query') ?? '');
        $limit = (int) ($request->query('limit') ?? $request->input('limit') ?? 10);

        if (trim($query) === '') {
            return response()->json([
                'status' => 'error',
                'message' => 'Search query string parameters "q" or "query" is required.',
            ], 422);
        }

        $results = $this->noteService->searchNotesSemantically($query, $limit);

        return response()->json([
            'status' => 'success',
            'message' => "Found {$results->count()} semantically relevant note(s)",
            'query' => $query,
            'count' => $results->count(),
            'data' => $results->values(),
        ], 200);
    }

    /**
     * Generate AI Summary for Note.
     * POST /api/notes/{id}/summary
     */
    public function summary(int $id): JsonResponse
    {
        try {
            $summaryData = $this->noteService->generateNoteSummary($id);

            return response()->json([
                'status' => 'success',
                'message' => 'AI note summary generated successfully',
                'data' => $summaryData,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => "Note with ID {$id} not found.",
            ], 404);
        } catch (\Throwable $e) {
            Log::error("Error generating summary for note {$id}: ".$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate AI summary: '.$e->getMessage(),
            ], 500);
        }
    }
}
