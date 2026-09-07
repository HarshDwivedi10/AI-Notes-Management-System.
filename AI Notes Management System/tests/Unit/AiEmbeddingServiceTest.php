<?php

namespace Tests\Unit;

use App\Services\AiEmbeddingService;
use Tests\TestCase;

class AiEmbeddingServiceTest extends TestCase
{
    protected AiEmbeddingService $embeddingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->embeddingService = new AiEmbeddingService;
    }

    public function test_it_generates_vector_embeddings(): void
    {
        $text = 'Vector embeddings and semantic search in PHP';
        $vector = $this->embeddingService->generateEmbedding($text);

        $this->assertIsArray($vector);
        $this->assertNotEmpty($vector);
        $this->assertGreaterThanOrEqual(10, count($vector));
    }

    public function test_it_calculates_identical_vectors_cosine_similarity_as_one(): void
    {
        $vec = [0.5, 0.5, 0.5, 0.5];
        $similarity = $this->embeddingService->calculateCosineSimilarity($vec, $vec);

        $this->assertEquals(1.0, $similarity);
    }

    public function test_it_calculates_orthogonal_vectors_cosine_similarity_as_zero(): void
    {
        $vecA = [1.0, 0.0];
        $vecB = [0.0, 1.0];
        $similarity = $this->embeddingService->calculateCosineSimilarity($vecA, $vecB);

        $this->assertEquals(0.0, $similarity);
    }

    public function test_it_handles_empty_vectors_gracefully(): void
    {
        $similarity = $this->embeddingService->calculateCosineSimilarity([], [1.0, 2.0]);
        $this->assertEquals(0.0, $similarity);
    }

    public function test_semantically_similar_texts_produce_positive_cosine_similarity(): void
    {
        $vecA = $this->embeddingService->generateEmbedding('Laravel REST API development');
        $vecB = $this->embeddingService->generateEmbedding('Building RESTful web services in PHP Laravel');

        $similarity = $this->embeddingService->calculateCosineSimilarity($vecA, $vecB);

        $this->assertGreaterThan(0.1, $similarity);

    }
}
