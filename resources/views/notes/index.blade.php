<!-- resources/views/notes/index.blade.php -->
@extends('layouts.app')

@section('content')
    <!-- Debug Info (remove after testing) -->
    <div style="background: yellow; padding: 10px; margin-bottom: 20px;">
        <strong>Debug Info:</strong><br>
        Total notes: {{ $notes->count() }}<br>
        Pinned notes: {{ $pinnedNotes->count() }}<br>
        @if($notes->count() > 0)
            First note title: {{ $notes->first()->title ?? 'No title' }}
        @endif
    </div>

    <!-- New Note Form -->
    <div class="new-note-form" id="newNoteForm">
        <form action="{{ route('notes.store') }}" method="POST">
            @csrf
            <div class="note-form-content">
                <input type="text" name="title" placeholder="Take a note..." class="note-input note-title-input"
                    style="width: 100%; border: none; outline: none; padding: 16px; font-size: 16px; font-weight: 500;"
                    onfocus="expandForm()">

                <div class="note-form-expanded" id="expandedForm" style="display: none;">
                    <textarea name="content" placeholder="Write your note here..." class="note-content-input"
                        style="width: 100%; border: none; outline: none; padding: 0 16px; font-size: 14px; resize: none; min-height: 60px;"
                        rows="3"></textarea>

                    <div class="note-form-actions"
                        style="padding: 8px 16px 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div class="note-actions">
                            <button type="button" class="toolbar-btn" title="Pin note">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M17,4A2,2 0 0,0 15,2H9A2,2 0 0,0 7,4V7L6,8V11H8.5L12.5,15L16.5,11H19V8L18,7V4M15,4V7H9V4H15Z" />
                                </svg>
                            </button>

                            <button type="button" class="toolbar-btn" title="Change color">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" />
                                </svg>
                            </button>
                        </div>

                        <div class="form-buttons">
                            <button type="button" onclick="closeForm()" class="btn-secondary"
                                style="padding: 8px 16px; margin-right: 8px; border: none; background: none; color: #5f6368; cursor: pointer; border-radius: 4px;">
                                Close
                            </button>
                            <button type="submit" class="btn-primary"
                                style="padding: 8px 16px; border: none; background: #1a73e8; color: white; cursor: pointer; border-radius: 4px;">
                                Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Pinned Notes Section (if any) -->
    @if($pinnedNotes->count() > 0)
        <div style="margin-bottom: 32px;">
            <h2
                style="font-size: 14px; font-weight: 500; color: #5f6368; margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.8px;">
                Pinned
            </h2>
            <div class="notes-grid">
                @foreach($pinnedNotes as $note)
                    <div class="note-card" onclick="openNote({{ $note->id }})" data-note-id="{{ $note->id }}"
                        style="border: 2px solid #fbbc04; background-color: {{ $note->color ?? '#ffffff' }};">
                        @if($note->title)
                            <div class="note-title">{{ $note->title }}</div>
                        @endif

                        @if($note->content)
                            <div class="note-content">{{ Str::limit($note->content, 200) }}</div>
                        @endif

                        <div class="note-toolbar">
                            <div class="toolbar-actions">
                                <form action="{{ route('notes.pin', $note) }}" method="POST" style="display: inline;"
                                    onclick="event.stopPropagation()">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="toolbar-btn" title="Unpin">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#fbbc04">
                                            <path
                                                d="M17,4A2,2 0 0,0 15,2H9A2,2 0 0,0 7,4V7L6,8V11H8.5L12.5,15L16.5,11H19V8L18,7V4M15,4V7H9V4H15Z" />
                                        </svg>
                                    </button>
                                </form>

                                <button class="toolbar-btn" title="Change color" onclick="event.stopPropagation()">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" />
                                    </svg>
                                </button>

                                <form action="{{ route('notes.archive', $note) }}" method="POST" style="display: inline;"
                                    onclick="event.stopPropagation()">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="toolbar-btn" title="Archive">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z" />
                                        </svg>
                                    </button>
                                </form>

                                <form action="{{ route('notes.destroy', $note) }}" method="POST" style="display: inline;"
                                    onclick="event.stopPropagation()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="toolbar-btn" title="Delete"
                                        onclick="return confirm('Are you sure you want to delete this note?')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                            <path
                                                d="M15 4V3H9v1H4v2h1v13c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6h1V4h-5zm2 15H7V6h10v13z" />
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            <div class="note-meta">
                                <small style="color: #9aa0a6; font-size: 12px;">
                                    {{ $note->updated_at->format('M j') }}
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <h2
                style="font-size: 14px; font-weight: 500; color: #5f6368; margin: 32px 0 16px; text-transform: uppercase; letter-spacing: 0.8px;">
                Others
            </h2>
        </div>
    @endif

    <!-- Regular Notes Grid -->
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
                        <form action="{{ route('notes.pin', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="toolbar-btn" title="Pin">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M17,4A2,2 0 0,0 15,2H9A2,2 0 0,0 7,4V7L6,8V11H8.5L12.5,15L16.5,11H19V8L18,7V4M15,4V7H9V4H15Z" />
                                </svg>
                            </button>
                        </form>

                        <button class="toolbar-btn" title="Change color" onclick="event.stopPropagation()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" />
                            </svg>
                        </button>

                        <form action="{{ route('notes.archive', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="toolbar-btn" title="Archive">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z" />
                                </svg>
                            </button>
                        </form>

                        <form action="{{ route('notes.destroy', $note) }}" method="POST" style="display: inline;"
                            onclick="event.stopPropagation()">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="toolbar-btn" title="Delete"
                                onclick="return confirm('Are you sure you want to delete this note?')">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M15 4V3H9v1H4v2h1v13c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6h1V4h-5zm2 15H7V6h10v13z" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <div class="note-meta">
                        <small style="color: #9aa0a6; font-size: 12px;">
                            {{ $note->updated_at->format('M j') }}
                        </small>
                    </div>
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #9aa0a6;">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor"
                    style="opacity: 0.3; margin-bottom: 16px;">
                    <path
                        d="M9 21c0 .5.4 1 1 1h4c.6 0 1-.5 1-1v-1H9v1zm3-19C8.1 2 5 5.1 5 9c0 2.4 1.2 4.5 3 5.7V17c0 .5.4 1 1 1h6c.6 0 1-.5 1-1v-2.3c1.8-1.3 3-3.4 3-5.7 0-3.9-3.1-7-7-7z" />
                </svg>
                <h3 style="font-size: 22px; font-weight: 400; margin-bottom: 8px; color: #5f6368;">Your notes appear here</h3>
                <p style="font-size: 14px;">Add a note to get started</p>
            </div>
        @endforelse
    </div>

    <script>
        function expandForm() {
            document.getElementById('expandedForm').style.display = 'block';
            document.getElementById('newNoteForm').classList.add('form-expanded');
        }

        function closeForm() {
            document.getElementById('expandedForm').style.display = 'none';
            document.getElementById('newNoteForm').classList.remove('form-expanded');
            document.querySelector('.note-title-input').value = '';
            document.querySelector('.note-content-input').value = '';
        }

        function openNote(noteId) {
            window.location.href = `/notes/${noteId}/edit`;
        }

        // Close form when clicking outside
        document.addEventListener('click', function (event) {
            const form = document.getElementById('newNoteForm');
            if (!form.contains(event.target)) {
                closeForm();
            }
        });

        // Auto-resize textarea
        document.querySelector('.note-content-input').addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
@endsection