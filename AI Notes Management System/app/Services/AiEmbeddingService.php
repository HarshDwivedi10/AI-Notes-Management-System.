<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEmbeddingService
{
    protected const VECTOR_DIMENSION = 128;

    /**
     * Generate vector embedding for text content.
     * Uses OpenAI / Gemini API if configured, otherwise falls back to deterministic TF-IDF term vectorizer.
     *
     * @return array<int, float>
     */
    public function generateEmbedding(string $text): array
    {
        $cleanText = trim(strip_tags($text));
        if ($cleanText === '') {
            return array_fill(0, self::VECTOR_DIMENSION, 0.0);
        }

        $openaiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $geminiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));

        if (! empty($openaiKey)) {
            try {
                $response = Http::withToken($openaiKey)
                    ->timeout(10)
                    ->post('https://api.openai.com/v1/embeddings', [
                        'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
                        'input' => $cleanText,
                    ]);

                if ($response->successful()) {
                    /** @var array<int, float> $embedding */
                    $embedding = $response->json('data.0.embedding', []);
                    if (! empty($embedding)) {
                        return $embedding;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('OpenAI Embedding API error, using fallback vectorizer: '.$e->getMessage());
            }
        }

        if (! empty($geminiKey)) {
            try {
                $response = Http::timeout(10)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key={$geminiKey}", [
                        'content' => [
                            'parts' => [
                                ['text' => $cleanText],
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    /** @var array<int, float> $embedding */
                    $embedding = $response->json('embedding.values', []);
                    if (! empty($embedding)) {
                        return $embedding;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini Embedding API error, using fallback vectorizer: '.$e->getMessage());
            }
        }

        return $this->generateFallbackVector($cleanText);
    }

    /**
     * Compute Cosine Similarity score between two vector float arrays.
     *
     * @param  array<int, float>  $vecA
     * @param  array<int, float>  $vecB
     * @return float Range 0.0 to 1.0
     */
    public function calculateCosineSimilarity(array $vecA, array $vecB): float
    {
        if (empty($vecA) || empty($vecB)) {
            return 0.0;
        }

        // If dimensions differ, truncate or align to min length
        $length = min(count($vecA), count($vecB));
        if ($length === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $valA = (float) $vecA[$i];
            $valB = (float) $vecB[$i];

            $dotProduct += $valA * $valB;
            $magnitudeA += $valA * $valA;
            $magnitudeB += $valB * $valB;
        }

        if ($magnitudeA <= 0.0 || $magnitudeB <= 0.0) {
            return 0.0;
        }

        $similarity = $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));

        // Clamp between 0.0 and 1.0
        return (float) max(0.0, min(1.0, round($similarity, 4)));
    }

    /**
     * Deterministic Hashed TF-IDF Term Vectorizer fallback for offline execution.
     *
     * @return array<int, float>
     */
    protected function generateFallbackVector(string $text): array
    {
        $tokens = $this->tokenize($text);
        $dim = self::VECTOR_DIMENSION;
        $vector = array_fill(0, $dim, 0.0);

        if (empty($tokens)) {
            return $vector;
        }

        $totalTokens = count($tokens);
        $frequencies = array_count_values($tokens);

        foreach ($frequencies as $word => $count) {
            $hash = crc32($word);
            $index = abs($hash) % $dim;
            $tf = $count / $totalTokens;

            // Simple pseudo-IDF multiplier based on word length / complexity
            $idf = 1.0 + (log(strlen($word) + 1));
            $vector[$index] += ($tf * $idf);
        }

        // L2 Normalization
        $sumSquares = 0.0;
        foreach ($vector as $val) {
            $sumSquares += $val * $val;
        }

        $norm = sqrt($sumSquares);
        if ($norm > 0) {
            for ($i = 0; $i < $dim; $i++) {
                $vector[$i] = round($vector[$i] / $norm, 6);
            }
        }

        return $vector;
    }

    /**
     * Tokenize and normalize text into clean words and 2-grams.
     *
     * @return array<int, string>
     */
    protected function tokenize(string $text): array
    {
        $normalized = strtolower($text);
        $normalized = preg_replace('/[^\w\s]/u', ' ', $normalized) ?? '';
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopWords = array_flip([
            'the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'in', 'to', 'for', 'with', 'by',
            'of', 'as', 'it', 'that', 'this', 'from', 'are', 'be', 'has', 'have', 'was', 'were', 'will',
        ]);

        $filteredWords = [];
        foreach ($words as $w) {
            if (strlen($w) >= 2 && ! isset($stopWords[$w])) {
                $filteredWords[] = $w;
            }
        }

        // Include 2-grams for phrase context
        $bigrams = [];
        $count = count($filteredWords);
        for ($i = 0; $i < $count - 1; $i++) {
            $bigrams[] = $filteredWords[$i].'_'.$filteredWords[$i + 1];
        }

        return array_merge($filteredWords, $bigrams);
    }
}
