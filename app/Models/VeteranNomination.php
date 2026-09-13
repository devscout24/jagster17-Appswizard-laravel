<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeteranNomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nominator_name',
        'nominator_email',
        'nominator_phone',
        'nominee_name',
        'nominee_branch',
        'nominee_city',
        'nominee_state',
        'project_needed',
        'story_details',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
