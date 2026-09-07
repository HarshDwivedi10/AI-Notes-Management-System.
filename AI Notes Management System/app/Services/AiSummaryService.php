<?php

namespace App\Services;

use App\Models\Note;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    /**
     * Generate or retrieve cached AI summary for a note.
     */
    public function generateSummary(Note $note, bool $forceRefresh = false): string
    {
        if (! $forceRefresh && ! empty($note->ai_summary)) {
            return $note->ai_summary;
        }

        $cacheKey = "note_summary_{$note->id}";

        if (! $forceRefresh && Cache::has($cacheKey)) {
            /** @var string $cachedSummary */
            $cachedSummary = Cache::get($cacheKey);
            if (! empty($cachedSummary)) {
                return $cachedSummary;
            }
        }

        $summary = $this->fetchAiSummaryFromApi($note->title, $note->content);

        // Save to model
        $note->ai_summary = $summary;
        $note->save();

        // Cache summary for 24 hours
        Cache::put($cacheKey, $summary, now()->addHours(24));

        return $summary;
    }

    /**
     * Fetch summary from OpenAI / Gemini API or fallback Extractive NLP algorithm.
     */
    protected function fetchAiSummaryFromApi(string $title, string $content): string
    {
        $openaiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $geminiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));

        if (! empty($openaiKey)) {
            try {
                $response = Http::withToken($openaiKey)
                    ->timeout(15)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => env('OPENAI_COMPLETION_MODEL', 'gpt-4o-mini'),
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are an AI note summarizer. Provide a clean, executive 2-3 bullet point summary of the note provided.'],
                            ['role' => 'user', 'content' => "Summarize this note:\nTitle: {$title}\nContent:\n{$content}"],
                        ],
                        'max_tokens' => 200,
                        'temperature' => 0.5,
                    ]);

                if ($response->successful()) {
                    /** @var string|null $resContent */
                    $resContent = $response->json('choices.0.message.content');
                    if (! empty($resContent)) {
                        return trim($resContent);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('OpenAI Summary API failed, using NLP fallback: '.$e->getMessage());
            }
        }

        if (! empty($geminiKey)) {
            try {
                $response = Http::timeout(15)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => "Summarize this note in 2-3 concise bullet points:\nTitle: {$title}\nContent: {$content}"],
                                ],
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    /** @var string|null $resContent */
                    $resContent = $response->json('candidates.0.content.parts.0.text');
                    if (! empty($resContent)) {
                        return trim($resContent);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini Summary API failed, using NLP fallback: '.$e->getMessage());
            }
        }

        return $this->generateFallbackSummary($title, $content);
    }

    /**
     * Fallback Extractive NLP Summarizer for offline usage.
     */
    protected function generateFallbackSummary(string $title, string $content): string
    {
        $cleanContent = strip_tags($content);
        $sentences = preg_split('/(?<=[.?!])\s+/', $cleanContent, -1, PREG_SPLIT_NO_EMPTY) ?: [$cleanContent];

        if (count($sentences) <= 2) {
            return '• '.implode("\n• ", array_map('trim', $sentences));
        }

        // Frequency matrix scoring
        $words = preg_split('/\s+/', strtolower($cleanContent), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCounts = array_count_values($words);

        $scoredSentences = [];
        foreach ($sentences as $index => $sentence) {
            $sWords = preg_split('/\s+/', strtolower($sentence), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $score = 0;
            foreach ($sWords as $sw) {
                if (strlen($sw) > 3) {
                    $score += ($wordCounts[$sw] ?? 1);
                }
            }
            // Boost first sentence and sentences containing title words
            if ($index === 0) {
                $score *= 1.5;
            }
            $scoredSentences[] = ['text' => trim($sentence), 'score' => $score, 'index' => $index];
        }

        // Sort by score descending
        usort($scoredSentences, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Select top 2 or 3 sentences
        $top = array_slice($scoredSentences, 0, min(3, count($scoredSentences)));

        // Re-sort selected sentences by original document order
        usort($top, fn ($a, $b) => $a['index'] <=> $b['index']);

        $bulletList = array_map(fn ($item) => '• '.rtrim($item['text'], '.'), $top);

        return implode("\n", $bulletList);
    }
}
