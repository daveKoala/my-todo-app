<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\NoteController;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('todos', TodoController::class);

// Notes routes - you can remove auth middleware for testing
Route::resource('notes', NoteController::class);

// Additional note actions
Route::patch('notes/{note}/pin', [NoteController::class, 'pin'])->name('notes.pin');
Route::patch('notes/{note}/archive', [NoteController::class, 'archive'])->name('notes.archive');
Route::patch('notes/{note}/unarchive', [NoteController::class, 'unarchive'])->name('notes.unarchive');
Route::patch('notes/{note}/restore', [NoteController::class, 'restore'])->name('notes.restore');
Route::delete('notes/{note}/force', [NoteController::class, 'forceDelete'])->name('notes.force-delete');
Route::post('notes/{note}/duplicate', [NoteController::class, 'duplicate'])->name('notes.duplicate');

// Special views
Route::get('notes-archived', [NoteController::class, 'archived'])->name('notes.archived');
Route::get('notes-trash', [NoteController::class, 'trash'])->name('notes.trash');
Route::get('notes-search', [NoteController::class, 'search'])->name('notes.search');

// Bulk operations
Route::patch('notes-bulk/restore', [NoteController::class, 'bulkRestore'])->name('notes.bulk-restore');
Route::patch('notes-bulk/unarchive', [NoteController::class, 'bulkUnarchive'])->name('notes.bulk-unarchive');
Route::delete('notes-bulk/delete', [NoteController::class, 'bulkDelete'])->name('notes.bulk-delete');
Route::delete('notes-bulk/force-delete', [NoteController::class, 'bulkForceDelete'])->name('notes.bulk-force-delete');

// Trash management
Route::delete('notes-trash/empty', [NoteController::class, 'emptyTrash'])->name('notes.empty-trash');
Route::delete('notes-trash/cleanup', [NoteController::class, 'cleanupOld'])->name('notes.cleanup-old');