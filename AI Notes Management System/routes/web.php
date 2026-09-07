<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// AI-Generated Frontend UI Dashboard
Route::get('/', function () {
    return view('index');
})->name('home');

// Interactive Swagger/OpenAPI Documentation UI
Route::get('/docs', function () {
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - AI Notes Management System</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <style>
        body { margin: 0; padding: 0; background: #0f172a; color: #f8fafc; font-family: Inter, sans-serif; }
        .swagger-ui { filter: invert(88%) hue-rotate(180deg) brightness(95%) contrast(90%); }
        .topbar { display: none !important; }
        .custom-header { background: #1e293b; padding: 1.5rem 2rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .custom-header h1 { margin: 0; font-size: 1.4rem; color: #38bdf8; font-weight: 700; }
        .custom-header a { color: #94a3b8; text-decoration: none; font-size: 0.9rem; border: 1px solid #475569; padding: 0.4rem 1rem; border-radius: 6px; transition: all 0.2s; }
        .custom-header a:hover { color: #fff; border-color: #38bdf8; }
    </style>
</head>
<body>
    <div class="custom-header">
        <h1>Notes API Documentation (OpenAPI 3.0)</h1>
        <a href="/">Back to Dashboard App</a>
    </div>

    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: '/swagger.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
            });
        };
    </script>
</body>
</html>
HTML;
})->name('docs');
