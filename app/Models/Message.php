<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['chat_contact_id','sender_id','channel_id','receiver_id','message','is_edited','is_deleted'];

    public function sender()
    {
        return $this->belongsTo(User::class,'sender_id');
    }

    
    public function receiver()
    {
        return $this->belongsTo(User::class,'receiver_id');
    }

    
    public function chat()
    {
        return $this->belongsTo(ChatContact::class,'chat_contact_id');
    }

    public function reply()
    {
        return $this->belongsTo(Message::class,'reply_id');
    }

    
}
