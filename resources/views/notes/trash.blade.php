<!-- resources/views/notes/trash.blade.php -->
@extends('layouts.app')

@section('content')
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 24px; font-weight: 400; color: #202124; margin-bottom: 8px;">
            Trash
        </h1>
        <p style="color: #5f6368; font-size: 14px;">
            Notes in trash are deleted after 7 days
        </p>

        @if($notes->count() > 0)
            <div style="margin-top: 16px;">
                <button onclick="emptyTrash()" class="btn-danger"
                    style="padding: 8px 16px; border: none; background: #ea4335; color: white; cursor: pointer; border-radius: 4px; font-size: 14px;">
                    Empty trash
                </button>
            </div>
        @endif
    </div>

    <!-- Trash Notes Grid -->
    <div class="notes-grid">
        @forelse($notes as $note)
            <div class="note-card" data-note-id="{{ $note->id }}"
                style="background-color: {{ $note->color ?? '#ffffff' }}; opacity: 0.7; cursor: default;"
                onclick="event.preventDefault()">
                @if($note->title)
                    <div class="note-title" style="text-decoration: line-through;">{{ $note->title }}</div>
                @endif

                @if($note->content)
                    <div class="note-content" style="text-decoration: line-through;">{{ Str::limit($note->content, 200) }}</div>
                @endif

                <div class="note-toolbar" style="opacity: 1;">
                    <div class="toolbar-actions">
                        <!-- Restore -->
                        <form action="{{ route('notes.restore', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="toolbar-btn" title="Restore">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M13,3A9,9 0 0,0 4,12H1L4.89,15.89L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3Z" />
                                </svg>
                            </button>
                        </form>

                        <!-- Delete Forever -->
                        <form action="{{ route('notes.force-delete', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="toolbar-btn" title="Delete forever"
                                onclick="return confirm('Are you sure you want to delete this note forever? This action cannot be undone.')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19M8,9H16V19H8V9M15.5,4L14.5,3H9.5L8.5,4H5V6H19V4H15.5Z" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <div class="note-meta">
                        <small style="color: #9aa0a6; font-size: 12px;">
                            @if($note->deleted_at)
                                Deleted {{ $note->deleted_at->diffForHumans() }}
                            @else
                                {{ $note->updated_at->format('M j') }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #9aa0a6;">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor"
                    style="opacity: 0.3; margin-bottom: 16px;">
                    <path d="M15 4V3H9v1H4v2h1v13c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6h1V4h-5zm2 15H7V6h10v13z" />
                </svg>
                <h3 style="font-size: 22px; font-weight: 400; margin-bottom: 8px; color: #5f6368;">No notes in trash</h3>
                <p style="font-size: 14px;">Notes you delete will appear here before being permanently removed</p>
            </div>
        @endforelse
    </div>

    <!-- Auto-delete warning -->
    @if($notes->count() > 0)
        @php
            $expiredNotesCount = $notes->filter(function ($note) {
                return $note->deleted_at && $note->deleted_at->lt(now()->subDays(7));
            })->count();
        @endphp

        <div
            style="background: #fef7e0; border: 1px solid #fbbc04; border-radius: 8px; padding: 16px; margin-top: 32px; display: flex; align-items: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#ea8600" style="margin-right: 12px; flex-shrink: 0;">
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z" />
            </svg>
            <div style="color: #ea8600; font-size: 14px;">
                <strong>Notes in trash are automatically deleted after 7 days.</strong>
                @if($expiredNotesCount > 0)
                    {{ $expiredNotesCount }} {{ Str::plural('note', $expiredNotesCount) }}
                    {{ $expiredNotesCount === 1 ? 'is' : 'are' }} ready to be permanently deleted.
                @endif
            </div>
        </div>
    @endif

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

                <button onclick="restoreSelected()" class="toolbar-btn" title="Restore selected">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M13,3A9,9 0 0,0 4,12H1L4.89,15.89L4.96,16.03L9,12H6A7,7 0 0,1 13,5A7,7 0 0,1 20,12A7,7 0 0,1 13,19C11.07,19 9.32,18.21 8.06,16.94L6.64,18.36C8.27,20 10.5,21 13,21A9,9 0 0,0 22,12A9,9 0 0,0 13,3Z" />
                    </svg>
                </button>

                <button onclick="deleteForeverSelected()" class="toolbar-btn" title="Delete forever" style="color: #ea4335;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19M8,9H16V19H8V9M15.5,4L14.5,3H9.5L8.5,4H5V6H19V4H15.5Z" />
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Hidden form for empty trash -->
    <form id="emptyTrashForm" action="{{ route('notes.empty-trash') }}" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        let selectedNotes = new Set();

        function selectAll() {
            const noteCards = document.querySelectorAll('.note-card');
            const allSelected = selectedNotes.size === noteCards.length;

            if (allSelected) {
                // Deselect all
                selectedNotes.clear();
                noteCards.forEach(card => {
                    card.style.border = '1px solid #e0e0e0';
                });
            } else {
                // Select all
                selectedNotes.clear();
                noteCards.forEach(card => {
                    const noteId = card.getAttribute('data-note-id');
                    selectedNotes.add(noteId);
                    card.style.border = '2px solid #1a73e8';
                });
            }
        }

        function restoreSelected() {
            if (selectedNotes.size === 0) {
                alert('Please select notes to restore');
                return;
            }

            if (confirm(`Restore ${selectedNotes.size} selected notes?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("notes.bulk-restore") }}';
                form.innerHTML = `
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="note_ids" value="${Array.from(selectedNotes).join(',')}">
                            `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteForeverSelected() {
            if (selectedNotes.size === 0) {
                alert('Please select notes to delete');
                return;
            }

            if (confirm(`Delete ${selectedNotes.size} selected notes forever? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("notes.bulk-force-delete") }}';
                form.innerHTML = `
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="note_ids" value="${Array.from(selectedNotes).join(',')}">
                            `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function emptyTrash() {
            if (confirm('Empty trash? All notes will be permanently deleted. This action cannot be undone.')) {
                document.getElementById('emptyTrashForm').submit();
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

        // Auto-cleanup old notes (frontend notification)

        @if($oldNotesCount > 0)
            setTimeout(() => {
                if (confirm('{{ $oldNotesCount }} notes have been in trash...')) {
                    // ... rest of code
                }
            }, 2000);
        @endif


        @if($oldNotesCount > 0)
            setTimeout(() => {
                if (confirm(
                    '{{ $oldNotesCount }} notes have been in trash for more than 7 days. Would you like to permanently delete them now?'
                )) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route("notes.cleanup-old") }}';
                    form.innerHTML = `
                                        @csrf
                                        @method('DELETE')
                            `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }, 2000);
        @endif
    </script>
@endsection