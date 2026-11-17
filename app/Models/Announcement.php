<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['message'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name']);
    }
}
