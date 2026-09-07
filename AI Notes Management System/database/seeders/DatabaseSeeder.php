<?php

namespace Database\Seeders;

use App\Models\Note;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed documentation .md files from documentation/ directory into Notes format
        $docDir = base_path('documentation');
        if (file_exists($docDir)) {
            $files = glob($docDir.'/*.md');
            foreach ($files as $filePath) {
                $rawContent = file_get_contents($filePath);
                if ($rawContent === false) {
                    continue;
                }

                $filename = basename($filePath, '.md');
                $lines = explode("\n", $rawContent);
                $title = str_replace('#', '', $lines[0] ?? $filename);
                $title = trim($title) !== '' ? trim($title) : ucwords(str_replace('_', ' ', $filename));

                Note::updateOrCreate(
                    ['title' => $title],
                    [
                        'content' => $rawContent,
                        'category' => 'Documentation',
                        'tags' => ['documentation', 'md', strtolower($filename)],
                        'ai_summary' => "Documentation note for {$title} automatically converted from {$filename}.md",
                    ]
                );
            }
        }

        // 2. Curated Architecture & Domain Notes
        $demoNotes = [
            [
                'title' => 'Vector Embeddings and Semantic Search in PHP',
                'content' => 'Semantic search transforms unstructured text into numerical vector embeddings. By calculating vector cosine similarity between query embeddings and stored document embeddings, applications can find conceptually relevant content even without exact keyword matches.',
                'category' => 'AI Research',
                'tags' => ['ai', 'embeddings', 'vector-search', 'php'],
                'ai_summary' => 'Explains how numerical vector embeddings enable conceptual search matching beyond rigid keyword queries.',
            ],
            [
                'title' => 'Building High Performance RESTful APIs with Laravel 11',
                'content' => 'Laravel 11 simplifies REST API development with clean route declarations, Form Request validation, Eloquent API Resources, and rate limiting middleware. Using Redis caching for hot endpoints significantly reduces database overhead.',
                'category' => 'Development',
                'tags' => ['laravel', 'api', 'performance', 'redis'],
                'ai_summary' => 'Highlights techniques for building fast Laravel 11 APIs using Form Requests, Eloquent resources, and Redis caching.',
            ],
            [
                'title' => 'Modern Security Practices: Preventing SQL Injection and OWASP Top 10',
                'content' => 'Securing backend applications requires parameterized SQL queries via PDO, strict input validation, rate limiting on sensitive endpoints, and proper sanitization of output to protect against XSS and injection vulnerabilities.',
                'category' => 'Security',
                'tags' => ['security', 'owasp', 'sql-injection', 'validation'],
                'ai_summary' => 'Covering defense mechanisms against SQL injection and standard web application security vulnerabilities.',
            ],
            [
                'title' => 'Machine Learning Model Deployment & LLM Summarization',
                'content' => 'Large Language Models (LLMs) excel at zero-shot summarization. By integrating OpenAI GPT-4o or Google Gemini API into backend services, developers can construct key takeaway bullet points automatically from lengthy articles or user notes.',
                'category' => 'AI Research',
                'tags' => ['ai', 'llm', 'summarization', 'openai', 'gemini'],
                'ai_summary' => 'Demonstrates using LLM APIs like OpenAI and Gemini for automatic text summarization and extraction.',
            ],
            [
                'title' => 'Database Indexing Strategies for MySQL and PostgreSQL',
                'content' => 'Proper database indexing on frequently queried columns dramatically improves SQL query execution speed. B-tree indexes, GIN vector indexes, and composite keys ensure quick access times as data scales to millions of rows.',
                'category' => 'Database',
                'tags' => ['database', 'mysql', 'postgresql', 'indexing'],
                'ai_summary' => 'Explores B-tree and vector index design to keep database query latency minimal at scale.',
            ],
            [
                'title' => 'Clean Architecture and Repository Pattern in PHP',
                'content' => 'Decoupling persistence logic from HTTP controllers using the Repository Pattern yields testable, maintainable code. Controllers delegate business domain actions to service classes, which interact with repository interfaces.',
                'category' => 'Architecture',
                'tags' => ['architecture', 'design-patterns', 'repository-pattern', 'clean-code'],
                'ai_summary' => 'Discusses decoupling controllers from database logic using services and repositories.',
            ],
        ];

        foreach ($demoNotes as $noteData) {
            Note::updateOrCreate(
                ['title' => $noteData['title']],
                $noteData
            );
        }
    }
}
