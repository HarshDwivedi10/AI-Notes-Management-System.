<?php

namespace App\Services;

use App\Models\Note;
use App\Repositories\NoteRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NoteService
{
    public function __construct(
        protected NoteRepositoryInterface $noteRepository,
        protected AiEmbeddingService $embeddingService,
        protected AiSummaryService $summaryService
    ) {}

    /**
     * Get paginated notes list.
     *
     * @return LengthAwarePaginator<Note>
     */
    public function getPaginatedNotes(int $perPage = 10, ?string $category = null): LengthAwarePaginator
    {
        $limit = max(1, min($perPage, 100));

        return $this->noteRepository->getPaginated($limit, $category);
    }

    /**
     * Find single note by ID.
     */
    public function getNoteById(int $id): Note
    {
        return $this->noteRepository->findById($id);
    }

    /**
     * Create note and auto-generate embedding.
     *
     * @param  array<string, mixed>  $data
     */
    public function createNote(array $data): Note
    {
        $textForEmbedding = ($data['title'] ?? '').' '.($data['content'] ?? '');
        $data['embedding'] = $this->embeddingService->generateEmbedding($textForEmbedding);

        return $this->noteRepository->create($data);
    }

    /**
     * Update note and update embedding if title or content changed.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateNote(int $id, array $data): Note
    {
        $note = $this->noteRepository->findById($id);

        $hasTitleChange = isset($data['title']) && $data['title'] !== $note->title;
        $hasContentChange = isset($data['content']) && $data['content'] !== $note->content;

        if ($hasTitleChange || $hasContentChange) {
            $newTitle = $data['title'] ?? $note->title;
            $newContent = $data['content'] ?? $note->content;
            $data['embedding'] = $this->embeddingService->generateEmbedding("{$newTitle} {$newContent}");
            // Reset cached AI summary when content changes significantly
            $data['ai_summary'] = null;
        }

        return $this->noteRepository->update($note, $data);
    }

    /**
     * Delete note.
     */
    public function deleteNote(int $id): bool
    {
        $note = $this->noteRepository->findById($id);

        return $this->noteRepository->delete($note);
    }

    /**
     * Generate or retrieve AI summary for note.
     *
     * @return array{id: int, title: string, summary: string}
     */
    public function generateNoteSummary(int $id): array
    {
        $note = $this->noteRepository->findById($id);
        $summary = $this->summaryService->generateSummary($note, true);

        return [
            'id' => $note->id,
            'title' => $note->title,
            'summary' => $summary,
        ];
    }

    /**
     * AI-Powered Semantic Search across notes.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function searchNotesSemantically(string $query, int $limit = 10): Collection
    {
        $cleanQuery = trim($query);
        if ($cleanQuery === '') {
            return collect([]);
        }

        $queryEmbedding = $this->embeddingService->generateEmbedding($cleanQuery);
        $allNotes = $this->noteRepository->getAllForSemanticSearch();

        $results = [];

        foreach ($allNotes as $note) {
            $embedding = $note->embedding;
            if (empty($embedding)) {
                $embedding = $this->embeddingService->generateEmbedding("{$note->title} {$note->content}");
                $note->embedding = $embedding;
                $note->save();
            }

            $score = $this->embeddingService->calculateCosineSimilarity($queryEmbedding, $embedding);

            // Also calculate direct keyword match bonus to boost relevance
            $queryWords = preg_split('/\s+/', strtolower($cleanQuery), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $textSample = strtolower($note->title.' '.$note->content);
            $keywordMatches = 0;
            foreach ($queryWords as $qw) {
                if (strlen($qw) >= 2 && str_contains($textSample, $qw)) {
                    $keywordMatches++;
                }
            }

            if (count($queryWords) > 0) {
                $keywordRatio = $keywordMatches / count($queryWords);
                $finalScore = round(($score * 0.7) + ($keywordRatio * 0.3), 4);
            } else {
                $finalScore = $score;
            }

            if ($finalScore > 0.01) {
                $noteArray = $note->toArray();
                $noteArray['similarity_score'] = $finalScore;
                $noteArray['similarity_percentage'] = round($finalScore * 100, 1).'%';
                $results[] = $noteArray;
            }
        }

        // Sort descending by similarity score
        usort($results, fn ($a, $b) => $b['similarity_score'] <=> $a['similarity_score']);

        return collect(array_slice($results, 0, max(1, min($limit, 50))));
    }
}
