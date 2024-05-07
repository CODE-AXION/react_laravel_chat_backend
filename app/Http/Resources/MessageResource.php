<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // dd($this);
        return [
            'id' => $this->id,
            'chat_contact_id' => $this->chat_contact_id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'group_id' => $this->group_id,
            'message' => $this->message,
            'is_edited' => ($this->is_edited == 1) ? true : false,
            'is_deleted' => ($this->is_deleted == 1) ? true : false,
            'created_at' => Carbon::parse($this->created_at)->format('H : i'),
            'updated_at' => $this->updated_at,
            'isSender' => $this->sender_id == request('sender_id'),
            'receiver' => $this->receiver,
            'sender' => $this->sender,
        ];
    }

}
