<!-- resources/views/notes/search.blade.php -->
@extends('layouts.app')

@section('content')
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 24px; font-weight: 400; color: #202124; margin-bottom: 8px;">
            Search Results
        </h1>
        <p style="color: #5f6368; font-size: 14px;">
            @if($query)
                {{ $notes->count() }} {{ Str::plural('result', $notes->count()) }} for "<strong>{{ $query }}</strong>"
            @else
                Enter a search term to find your notes
            @endif
        </p>

        @if($query && $notes->count() === 0)
            <div style="margin-top: 16px;">
                <a href="{{ route('notes.index') }}" class="btn-secondary"
                    style="padding: 8px 16px; border: 1px solid #dadce0; background: white; color: #5f6368; text-decoration: none; border-radius: 4px; font-size: 14px;">
                    View all notes
                </a>
            </div>
        @endif
    </div>

    <!-- Search Form -->
    <div class="new-note-form" style="margin-bottom: 32px;">
        <form action="{{ route('notes.search') }}" method="GET">
            <div style="padding: 16px; display: flex; gap: 12px; align-items: center;">
                <input type="text" name="q" placeholder="Search your notes..." value="{{ $query }}" class="search-input"
                    style="flex: 1; border: none; outline: none; font-size: 16px; background: transparent;" autofocus>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn-primary"
                        style="padding: 8px 16px; border: none; background: #1a73e8; color: white; cursor: pointer; border-radius: 4px; font-size: 14px;">
                        Search
                    </button>

                    @if($query)
                        <a href="{{ route('notes.search') }}" class="btn-secondary"
                            style="padding: 8px 16px; border: 1px solid #dadce0; background: white; color: #5f6368; text-decoration: none; border-radius: 4px; font-size: 14px; display: inline-flex; align-items: center;">
                            Clear
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Search Filters -->
    @if($query)
        <div style="margin-bottom: 24px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('notes.search', ['q' => $query, 'filter' => 'all']) }}"
                class="filter-btn {{ (!request('filter') || request('filter') === 'all') ? 'active' : '' }}"
                style="padding: 6px 12px; border-radius: 16px; font-size: 12px; text-decoration: none; border: 1px solid #dadce0; {{ (!request('filter') || request('filter') === 'all') ? 'background: #e8f0fe; color: #1a73e8; border-color: #1a73e8;' : 'background: white; color: #5f6368;' }}">
                All ({{ $totalCount }})
            </a>

            <a href="{{ route('notes.search', ['q' => $query, 'filter' => 'pinned']) }}"
                class="filter-btn {{ request('filter') === 'pinned' ? 'active' : '' }}"
                style="padding: 6px 12px; border-radius: 16px; font-size: 12px; text-decoration: none; border: 1px solid #dadce0; {{ request('filter') === 'pinned' ? 'background: #e8f0fe; color: #1a73e8; border-color: #1a73e8;' : 'background: white; color: #5f6368;' }}">
                Pinned ({{ $pinnedCount }})
            </a>

            <a href="{{ route('notes.search', ['q' => $query, 'filter' => 'archived']) }}"
                class="filter-btn {{ request('filter') === 'archived' ? 'active' : '' }}"
                style="padding: 6px 12px; border-radius: 16px; font-size: 12px; text-decoration: none; border: 1px solid #dadce0; {{ request('filter') === 'archived' ? 'background: #e8f0fe; color: #1a73e8; border-color: #1a73e8;' : 'background: white; color: #5f6368;' }}">
                Archived ({{ $archivedCount }})
            </a>
        </div>
    @endif

    <!-- Search Results -->
    @if($query)
        <div class="notes-grid">
            @forelse($notes as $note)
                <div class="note-card" onclick="openNote({{ $note->id }})" data-note-id="{{ $note->id }}"
                    style="background-color: {{ $note->color ?? '#ffffff' }};">
                    @if($note->title)
                        <div class="note-title">{!! highlightSearchTerm($note->title, $query) !!}</div>
                    @endif

                    @if($note->content)
                        <div class="note-content">{!! highlightSearchTerm(Str::limit($note->content, 200), $query) !!}</div>
                    @endif

                    <div class="note-toolbar">
                        <div class="toolbar-actions">
                            <!-- Pin Toggle -->
                            <form action="{{ route('notes.pin', $note) }}" method="POST" style="display: inline;"
                                onclick="event.stopPropagation()">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="toolbar-btn" title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24"
                                        fill="{{ $note->is_pinned ? '#fbbc04' : 'currentColor' }}">
                                        <path
                                            d="M17,4A2,2 0 0,0 15,2H9A2,2 0 0,0 7,4V7L6,8V11H8.5L12.5,15L16.5,11H19V8L18,7V4M15,4V7H9V4H15Z" />
                                    </svg>
                                </button>
                            </form>

                            <!-- Archive/Unarchive -->
                            @if($note->is_archived)
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
                            @else
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
                            @endif

                            <!-- Delete -->
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
                                @if($note->is_archived)
                                    • Archived
                                @endif
                                @if($note->is_pinned)
                                    • Pinned
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: #9aa0a6;">
                    <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor"
                        style="opacity: 0.3; margin-bottom: 16px;">
                        <path
                            d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
                    </svg>
                    <h3 style="font-size: 22px; font-weight: 400; margin-bottom: 8px; color: #5f6368;">No notes found</h3>
                    <p style="font-size: 14px;">Try searching for something else or check your spelling</p>
                </div>
            @endforelse
        </div>
    @else
        <!-- Recent searches or suggestions when no query -->
        <div style="text-align: center; padding: 60px 20px; color: #9aa0a6;">
            <svg width="120" height="120" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.3; margin-bottom: 16px;">
                <path
                    d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z" />
            </svg>
            <h3 style="font-size: 22px; font-weight: 400; margin-bottom: 8px; color: #5f6368;">Search your notes</h3>
            <p style="font-size: 14px;">Enter keywords to find what you're looking for</p>
        </div>
    @endif

    <script>
        function openNote(noteId) {
            window.location.href = `/notes/${noteId}/edit`;
        }

        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput && !searchInput.value) {
                searchInput.focus();
            }
        });

        // Search on Enter key
        document.querySelector('input[name="q"]').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    </script>

    @php
        // Helper function to highlight search terms in results
        function highlightSearchTerm($text, $query)
        {
            if (!$query)
                return e($text);

            $highlighted = preg_replace(
                '/(' . preg_quote($query, '/') . ')/i',
                '<mark style="background: #fff475; padding: 0 2px;">$1</mark>',
                e($text)
            );

            return $highlighted;
        }
    @endphp
@endsection