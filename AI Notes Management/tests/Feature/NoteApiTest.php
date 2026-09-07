<?php

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_paginated_notes_list(): void
    {
        Note::factory()->count(15)->create();

        $response = $this->getJson('/api/notes?page=1&limit=5');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'content', 'category', 'tags', 'created_at', 'updated_at'],
                ],
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'has_more_pages',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 5,
                    'total' => 15,
                ],
            ]);
    }

    public function test_cannot_create_note_without_required_fields(): void
    {
        $response = $this->postJson('/api/notes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_can_create_note_successfully(): void
    {
        $payload = [
            'title' => 'Test AI Integration Note',
            'content' => 'This is a test note to verify API creation and embedding generation.',
            'category' => 'AI Research',
            'tags' => ['ai', 'testing'],
        ];

        $response = $this->postJson('/api/notes', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'title' => 'Test AI Integration Note',
                    'category' => 'AI Research',
                ],
            ]);

        $this->assertDatabaseHas('notes', [
            'title' => 'Test AI Integration Note',
        ]);
    }

    public function test_can_get_single_note(): void
    {
        $note = Note::factory()->create([
            'title' => 'Single Note Test',
        ]);

        $response = $this->getJson("/api/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $note->id,
                    'title' => 'Single Note Test',
                ],
            ]);
    }

    public function test_returns_404_for_non_existent_note(): void
    {
        $response = $this->getJson('/api/notes/99999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_can_update_note(): void
    {
        $note = Note::factory()->create([
            'title' => 'Original Title',
            'content' => 'Original Content',
        ]);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated Content',
        ];

        $response = $this->putJson("/api/notes/{$note->id}", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'title' => 'Updated Title',
                    'content' => 'Updated Content',
                ],
            ]);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_note(): void
    {
        $note = Note::factory()->create();

        $response = $this->deleteJson("/api/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertSoftDeleted('notes', [
            'id' => $note->id,
        ]);
    }

    public function test_can_search_notes_semantically(): void
    {
        Note::factory()->create([
            'title' => 'Vector Embeddings in PHP',
            'content' => 'Explains vector search and cosine similarity algorithms.',
        ]);

        Note::factory()->create([
            'title' => 'Baking Homemade Bread',
            'content' => 'Recipe for making sourdough bread.',
        ]);

        $response = $this->getJson('/api/notes/search?q=vector similarity');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'query' => 'vector similarity',
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('Vector Embeddings in PHP', $data[0]['title']);
    }

    public function test_can_generate_ai_summary_for_note(): void
    {
        $note = Note::factory()->create([
            'title' => 'Architecture Strategy',
            'content' => 'Decoupling persistence logic from HTTP controllers using the Repository Pattern yields testable, maintainable code.',
        ]);

        $response = $this->postJson("/api/notes/{$note->id}/summary");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $note->id,
                    'title' => 'Architecture Strategy',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.summary'));
    }
}
