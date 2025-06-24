<!-- resources/views/notes/archive.blade.php -->
@extends('layouts.app')

@section('content')
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 24px; font-weight: 400; color: #202124; margin-bottom: 8px;">
            Archive
        </h1>
        <p style="color: #5f6368; font-size: 14px;">
            Your archived notes appear here
        </p>
    </div>

    <!-- Archived Notes Grid -->
    <div class="notes-grid">
        @forelse($notes as $note)
            <div class="note-card" onclick="openNote({{ $note->id }})" data-note-id="{{ $note->id }}"
                style="background-color: {{ $note->color ?? '#ffffff' }};">
                @if($note->title)
                    <div class="note-title">{{ $note->title }}</div>
                @endif

                @if($note->content)
                    <div class="note-content">{{ Str::limit($note->content, 200) }}</div>
                @endif

                <div class="note-toolbar">
                    <div class="toolbar-actions">
                        <!-- Unarchive -->
                        <form action="{{ route('notes.unarchive', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="toolbar-btn" title="Unarchive">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 9.5l5.5 5.5H14v2h-4v-2H6.5L12 9.5zM5.12 5l.81-1h12l.94 1H5.12z" />
                                </svg>
                            </button>
                        </form>

                        <!-- Delete Permanently -->
                        <form action="{{ route('notes.destroy', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="toolbar-btn" title="Delete permanently"
                                onclick="return confirm('Are you sure you want to delete this note permanently?')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M15 4V3H9v1H4v2h1v13c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6h1V4h-5zm2 15H7V6h10v13z" />
                                </svg>
                            </button>
                        </form>

                        <!-- Duplicate -->
                        <form action="{{ route('notes.duplicate', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            <button type="submit" class="toolbar-btn" title="Make a copy">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <div class="note-meta">
                        <small style="color: #9aa0a6; font-size: 12px;">
                            Archived
                            {{ $note->archived_at ? $note->archived_at->diffForHumans() : $note->updated_at->diffForHumans() }}
                        </small>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #9aa0a6;">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor"
                    style="opacity: 0.3; margin-bottom: 16px;">
                    <path
                        d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z" />
                </svg>
                <h3 style="font-size: 22px; font-weight: 400; margin-bottom: 8px; color: #5f6368;">Your archived notes appear
                    here</h3>
                <p style="font-size: 14px;">Notes you archive will be stored here</p>
            </div>
        @endforelse
    </div>

    <!-- Bulk Actions -->
    @if($notes->count() > 0)
        <div style="position: fixed; bottom: 24px; right: 24px;">
            <div
                style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); padding: 8px; display: flex; gap: 8px;">
                <button onclick="selectAll()" class="toolbar-btn" title="Select all">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                    </svg>
                </button>

                <button onclick="unarchiveSelected()" class="toolbar-btn" title="Unarchive selected">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 9.5l5.5 5.5H14v2h-4v-2H6.5L12 9.5zM5.12 5l.81-1h12l.94 1H5.12z" />
                    </svg>
                </button>

                <button onclick="deleteSelected()" class="toolbar-btn" title="Delete selected">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M15 4V3H9v1H4v2h1v13c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6h1V4h-5zm2 15H7V6h10v13z" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <script>
        let selectedNotes = new Set();

        function openNote(noteId) {
            window.location.href = `/notes/${noteId}/edit`;
        }

        function selectAll() {
            const noteCards = document.querySelectorAll('.note-card');
            noteCards.forEach(card => {
                const noteId = card.getAttribute('data-note-id');
                if (selectedNotes.has(noteId)) {
                    selectedNotes.delete(noteId);
                    card.style.border = '1px solid #e0e0e0';
                } else {
                    selectedNotes.add(noteId);
                    card.style.border = '2px solid #1a73e8';
                }
            });
        }

        function unarchiveSelected() {
            if (selectedNotes.size === 0) {
                alert('Please select notes to unarchive');
                return;
            }

            if (confirm(`Unarchive ${selectedNotes.size} selected notes?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("notes.bulk-unarchive") }}';
                form.innerHTML = `
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="note_ids" value="${Array.from(selectedNotes).join(',')}">
                        `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteSelected() {
            if (selectedNotes.size === 0) {
                alert('Please select notes to delete');
                return;
            }

            if (confirm(`Delete ${selectedNotes.size} selected notes permanently?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("notes.bulk-delete") }}';
                form.innerHTML = `
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="note_ids" value="${Array.from(selectedNotes).join(',')}">
                        `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Note selection functionality
        document.addEventListener('click', function (event) {
            if (event.ctrlKey || event.metaKey) {
                const noteCard = event.target.closest('.note-card');
                if (noteCard) {
                    event.preventDefault();
                    const noteId = noteCard.getAttribute('data-note-id');

                    if (selectedNotes.has(noteId)) {
                        selectedNotes.delete(noteId);
                        noteCard.style.border = '1px solid #e0e0e0';
                    } else {
                        selectedNotes.add(noteId);
                        noteCard.style.border = '2px solid #1a73e8';
                    }
                }
            }
        });

        // Clear selection when clicking elsewhere
        document.addEventListener('click', function (event) {
            if (!event.ctrlKey && !event.metaKey && !event.target.closest('.note-card') && !event.target.closest(
                '[style*="position: fixed"]')) {
                selectedNotes.clear();
                document.querySelectorAll('.note-card').forEach(card => {
                    card.style.border = '1px solid #e0e0e0';
                });
            }
        });
    </script>
@endsection