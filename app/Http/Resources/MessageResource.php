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
        // if (isset($this->resource['date'])) {
        //     return [
        //         'date' => $this->resource['date'],
        //     ];
        // }

        $createdAt = Carbon::parse($this->created_at);
        if ($createdAt->isToday()) {
            // $formattedDate = 'Today';
            $formattedDate = $createdAt->format('l');
        } elseif ($createdAt->isYesterday()) {
            // $formattedDate = 'Yesterday';
            $formattedDate = $createdAt->format('l');
        } elseif ($createdAt->diffInDays(Carbon::now()) <= 7) {
            // Within the last 7 days, display the day of the week
            $formattedDate = $createdAt->format('l');
        } else {
            // Otherwise, format the date as "j F Y"
            $formattedDate = $createdAt->format('j F Y');
        }

        return [
            'id' => $this->id ?? '',
            'chat_contact_id' => $this->chat_contact_id ?? '',
            'sender_id' => $this->sender_id ?? '',
            'receiver_id' => $this->receiver_id ?? '',
            'group_id' => $this->group_id ?? '',
            'message' => $this->message ?? '',
            'is_edited' => (($this->is_edited ?? '') == 1) ? true : false,
            'is_deleted' => (($this->is_deleted ?? '') == 1) ? true : false,
            'created_at' => $formattedDate ?? '',
            'formatted_created_at' => Carbon::parse(($this->created_at ?? ''))->format('H : i') ?? '',
            'updated_at' => $this->updated_at ?? '',
            'isSender' => ($this->sender_id ?? '') == request('sender_id') ,
            'receiver' => $this->receiver ?? '',
            'sender' => $this->sender ?? '',
            'reply' => $this->reply ?? '',
        ];
    }

}
