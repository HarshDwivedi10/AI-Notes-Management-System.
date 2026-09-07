<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Notes Studio | AI Notes Management System</title>
    <meta name="description" content="AI-Powered Executive Notes Management System with RESTful CRUD APIs, Semantic Vector Search, and Automated Note Summarization.">
    <!-- Premium Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Executive Stylesheet -->
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <!-- Subtle Background Overlay -->
    <div class="bg-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <!-- Main Application Container -->
    <div class="app-container">
        <!-- Header / Navigation Bar -->
        <header class="app-header">
            <div class="logo-group">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <div class="logo-text">
                    <h1>Notes Studio</h1>
                    <p>Laravel 11 • Semantic Search • Intelligence System</p>
                </div>
            </div>
            <div class="header-actions">
                <button class="btn btn-ai-search" onclick="openSearchModal()" id="openSearchBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    Semantic Search
                </button>
                <button class="btn btn-primary" onclick="openCreateModal()" id="createNoteBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    New Note
                </button>
                <a href="/docs" class="btn btn-secondary" target="_blank" title="API Documentation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    API Documentation
                </a>
            </div>
        </header>

        <!-- Category Filter & Limit Toolbar -->
        <div class="toolbar">
            <div class="category-pills" id="categoryPills">
                <button class="pill active" data-category="">All Categories</button>
                <button class="pill" data-category="Documentation">Documentation</button>
                <button class="pill" data-category="AI Research">AI Research</button>
                <button class="pill" data-category="Development">Development</button>
                <button class="pill" data-category="Security">Security</button>
                <button class="pill" data-category="Database">Database</button>
                <button class="pill" data-category="Architecture">Architecture</button>
            </div>

            <div class="limit-selector">
                <label for="limitSelect">Display Limit:</label>
                <select id="limitSelect" class="select-input">
                    <option value="10" selected>10 Per Page</option>
                    <option value="25">25 Per Page</option>
                    <option value="50">50 Per Page</option>
                </select>
            </div>
        </div>

        <!-- Notes Grid Container -->
        <main class="notes-grid" id="notesGrid">
            <!-- Dynamic note cards rendered via app.js -->
        </main>

        <!-- Pagination Controls Bar -->
        <footer class="pagination-wrapper" id="paginationWrapper">
            <!-- Dynamic pagination controls -->
        </footer>
    </div>

    <!-- Modal 1: Create / Edit Note -->
    <div class="modal-overlay" id="noteModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title" id="noteModalTitle">Create Note</h2>
                <button class="close-btn" onclick="closeModal('noteModal')">&times;</button>
            </div>
            <form id="noteForm">
                <div class="form-group">
                    <label class="form-label" for="noteTitleInput">Title *</label>
                    <input type="text" id="noteTitleInput" class="form-control" placeholder="Enter note title..." required>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label class="form-label" for="noteCategoryInput">Category</label>
                        <input type="text" id="noteCategoryInput" class="form-control" placeholder="e.g. Executive Strategy">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="noteTagsInput">Tags (Comma Separated)</label>
                        <input type="text" id="noteTagsInput" class="form-control" placeholder="architecture, php, security">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="noteContentInput">Content *</label>
                    <textarea id="noteContentInput" class="form-control" placeholder="Write detailed note content..." required></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:0.8rem; margin-top:1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('noteModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveNoteSubmitBtn">Save Note</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: AI Semantic Search -->
    <div class="modal-overlay" id="searchModal">
        <div class="modal-box" style="max-width: 720px;">
            <div class="modal-header">
                <h2 class="modal-title">Semantic Search Engine</h2>
                <button class="close-btn" onclick="closeModal('searchModal')">&times;</button>
            </div>
            <div class="search-input-wrapper">
                <svg class="search-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Type conceptual queries (e.g. 'Vector search and similarity algorithms')...">
            </div>
            <div id="searchResultsGrid" style="max-height: 50vh; overflow-y: auto; padding-right: 0.5rem;">
                <!-- Search results injected here -->
            </div>
        </div>
    </div>

    <!-- Modal 3: AI Summary -->
    <div class="modal-overlay" id="summaryModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 class="modal-title" id="summaryNoteTitle">Executive Note Summary</h2>
                <button class="close-btn" onclick="closeModal('summaryModal')">&times;</button>
            </div>
            <div class="summary-content-box" id="summaryBody">
                <!-- AI summary text injected here -->
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:0.78rem; color:var(--text-muted);">Cached for 24 hours</span>
                <button class="btn btn-secondary btn-sm" onclick="copySummaryToClipboard()">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                    Copy Summary
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Application Script -->
    <script src="/js/app.js"></script>
</body>
</html>
