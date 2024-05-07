<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id','name','avatar','last_seen_message'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function groupOwner()
    {
        return $this->belongsTo(User::class);
    }
}
