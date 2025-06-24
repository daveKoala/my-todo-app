<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Todo extends Model
{
    /** @use HasFactory<\Database\Factories\TodoFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'task_name',
        'done_on',
        'due_date',
        'category_id',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'done_on' => 'date',
        'due_date' => 'date',
    ];

    public function category() 
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Check if the todo is completed.
     */
    public function isCompleted(): bool
    {
        return !is_null($this->done_on);
    }

    /**
     * Check if the todo is overdue.
     */
    public function isOverdue(): bool
    {
        return !$this->isCompleted() && $this->due_date < Carbon::today();
    }

    /**
     * Scope to get only completed todos.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('done_on');
    }

    /**
     * Scope to get only pending todos.
     */
    public function scopePending($query)
    {
        return $query->whereNull('done_on');
    }
}