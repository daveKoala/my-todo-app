<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'color',
        'is_pinned',
        'is_archived',
        'archived_at',
        'sort_order',
        'labels',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_pinned' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
        'labels' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [];

    /**
     * Get the user that owns the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active (non-archived, non-trashed) notes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope a query to only include archived notes.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope a query to only include pinned notes.
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope a query to only include unpinned notes.
     */
    public function scopeUnpinned(Builder $query): Builder
    {
        return $query->where('is_pinned', false);
    }

    /**
     * Scope a query to search notes by title and content.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        });
    }

    /**
     * Scope a query to use full-text search (MySQL).
     */
    public function scopeFullTextSearch(Builder $query, string $search): Builder
    {
        return $query->whereRaw(
            "MATCH(title, content) AGAINST(? IN NATURAL LANGUAGE MODE)",
            [$search]
        );
    }

    /**
     * Scope a query to order notes by pinned status and then by updated date.
     */
    public function scopeDefaultOrder(Builder $query): Builder
    {
        return $query->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Scope a query for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Toggle the pinned status of the note.
     */
    public function togglePin(): bool
    {
        $this->is_pinned = !$this->is_pinned;
        return $this->save();
    }

    /**
     * Pin the note.
     */
    public function pin(): bool
    {
        $this->is_pinned = true;
        return $this->save();
    }

    /**
     * Unpin the note.
     */
    public function unpin(): bool
    {
        $this->is_pinned = false;
        return $this->save();
    }

    /**
     * Archive the note.
     */
    public function archive(): bool
    {
        $this->is_archived = true;
        $this->archived_at = now();
        return $this->save();
    }

    /**
     * Unarchive the note.
     */
    public function unarchive(): bool
    {
        $this->is_archived = false;
        $this->archived_at = null;
        return $this->save();
    }

    /**
     * Check if the note is empty (no title or content).
     */
    public function isEmpty(): bool
    {
        return empty(trim($this->title ?? '')) && empty(trim($this->content ?? ''));
    }

    /**
     * Get a truncated version of the content.
     */
    public function getTruncatedContentAttribute(int $length = 200): string
    {
        return \Illuminate\Support\Str::limit($this->content ?? '', $length);
    }

    /**
     * Get the note's preview text (title or content).
     */
    public function getPreviewAttribute(): string
    {
        if (!empty($this->title)) {
            return $this->title;
        }

        return $this->getTruncatedContentAttribute(100);
    }

    /**
     * Check if the note has been recently updated (within last 24 hours).
     */
    public function isRecentlyUpdated(): bool
    {
        return $this->updated_at->greaterThan(Carbon::now()->subDay());
    }

    /**
     * Get notes that should be auto-deleted (in trash for more than 7 days).
     */
    public function scopeReadyForDeletion(Builder $query): Builder
    {
        return $query->onlyTrashed()
            ->where('deleted_at', '<', Carbon::now()->subDays(7));
    }

    /**
     * Create a duplicate of this note.
     */
    public function duplicate(): self
    {
        $duplicate = $this->replicate();
        $duplicate->title = $this->title ? $this->title . ' (Copy)' : null;
        $duplicate->is_pinned = false; // Don't pin the duplicate
        $duplicate->created_at = now();
        $duplicate->updated_at = now();
        $duplicate->save();

        return $duplicate;
    }

    /**
     * Set the note's color.
     */
    public function setColor(string $color): bool
    {
        // Validate hex color
        if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return false;
        }

        $this->color = $color;
        return $this->save();
    }

    /**
     * Add a label to the note.
     */
    public function addLabel(string $label): bool
    {
        $labels = $this->labels ?? [];

        if (!in_array($label, $labels)) {
            $labels[] = $label;
            $this->labels = $labels;
            return $this->save();
        }

        return true;
    }

    /**
     * Remove a label from the note.
     */
    public function removeLabel(string $label): bool
    {
        $labels = $this->labels ?? [];

        if (($key = array_search($label, $labels)) !== false) {
            unset($labels[$key]);
            $this->labels = array_values($labels); // Re-index array
            return $this->save();
        }

        return true;
    }

    /**
     * Check if the note has a specific label.
     */
    public function hasLabel(string $label): bool
    {
        return in_array($label, $this->labels ?? []);
    }

    /**
     * Boot method for model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically delete empty notes when updated
        static::saving(function ($note) {
            if ($note->isEmpty() && $note->exists) {
                $note->delete();
                return false; // Prevent saving
            }
        });

        // Set user_id automatically if authenticated
        static::creating(function ($note) {
            if (!$note->user_id && auth()->check()) {
                $note->user_id = auth()->id();
            }
        });
    }
}