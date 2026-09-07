<?php

namespace App\Repositories;

use App\Models\Note;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface NoteRepositoryInterface
{
    /**
     * Get paginated notes.
     *
     * @return LengthAwarePaginator<Note>
     */
    public function getPaginated(int $perPage = 10, ?string $category = null): LengthAwarePaginator;

    /**
     * Get single note by ID.
     */
    public function findById(int $id): Note;

    /**
     * Create a new note.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Note;

    /**
     * Update an existing note.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Note $note, array $data): Note;

    /**
     * Delete a note (soft delete).
     */
    public function delete(Note $note): bool;

    /**
     * Get all active notes with embeddings for semantic search calculation.
     *
     * @return Collection<int, Note>
     */
    public function getAllForSemanticSearch(): Collection;
}
