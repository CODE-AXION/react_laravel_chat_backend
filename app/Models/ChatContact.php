<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ChatContact extends Pivot
{
    use HasFactory;

    protected $table = "chat_contacts";

    protected $fillable = ['user_id','message','contact_user_id','last_seen_message_id','reciever_id'];

    public function owner()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    
    public function contact()
    {
        return $this->belongsTo(User::class,'contact_user_id');
    }

    
   
    
}
