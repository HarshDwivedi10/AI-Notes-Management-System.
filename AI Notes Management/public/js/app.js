document.addEventListener('DOMContentLoaded', () => {
    let currentPage = 1;
    let currentLimit = 10;
    let currentCategory = '';
    let editingNoteId = null;
    let searchDebounceTimer = null;

    // Element Selectors
    const notesGrid = document.getElementById('notesGrid');
    const paginationWrapper = document.getElementById('paginationWrapper');
    const categoryPills = document.querySelectorAll('.pill');
    const limitSelect = document.getElementById('limitSelect');

    // Modals
    const noteModal = document.getElementById('noteModal');
    const searchModal = document.getElementById('searchModal');
    const summaryModal = document.getElementById('summaryModal');

    // Form Inputs
    const noteForm = document.getElementById('noteForm');
    const noteModalTitle = document.getElementById('noteModalTitle');
    const noteTitleInput = document.getElementById('noteTitleInput');
    const noteCategoryInput = document.getElementById('noteCategoryInput');
    const noteTagsInput = document.getElementById('noteTagsInput');
    const noteContentInput = document.getElementById('noteContentInput');

    // Search Inputs
    const searchInput = document.getElementById('searchInput');
    const searchResultsGrid = document.getElementById('searchResultsGrid');

    // Summary Elements
    const summaryNoteTitle = document.getElementById('summaryNoteTitle');
    const summaryBody = document.getElementById('summaryBody');

    // --- Initial Load ---
    fetchNotes(currentPage, currentCategory, currentLimit);

    // --- Category Filter Pills ---
    categoryPills.forEach(pill => {
        pill.addEventListener('click', () => {
            categoryPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentCategory = pill.dataset.category || '';
            currentPage = 1;
            fetchNotes(currentPage, currentCategory, currentLimit);
        });
    });

    // --- Limit Selector ---
    if (limitSelect) {
        limitSelect.addEventListener('change', (e) => {
            currentLimit = parseInt(e.target.value, 10);
            currentPage = 1;
            fetchNotes(currentPage, currentCategory, currentLimit);
        });
    }

    // --- API Functions ---
    async function fetchNotes(page = 1, category = '', limit = 10) {
        notesGrid.innerHTML = `
            <div class="empty-state">
                <p>Loading entries...</p>
            </div>
        `;

        try {
            let url = `/api/notes?page=${page}&limit=${limit}`;
            if (category) {
                url += `&category=${encodeURIComponent(category)}`;
            }

            const response = await fetch(url);
            const result = await response.json();

            if (result.status === 'success') {
                renderNotes(result.data);
                renderPagination(result.pagination);
            } else {
                showToast(result.message || 'Failed to fetch notes', 'error');
            }
        } catch (error) {
            console.error('Fetch Notes Error:', error);
            showToast('Network error loading notes', 'error');
        }
    }

    function renderNotes(notes) {
        if (!notes || notes.length === 0) {
            notesGrid.innerHTML = `
                <div class="empty-state">
                    <h3>No Entries Found</h3>
                    <p style="margin-top:0.5rem; font-size:0.85rem;">Click "New Note" above to record your first entry.</p>
                </div>
            `;
            return;
        }

        notesGrid.innerHTML = notes.map(note => {
            const tags = Array.isArray(note.tags) ? note.tags : [];
            const formattedDate = new Date(note.created_at).toLocaleDateString(undefined, {
                month: 'short', day: 'numeric', year: 'numeric'
            });

            return `
                <div class="note-card" data-id="${note.id}">
                    <div>
                        <div class="note-header">
                            <h3 class="note-title">${escapeHtml(note.title)}</h3>
                            ${note.category ? `<span class="category-tag">${escapeHtml(note.category)}</span>` : ''}
                        </div>
                        <p class="note-body">${escapeHtml(note.content)}</p>
                        ${tags.length > 0 ? `
                            <div class="tags-row">
                                ${tags.map(t => `<span class="tag-badge">#${escapeHtml(t)}</span>`).join('')}
                            </div>
                        ` : ''}
                    </div>
                    <div class="note-footer">
                        <span>${formattedDate}</span>
                        <div class="card-actions">
                            <button class="action-btn ai-summary-btn" onclick="triggerSummary(${note.id})" title="Generate Summary">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                </svg>
                                Summary
                            </button>
                            <button class="action-btn" onclick="triggerEditNote(${note.id})" title="Edit Note">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="action-btn delete-btn" onclick="triggerDeleteNote(${note.id})" title="Delete Note">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.total === 0) {
            paginationWrapper.style.display = 'none';
            return;
        }

        paginationWrapper.style.display = 'flex';
        const startItem = ((pagination.current_page - 1) * pagination.per_page) + 1;
        const endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total);

        paginationWrapper.innerHTML = `
            <div class="page-info">
                Showing <strong>${startItem} - ${endItem}</strong> of <strong>${pagination.total}</strong> entries
            </div>
            <div class="page-controls">
                <button class="btn btn-secondary btn-sm" ${pagination.current_page === 1 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''} id="prevPageBtn">
                    Previous
                </button>
                <span style="align-self:center; font-size:0.82rem; color:var(--text-muted); font-weight:600;">
                    Page ${pagination.current_page} of ${pagination.last_page}
                </span>
                <button class="btn btn-secondary btn-sm" ${!pagination.has_more_pages ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : ''} id="nextPageBtn">
                    Next
                </button>
            </div>
        `;

        document.getElementById('prevPageBtn')?.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchNotes(currentPage, currentCategory, currentLimit);
            }
        });

        document.getElementById('nextPageBtn')?.addEventListener('click', () => {
            if (pagination.has_more_pages) {
                currentPage++;
                fetchNotes(currentPage, currentCategory, currentLimit);
            }
        });
    }

    // --- Create / Edit Note Modal Handling ---
    window.openCreateModal = () => {
        editingNoteId = null;
        noteModalTitle.textContent = 'Create New Note';
        noteForm.reset();
        noteModal.classList.add('active');
    };

    window.triggerEditNote = async (id) => {
        editingNoteId = id;
        noteModalTitle.textContent = 'Edit Note';

        try {
            const res = await fetch(`/api/notes/${id}`);
            const result = await res.json();

            if (result.status === 'success') {
                const note = result.data;
                noteTitleInput.value = note.title;
                noteCategoryInput.value = note.category || '';
                noteTagsInput.value = Array.isArray(note.tags) ? note.tags.join(', ') : '';
                noteContentInput.value = note.content;
                noteModal.classList.add('active');
            } else {
                showToast(result.message, 'error');
            }
        } catch (e) {
            showToast('Failed to load note details', 'error');
        }
    };

    window.closeModal = (modalId) => {
        document.getElementById(modalId)?.classList.remove('active');
    };

    noteForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const tagsArray = noteTagsInput.value
            .split(',')
            .map(t => t.trim())
            .filter(t => t.length > 0);

        const payload = {
            title: noteTitleInput.value.trim(),
            category: noteCategoryInput.value.trim() || null,
            tags: tagsArray,
            content: noteContentInput.value.trim(),
        };

        const isEdit = editingNoteId !== null;
        const url = isEdit ? `/api/notes/${editingNoteId}` : '/api/notes';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showToast(isEdit ? 'Note updated successfully' : 'Note created successfully', 'success');
                closeModal('noteModal');
                fetchNotes(currentPage, currentCategory, currentLimit);
            } else {
                const errMsg = result.message || (result.errors ? Object.values(result.errors).flat().join(' ') : 'Save failed');
                showToast(errMsg, 'error');
            }
        } catch (err) {
            showToast('Network error saving note', 'error');
        }
    });

    // --- Delete Note ---
    window.triggerDeleteNote = async (id) => {
        if (!confirm('Are you sure you want to delete this note entry?')) return;

        try {
            const res = await fetch(`/api/notes/${id}`, { method: 'DELETE' });
            const result = await res.json();

            if (res.ok && result.status === 'success') {
                showToast('Note entry deleted successfully', 'success');
                fetchNotes(currentPage, currentCategory, currentLimit);
            } else {
                showToast(result.message || 'Delete failed', 'error');
            }
        } catch (e) {
            showToast('Network error deleting note', 'error');
        }
    };

    // --- AI Semantic Search ---
    window.openSearchModal = () => {
        searchInput.value = '';
        searchResultsGrid.innerHTML = `
            <div class="empty-state">
                <p>Type a conceptual search query above to query the vector engine...</p>
            </div>
        `;
        searchModal.classList.add('active');
        setTimeout(() => searchInput.focus(), 100);
    };

    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchDebounceTimer);
        const query = e.target.value.trim();

        if (query.length < 2) {
            searchResultsGrid.innerHTML = `
                <div class="empty-state">
                    <p>Type at least 2 characters to trigger vector search...</p>
                </div>
            `;
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            performSemanticSearch(query);
        }, 350);
    });

    async function performSemanticSearch(query) {
        searchResultsGrid.innerHTML = `
            <div class="empty-state">
                <p>Computing vector similarity scores...</p>
            </div>
        `;

        try {
            const res = await fetch(`/api/notes/search?q=${encodeURIComponent(query)}`);
            const result = await res.json();

            if (result.status === 'success') {
                renderSearchResults(result.data, query);
            } else {
                searchResultsGrid.innerHTML = `<div class="empty-state"><p>${escapeHtml(result.message)}</p></div>`;
            }
        } catch (e) {
            searchResultsGrid.innerHTML = `<div class="empty-state"><p>Error executing semantic search.</p></div>`;
        }
    }

    function renderSearchResults(notes, query) {
        if (!notes || notes.length === 0) {
            searchResultsGrid.innerHTML = `
                <div class="empty-state">
                    <p>No relevant entries found matching "<strong>${escapeHtml(query)}</strong>"</p>
                </div>
            `;
            return;
        }

        searchResultsGrid.innerHTML = notes.map(note => `
            <div class="note-card" style="margin-bottom:1rem;">
                <div class="note-header">
                    <h4 class="note-title">${escapeHtml(note.title)}</h4>
                    <span class="score-badge">${note.similarity_percentage} Match</span>
                </div>
                <p class="note-body" style="-webkit-line-clamp:3;">${escapeHtml(note.content)}</p>
                <div class="note-footer">
                    <span>${note.category ? escapeHtml(note.category) : 'General'}</span>
                    <button class="action-btn ai-summary-btn" onclick="closeModal('searchModal'); triggerSummary(${note.id});">
                        Summary
                    </button>
                </div>
            </div>
        `).join('');
    }

    // --- AI Summary Modal ---
    window.triggerSummary = async (id) => {
        summaryNoteTitle.textContent = 'Generating Executive Summary...';
        summaryBody.textContent = 'Extracting key takeaways...';
        summaryModal.classList.add('active');

        try {
            const res = await fetch(`/api/notes/${id}/summary`, { method: 'POST' });
            const result = await res.json();

            if (res.ok && result.status === 'success') {
                summaryNoteTitle.textContent = `Executive Summary: ${result.data.title}`;
                summaryBody.textContent = result.data.summary;
            } else {
                summaryBody.textContent = 'Failed to generate summary: ' + (result.message || 'Unknown error');
            }
        } catch (e) {
            summaryBody.textContent = 'Error connecting to summary service.';
        }
    };

    window.copySummaryToClipboard = () => {
        const text = summaryBody.textContent;
        navigator.clipboard.writeText(text).then(() => {
            showToast('Summary copied to clipboard', 'success');
        });
    };

    // --- Toast Notifications ---
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconSvg = type === 'success' 
            ? `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#047857" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`
            : `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#9f1239" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>`;

        toast.innerHTML = `
            ${iconSvg}
            <span>${escapeHtml(message)}</span>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // --- Helpers ---
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
