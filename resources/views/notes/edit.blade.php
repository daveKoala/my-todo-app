<!-- resources/views/notes/edit.blade.php -->
@extends('layouts.app')

@section('content')
    <div style="max-width: 600px; margin: 0 auto;">
        <!-- Back Button -->
        <div style="margin-bottom: 20px;">
            <a href="{{ route('notes.index') }}" class="toolbar-btn"
                style="display: inline-flex; align-items: center; text-decoration: none; color: #5f6368;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 8px;">
                    <path d="M20 22L4 12l16-10v20z" />
                </svg>
                Back to notes
            </a>
        </div>

        <!-- Note Edit Form -->
        <form action="{{ route('notes.update', $note) }}" method="POST" id="noteForm">
            @csrf
            @method('PUT')

            <div class="note-card" style="margin-bottom: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.15); border: none;">
                <!-- Title Input -->
                <input type="text" name="title" placeholder="Title" value="{{ old('title', $note->title) }}"
                    class="note-title-input"
                    style="width: 100%; border: none; outline: none; font-size: 22px; font-weight: 400; color: #202124; background: transparent; margin-bottom: 16px;">

                <!-- Content Textarea -->
                <textarea name="content" placeholder="Take a note..." class="note-content-input"
                    style="width: 100%; border: none; outline: none; font-size: 16px; color: #202124; background: transparent; resize: none; min-height: 200px; line-height: 1.5;"
                    rows="10">{{ old('content', $note->content) }}</textarea>

                <!-- Note Metadata -->
                <div style="margin: 20px 0; padding-top: 16px; border-top: 1px solid #e8eaed;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; color: #5f6368; font-size: 12px;">
                        <span>Created: {{ $note->created_at->format('M j, Y g:i A') }}</span>
                        <span>Modified: {{ $note->updated_at->format('M j, Y g:i A') }}</span>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="note-toolbar"
                    style="opacity: 1; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e8eaed;">
                    <div class="toolbar-actions">
                        <!-- Pin Toggle -->
                        <button type="button" class="toolbar-btn" title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}"
                            onclick="togglePin()">
                            <svg width="20" height="20" viewBox="0 0 24 24"
                                fill="{{ $note->is_pinned ? '#fbbc04' : 'currentColor' }}">
                                <path
                                    d="M17,4A2,2 0 0,0 15,2H9A2,2 0 0,0 7,4V7L6,8V11H8.5L12.5,15L16.5,11H19V8L18,7V4M15,4V7H9V4H15Z" />
                            </svg>
                        </button>

                        <!-- Color Picker -->
                        <div class="color-picker" style="position: relative; display: inline-block;">
                            <button type="button" class="toolbar-btn" title="Change color" onclick="toggleColorPicker()">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8Z" />
                                </svg>
                            </button>

                            <div id="colorPicker" class="color-picker-dropdown"
                                style="display: none; position: absolute; bottom: 100%; left: 0; background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 100;">
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                                    <button type="button" class="color-btn" data-color="white"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #ffffff; border: 2px solid #e0e0e0; cursor: pointer;"
                                        onclick="setColor('white')"></button>
                                    <button type="button" class="color-btn" data-color="red"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #f28b82; border: none; cursor: pointer;"
                                        onclick="setColor('#f28b82')"></button>
                                    <button type="button" class="color-btn" data-color="orange"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #fbbc04; border: none; cursor: pointer;"
                                        onclick="setColor('#fbbc04')"></button>
                                    <button type="button" class="color-btn" data-color="yellow"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #fff475; border: none; cursor: pointer;"
                                        onclick="setColor('#fff475')"></button>
                                    <button type="button" class="color-btn" data-color="green"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #ccff90; border: none; cursor: pointer;"
                                        onclick="setColor('#ccff90')"></button>
                                    <button type="button" class="color-btn" data-color="teal"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #a7ffeb; border: none; cursor: pointer;"
                                        onclick="setColor('#a7ffeb')"></button>
                                    <button type="button" class="color-btn" data-color="blue"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #cbf0f8; border: none; cursor: pointer;"
                                        onclick="setColor('#cbf0f8')"></button>
                                    <button type="button" class="color-btn" data-color="darkblue"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #aecbfa; border: none; cursor: pointer;"
                                        onclick="setColor('#aecbfa')"></button>
                                    <button type="button" class="color-btn" data-color="purple"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #d7aefb; border: none; cursor: pointer;"
                                        onclick="setColor('#d7aefb')"></button>
                                    <button type="button" class="color-btn" data-color="pink"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #fdcfe8; border: none; cursor: pointer;"
                                        onclick="setColor('#fdcfe8')"></button>
                                    <button type="button" class="color-btn" data-color="brown"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #e6c9a8; border: none; cursor: pointer;"
                                        onclick="setColor('#e6c9a8')"></button>
                                    <button type="button" class="color-btn" data-color="gray"
                                        style="width: 32px; height: 32px; border-radius: 50%; background: #e8eaed; border: none; cursor: pointer;"
                                        onclick="setColor('#e8eaed')"></button>
                                </div>
                            </div>
                        </div>

                        <!-- Archive -->
                        <button type="button" class="toolbar-btn" title="Archive" onclick="archiveNote()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z" />
                            </svg>
                        </button>

                        <!-- Delete -->
                        <button type="button" class="toolbar-btn" title="Delete" onclick="deleteNote()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M15 4V3H9v1H4v2h1v13c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V6h1V4h-5zm2 15H7V6h10v13z" />
                            </svg>
                        </button>

                        <!-- Duplicate -->
                        <button type="button" class="toolbar-btn" title="Make a copy" onclick="duplicateNote()">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Save Status -->
                    <div class="save-status" style="color: #5f6368; font-size: 12px; display: flex; align-items: center;">
                        <span id="saveStatus">All changes saved</span>
                    </div>
                </div>
            </div>

            <!-- Hidden color input -->
            <input type="hidden" name="color" value="{{ $note->color ?? '#ffffff' }}" id="colorInput">

            <!-- Hidden pin input -->
            <input type="hidden" name="is_pinned" value="{{ $note->is_pinned ? '1' : '0' }}" id="pinInput">
        </form>

        <!-- Hidden forms for actions -->
        <form id="pinForm" action="{{ route('notes.pin', $note) }}" method="POST" style="display: none;">
            @csrf
            @method('PATCH')
        </form>

        <form id="archiveForm" action="{{ route('notes.archive', $note) }}" method="POST" style="display: none;">
            @csrf
            @method('PATCH')
        </form>

        <form id="deleteForm" action="{{ route('notes.destroy', $note) }}" method="POST" style="display: none;">
            @csrf
            @method('DELETE')
        </form>

        <form id="duplicateForm" action="{{ route('notes.duplicate', $note) }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>

    <script>
        let autoSaveTimeout;
        let hasUnsavedChanges = false;

        // Auto-save functionality
        function autoSave() {
            clearTimeout(autoSaveTimeout);
            hasUnsavedChanges = true;
            document.getElementById('saveStatus').textContent = 'Saving...';

            autoSaveTimeout = setTimeout(() => {
                const form = document.getElementById('noteForm');
                const formData = new FormData(form);

                // Ensure the _method field is included for PUT request
                formData.append('_method', 'PUT');

                fetch(form.action, {
                    method: 'POST', // Laravel expects POST with _method field
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json', // Important: Tell server we expect JSON
                    },
                    body: formData
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.deleted) {
                            // Note was deleted because it was empty
                            document.getElementById('saveStatus').textContent = 'Note deleted (was empty)';
                            // Immediately redirect to notes index
                            window.location.href = '/notes';
                        } else {
                            document.getElementById('saveStatus').textContent = 'All changes saved';
                            hasUnsavedChanges = false;
                        }
                    })
                    .catch(error => {
                        document.getElementById('saveStatus').textContent = 'Error saving';
                        console.error('Auto-save error:', error);
                    });
            }, 1000);
        }

        // Add auto-save listeners
        document.querySelector('input[name="title"]').addEventListener('input', autoSave);
        document.querySelector('textarea[name="content"]').addEventListener('input', autoSave);

        // Auto-resize textarea
        const textarea = document.querySelector('.note-content-input');

        function resizeTextarea() {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }

        textarea.addEventListener('input', resizeTextarea);
        // Initial resize
        resizeTextarea();

        // Color picker functionality
        function toggleColorPicker() {
            const picker = document.getElementById('colorPicker');
            picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
        }

        function setColor(color) {
            document.getElementById('colorInput').value = color;
            document.querySelector('.note-card').style.backgroundColor = color;
            document.getElementById('colorPicker').style.display = 'none';
            autoSave();
        }

        // Set initial color
        const currentColor = '{{ $note->color ?? "#ffffff" }}';
        document.querySelector('.note-card').style.backgroundColor = currentColor;

        // Pin functionality
        function togglePin() {
            const pinInput = document.getElementById('pinInput');
            const currentValue = pinInput.value === '1';
            pinInput.value = currentValue ? '0' : '1';

            const pinButton = document.querySelector('[title*="Pin"]');
            const svg = pinButton.querySelector('svg');

            if (!currentValue) {
                svg.setAttribute('fill', '#fbbc04');
                pinButton.setAttribute('title', 'Unpin');
            } else {
                svg.setAttribute('fill', 'currentColor');
                pinButton.setAttribute('title', 'Pin');
            }

            autoSave();
        }

        // Archive functionality
        function archiveNote() {
            if (confirm('Archive this note?')) {
                document.getElementById('archiveForm').submit();
            }
        }

        // Delete functionality
        function deleteNote() {
            console.log("Deleting note...");
            if (confirm('Delete this note permanently?')) {
                document.getElementById('deleteForm').submit();
            }
        }

        // Duplicate functionality
        function duplicateNote() {
            document.getElementById('duplicateForm').submit();
        }

        // Close color picker when clicking outside
        document.addEventListener('click', function (event) {
            const colorPicker = document.querySelector('.color-picker');
            if (!colorPicker.contains(event.target)) {
                document.getElementById('colorPicker').style.display = 'none';
            }
        });

        // Warn about unsaved changes
        window.addEventListener('beforeunload', function (event) {
            if (hasUnsavedChanges) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function (event) {
            // Ctrl/Cmd + S to save
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault();
                autoSave();
            }

            // Escape to go back
            if (event.key === 'Escape') {
                window.location.href = '{{ route("notes.index") }}';
            }
        });
    </script>
@endsection