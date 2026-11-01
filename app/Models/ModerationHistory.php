<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationHistory extends Model
{
    protected $fillable = ['moderator_id', 'action', 'reason'];

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
