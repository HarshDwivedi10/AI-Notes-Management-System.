<?php

namespace App\Repositories;

use App\Models\Note;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentNoteRepository implements NoteRepositoryInterface
{
    /**
     * Get paginated notes listing.
     *
     * @return LengthAwarePaginator<Note>
     */
    public function getPaginated(int $perPage = 10, ?string $category = null): LengthAwarePaginator
    {
        $query = Note::query()->latest('id');

        if ($category !== null && trim($category) !== '') {
            $query->where('category', $category);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get single note by ID.
     */
    public function findById(int $id): Note
    {
        return Note::findOrFail($id);
    }

    /**
     * Create a new note.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Note
    {
        return Note::create($data);
    }

    /**
     * Update an existing note.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Note $note, array $data): Note
    {
        $note->update($data);

        return $note->fresh();
    }

    /**
     * Delete a note.
     */
    public function delete(Note $note): bool
    {
        return (bool) $note->delete();
    }

    /**
     * Get all active notes with embeddings for semantic search.
     *
     * @return Collection<int, Note>
     */
    public function getAllForSemanticSearch(): Collection
    {
        return Note::query()
            ->select(['id', 'title', 'content', 'category', 'tags', 'embedding', 'ai_summary', 'created_at', 'updated_at'])
            ->get();
    }
}
