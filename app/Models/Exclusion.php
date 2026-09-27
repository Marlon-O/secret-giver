<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exclusion extends Model
{
    protected $fillable = ['giver_id', 'receiver_id'];

    public function giver(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'giver_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'receiver_id');
    }
}
