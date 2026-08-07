<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quest extends Model
{
    use HasFactory;

    /**
     * The stat a quest can train. "intelligence" is spelled out (not "int")
     * to avoid colliding with the PHP type name.
     */
    public const STATS = ['str', 'agi', 'per', 'vit', 'intelligence'];

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progress(): HasOne
    {
        return $this->hasOne(Progress::class);
    }
}
