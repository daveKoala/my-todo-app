<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * Get the authenticated user or create/get demo user for testing
     */
    private function getUser()
    {
        $user = Auth::user();

        if (!$user) {
            // Get first user or create demo user
            $user = \App\Models\User::first();

            if (!$user) {
                $user = \App\Models\User::create([
                    'name' => 'Demo User',
                    'email' => 'demo@example.com',
                    'password' => bcrypt('password'),
                ]);
            }
        }

        return $user;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $user = $this->getUser();

        // Get only active notes (not archived, not deleted)
        $activeNotes = $user->notes()
            ->where('is_archived', 0)
            ->whereNull('deleted_at')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        $pinnedNotes = $activeNotes->where('is_pinned', 1);
        $notes = $activeNotes->where('is_pinned', 0);

        return view('notes.index', compact('notes', 'pinnedNotes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('notes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:10000',
            'color' => 'nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ]);

        // Don't create empty notes
        if (empty(trim($validated['title'] ?? '')) && empty(trim($validated['content'] ?? ''))) {
            return redirect()->route('notes.index');
        }

        $user = $this->getUser();
        $note = $user->notes()->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'color' => $validated['color'] ?? '#ffffff',
        ]);

        return redirect()->route('notes.index')
            ->with('success', "Note '{$note->title}' created successfully!");
    }

    /**
     * Display the specified resource.
     */
    public function show(Note $note): View
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        return view('notes.show', compact('note'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Note $note): View
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        return view('notes.edit', compact('note'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Note $note)
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:10000',
            'color' => 'nullable|string|regex:/^#[a-f0-9]{6}$/i',
            'is_pinned' => 'sometimes|boolean',
        ]);

        // Delete if note becomes empty
        if (empty(trim($validated['title'] ?? '')) && empty(trim($validated['content'] ?? ''))) {
            $note->delete();
            return redirect()->route('notes.index')
                ->with('success', 'Empty note deleted!');
        }

        $note->update($validated);

        // Return JSON for AJAX requests
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Note updated successfully!']);
        }

        return redirect()->route('notes.index')
            ->with('success', 'Note updated successfully!');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Note $note): RedirectResponse
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        $note->delete();

        return redirect()->route('notes.index')
            ->with('success', 'Note moved to trash!');
    }

    /**
     * Toggle pin status of a note.
     */
    public function pin(Note $note): RedirectResponse
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        $note->togglePin();

        $message = $note->is_pinned ? 'Note pinned!' : 'Note unpinned!';

        return redirect()->back()
            ->with('success', $message);
    }

    /**
     * Archive a note.
     */
    public function archive(Note $note): RedirectResponse
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        $note->archive();

        return redirect()->back()
            ->with('success', 'Note archived!');
    }

    /**
     * Unarchive a note.
     */
    public function unarchive(Note $note): RedirectResponse
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        $note->unarchive();

        return redirect()->back()
            ->with('success', 'Note unarchived!');
    }

    /**
     * Show archived notes.
     */
    public function archived(): View
    {
        $user = $this->getUser();

        $notes = $user->notes()
            ->archived()
            ->defaultOrder()
            ->get();

        return view('notes.archive', compact('notes'));
    }

    /**
     * Show trash (soft deleted notes).
     */
    public function trash(): View
    {
        $user = $this->getUser();

        $notes = $user->notes()
            ->onlyTrashed()
            ->orderBy('deleted_at', 'desc')
            ->get();

        // Calculate old notes count for the template
        $oldNotesCount = $notes->filter(function ($note) {
            return $note->deleted_at && $note->deleted_at->lt(now()->subDays(7));
        })->count();

        return view('notes.trash', compact('notes', 'oldNotesCount'));
    }

    /**
     * Restore a soft deleted note.
     */
    public function restore(Note $note): RedirectResponse
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        $note->restore();

        return redirect()->back()
            ->with('success', 'Note restored!');
    }

    /**
     * Permanently delete a note.
     */
    public function forceDelete(Note $note): RedirectResponse
    {
        $user = $this->getUser();

        // Then do your own authorization check
        if ($note->user_id !== $user->id) {
            abort(403);
        }

        $note->forceDelete();

        return redirect()->back()
            ->with('success', 'Note permanently deleted!');
    }

    /**
     * Duplicate a note.
     */
    public function duplicate(Note $note): RedirectResponse
    {
        $this->authorize('view', $note);

        $duplicate = $note->duplicate();

        return redirect()->route('notes.edit', $duplicate)
            ->with('success', 'Note duplicated!');
    }

    /**
     * Search notes.
     */
    public function search(Request $request): View
    {
        $query = $request->get('q');
        $filter = $request->get('filter', 'all');

        $user = $this->getUser();
        $notesQuery = $user->notes();

        // Apply search if query exists
        if ($query) {
            $notesQuery->search($query);
        }

        // Apply filters
        switch ($filter) {
            case 'pinned':
                $notesQuery->pinned();
                break;
            case 'archived':
                $notesQuery->archived();
                break;
            default:
                $notesQuery->active(); // Default to active notes
        }

        $notes = $notesQuery->defaultOrder()->get();

        // Get counts for filter tabs
        $totalCount = $user->notes()->search($query ?? '')->count();
        $pinnedCount = $user->notes()->search($query ?? '')->pinned()->count();
        $archivedCount = $user->notes()->search($query ?? '')->archived()->count();

        return view('notes.search', compact(
            'notes',
            'query',
            'filter',
            'totalCount',
            'pinnedCount',
            'archivedCount'
        ));
    }

    /**
     * Empty trash (permanently delete all trashed notes).
     */
    public function emptyTrash(): RedirectResponse
    {
        $count = Auth::user()->notes()->onlyTrashed()->count();

        Auth::user()->notes()->onlyTrashed()->forceDelete();

        return redirect()->route('notes.trash')
            ->with('success', "Permanently deleted {$count} notes!");
    }

    /**
     * Clean up old notes (7+ days in trash).
     */
    public function cleanupOld(): RedirectResponse
    {
        $count = Auth::user()->notes()
            ->readyForDeletion()
            ->count();

        Auth::user()->notes()
            ->readyForDeletion()
            ->forceDelete();

        return redirect()->route('notes.trash')
            ->with('success', "Cleaned up {$count} old notes!");
    }

    /**
     * Bulk restore notes.
     */
    public function bulkRestore(Request $request): RedirectResponse
    {
        $noteIds = explode(',', $request->note_ids);

        $notes = Auth::user()->notes()
            ->onlyTrashed()
            ->whereIn('id', $noteIds);

        $count = $notes->count();
        $notes->restore();

        return redirect()->back()
            ->with('success', "Restored {$count} notes!");
    }

    /**
     * Bulk unarchive notes.
     */
    public function bulkUnarchive(Request $request): RedirectResponse
    {
        $noteIds = explode(',', $request->note_ids);

        $count = Auth::user()->notes()
            ->whereIn('id', $noteIds)
            ->update(['is_archived' => false, 'archived_at' => null]);

        return redirect()->back()
            ->with('success', "Unarchived {$count} notes!");
    }

    /**
     * Bulk delete notes.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $noteIds = explode(',', $request->note_ids);

        $count = Auth::user()->notes()
            ->whereIn('id', $noteIds)
            ->count();

        Auth::user()->notes()
            ->whereIn('id', $noteIds)
            ->delete();

        return redirect()->back()
            ->with('success', "Moved {$count} notes to trash!");
    }

    /**
     * Bulk force delete notes.
     */
    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $noteIds = explode(',', $request->note_ids);

        $count = Auth::user()->notes()
            ->onlyTrashed()
            ->whereIn('id', $noteIds)
            ->count();

        Auth::user()->notes()
            ->onlyTrashed()
            ->whereIn('id', $noteIds)
            ->forceDelete();

        return redirect()->back()
            ->with('success', "Permanently deleted {$count} notes!");
    }
}