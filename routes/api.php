<?php

use App\Models\Category;
use App\Http\Resources\MessageResource;
use App\Models\ChatContact;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('v1/user', function (Request $request) {
    return $request->user();
});

Route::get('/v1/chat/contacts-testing', function () {

    // $users = User::with('conversations','conversations.messages','conversations.chat_messages', 'messages.chat.owner', 'messages.chat.contact')->where('id',Auth::id())->first()->toArray();
    $user = User::with(['conversations', 'getCurrentUserGroup', 'getCurrentUserGroup.users', 'groups' => function ($query) {

        $query->with('users')
            ->where('user_id', request('user_id'));
    }])->where('id', request('user_id'))->first()->toArray();

    $user['conversations'] = array_merge($user['conversations'], $user['get_current_user_group']);
    $user['conversations'] = array_merge($user['conversations'], $user['groups']);

    foreach ($user['conversations'] as &$conversation) {

        (!empty($conversation['users'])) ? $conversation['is_group'] = true : $conversation['is_group'] = false;
    }
    // unsert
    // dd($user);
    // $user = User::withWhereHas('messages',function($query) use($contact_user_id) {
    //     $query->where('chat_contact_id',$contact_user_id);
    // })->get()->toArray();

    // $messages = Message::with('receiver', 'sender')->where('chat_contact_id', $contact_user_id)->get();

    // $messages = MessageResource::collection($messages);
    return response()->json([
        'data' => $user
    ]);
    // dd($users);
    // return view('welcome',['categories' => $categories]);
});


Route::post('/v2/send-message/chat-id/{chat_id}', function ($chat_id, Request $request) {

    if (request('delete_mode') == 'on') {
        Message::where('id', $request->id)->update([
            'message' => 'Message Deleted',
            'is_deleted' => 1
        ]);

        return;
    }

    if(request()->has('is_group') && request('is_group'))
    {
        if(request('edit_mode') == 'on')
        {

            Message::where('id', $request->id)->update([
                'group_id' => $request->group_id,
                'message' => $request->message,
                'sender_id' => $request->sender_id,
                'is_edited' => 1
            ]);

            return;
        }

        $message = Message::insert([
            // 'chat_contact_id' => $chat_id,
            'sender_id' => Auth::id(),
            // 'receiver_id' => $request->receiver_id,
            'group_id' => $request->group_id,
            'message' => $request->message,
            'created_at' => now(),
            'updated_at' => now()

        ]);

        Group::where('id',$request->group_id)->update([
            'last_seen_message' => $request->message,
        ]);

        return;

    }

    if(request()->has('is_group') && !request('is_group'))
    {

        if (request('edit_mode') == 'on') {
            Message::where('id', $request->id)->update([
                'message' => $request->message,
                'is_edited' => 1
            ]);

            return;
        }

        //file upload code lies here
        \DB::transaction(function() use($request) {

            if ($request->hasFile('file')) {
                $imagePath = $request->file('file')->store('avatars','public'); // Store the image in the 'avatars' directory
                // You may need to configure the storage disk and path according to your requirements
            }
            
            $group = Group::create([
                'sender_id' => $request->user_id,
                'receiver_id' => $request->receiver_id,
                'channel_id' => $request->channel_id,
                'message' => $request->message,
            ]);
    
            $insertGroupUsers = [];
            // print_r(request('users'));
            // dd(explode(',',request('users')));
            foreach(explode(',',request('users')) as $userId)
            {
                $insertGroupUsers[] = [
                    'group_id' => $group->id,
                    'user_id'  => $userId 
                ];
            }
    
            \DB::table('group_user')->insert($insertGroupUsers);

        });

        Message::insert([
            'chat_contact_id' => $chat_id,
            'sender_id' => $request->user_id,
            'receiver_id' => $request->receiver_id,
            'channel_id' => $request->channel_id,
            'message' => $request->message,
            'created_at' => now(),
            'updated_at' => now()

        ]);

        ChatContact::where('id', $chat_id)->update([
            'last_seen_message' => $request->message
        ]);

        return response()->json('Message Sent');
    }

});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/v1/chat/contacts', function () {

        // $users = User::with('conversations','conversations.messages','conversations.chat_messages', 'messages.chat.owner', 'messages.chat.contact')->where('id',Auth::id())->first()->toArray();
        $user = User::with(['conversations', 'getCurrentUserGroup', 'getCurrentUserGroup.users', 'groups' => function ($query) {

            $query->with('users')
                ->where('user_id', request()->user()->id);
        }])->where('id', request()->user()->id)->first()->toArray();

        foreach($user['get_current_user_group'] as &$current_user_group)
        {
            $current_user_group['avatar'] = url('storage/' . $current_user_group['avatar'] );
            // dd($user['get_current_user_group']);
        }

        $user['conversations'] = array_merge($user['conversations'], $user['get_current_user_group']);
        $user['conversations'] = array_merge($user['conversations'], $user['groups']);

        foreach ($user['conversations'] as &$conversation) {

            (!empty($conversation['users'])) ? $conversation['is_group'] = true : $conversation['is_group'] = false;
        }
        // Iterate over the conversations and assign a unique id
        foreach ($user['conversations'] as &$conversation) {
            $conversation['unique_id'] = uniqid(); // Generate a unique id
        }
        // dd($users);
        // $users = User::withWhereHas('messages',function($query) use($contact_user_id) {
        //     $query->where('chat_contact_id',$contact_user_id);
        // })->get()->toArray();

        // $messages = Message::with('receiver', 'sender')->where('chat_contact_id', $contact_user_id)->get();

        // $messages = MessageResource::collection($messages);
        return response()->json([
            'data' => $user
        ]);
        // dd($users);
        // return view('welcome',['categories' => $categories]);
    });


    Route::get('/v1/chat', function () {

        $page = request()->has('page') ? request('page') : 1;
        $pageSize = 6; // Adjust as per your requirement
    
        // $users = User::with('conversations.messages','conversations.chat_messages', 'messages.chat.owner', 'messages.chat.contact')->first()->toArray();

        // $users = User::withWhereHas('messages',function($query) use($contact_user_id) {
        //     $query->where('chat_contact_id',$contact_user_id);
        // })->get()->toArray();
        $messages = [];
        $unreadMessagesCount = 0;
        $maxId = 0;
        if (request()->has('group_id') && !is_null(request('group_id'))) {

            $messages = Message::with('receiver', 'sender')->where('group_id', request('group_id'))->orderBy('id', 'desc')->paginate($pageSize);
        
            $messages = $messages->reverse();

            // $lastSeenMessageId = Group::where('group_id',request('group_id'))->first()->last_seen_message_id;
            // $unreadMessagesCount = Message::where('group_id', request('group_id'))
            // ->where('id', '>', $lastSeenMessageId)
            // ->count() ?? 0;

        } elseif(!is_null(request('contact_user_id'))) {

            
            $messages = Message::with('receiver', 'sender','reply')->where('channel_id', request('contact_user_id'))->orderBy('id', 'desc')->paginate($pageSize);
            
            $messages = $messages->reverse();

            // $lastSeenMessageId = ChatContact::where('channel_id',request('contact_user_id'))->first()->last_seen_message_id;
            // $unreadMessagesCount = Message::where('channel_id', request('contact_user_id'))
            // ->where('id', '>', $lastSeenMessageId)
            // ->count() ?? 0;
            $maxId = $messages->max()->id ?? 0;
        }

        $groupedMessages = MessageResource::collection($messages);

        return response()->json(['data' => $groupedMessages, 'max_id' => $maxId ,'unread_messages_count' => 0]);
        // dd($users);
        // return view('welcome',['categories' => $categories]);
    });

    Route::get('/v1/user-contacts', function () {

        $users = User::where('id', '!=', Auth::id())->get()->toArray();

        return response()->json(['data' => $users]);
    });

    Route::post('/v1/add-user-contact', function () {

        if (request('add_user_contact_id') != '') {
            $rand = rand(111, 999999);
            ChatContact::insert([[
                'user_id' => Auth::id(),
                'contact_user_id' => request('add_user_contact_id'),
                'channel_id' => $rand
            ], [
                'user_id' => request('add_user_contact_id'),
                'contact_id' => Auth::id(),
                'channel_id' => $rand
            ]]);

            return response()->json('Contact Added');
        }
    });

    Route::get('/categories', function () {

        $sort = request('sort.0.sort');
        $sortColumn = request('sort.0.field');
        // dd($sortColumn);
        $categories = \App\Models\Category::with('products');

        if (request()->has('sort')) {
            $categories->orderBy($sortColumn, $sort);
        }

        if (request()->has('q')) {
            $q = '%' . request('q.0') . '%';

            $categories->where('name', 'LIKE', $q);
        }

        $categories = $categories->paginate(request()->input('perPage', 10));

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    });

    Route::post('/categories', function () {

        $request = request();

        $request->validate([
            'name' => ['required']
        ]);

        try {
            Category::create($request->all());

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Category created succesfully !',
            ]);
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $th->getMessage(),
            ]);
        }
    });

    Route::put('/categories/{id}', function ($id) {
        $request = request();

        $request->validate([
            'name' => ['required']
        ]);

        try {
            $category = Category::findOrFail($id);

            $category->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category updated successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $th->getMessage(),
            ]);
        }
    });

    // Show a category
    Route::get('/categories/{id}', function ($id) {
        try {
            $category = Category::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category retrieved successfully!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $th->getMessage(),
            ]);
        }
    });


    Route::post('/v1/send-message/chat-id/{chat_id}', function ($chat_id, Request $request) {

        if (request('delete_mode') == 'on') {
            Message::where('id', $request->id)->update([
                'message' => 'Message Deleted',
                'is_deleted' => 1
            ]);

            return;
        }

        if(request()->has('is_group') && request('is_group'))
        {
            if(request('edit_mode') == 'on')
            {

                Message::where('id', $request->id)->update([
                    'group_id' => $request->group_id,
                    'message' => $request->message,
                    'sender_id' => $request->sender_id,
                    'is_edited' => 1
                ]);
    
                return;
            }

            $message = Message::insert([
                // 'chat_contact_id' => $chat_id,
                'sender_id' => Auth::id(),
                // 'receiver_id' => $request->receiver_id,
                'group_id' => $request->group_id,
                'message' => $request->message,
                'created_at' => now(),
                'updated_at' => now(),
                'reply_id' => $request->reply_id ?? null,
    
            ]);

            Group::where('id',$request->group_id)->update([
                'last_seen_message' => $request->message,
            ]);

            return;
    
        }

        if(request()->has('is_group') && !request('is_group'))
        {

            if (request('edit_mode') == 'on') {
                Message::where('id', $request->id)->update([
                    'message' => $request->message,
                    'is_edited' => 1
                ]);
    
                return;
            }
            
          
    
            $message = Message::insert([
                'chat_contact_id' => $chat_id,
                'sender_id' => Auth::id(),
                'receiver_id' => $request->receiver_id,
                'channel_id' => $request->channel_id,
                'message' => $request->message,
                'reply_id' => $request->reply_id ?? null,
                'created_at' => now(),
                'updated_at' => now()
    
            ]);
    
            ChatContact::where('id', $chat_id)->update([
                'last_seen_message' => $request->message,
                'last_seen_message_id' => Message::latest()->first()->id ?? null,
                'reciever_id' => $request->receiver_id,
            ]);
    
            return response()->json([
                'message' => 'Message Sent',
                'data' => new MessageResource(Message::latest()->first())
            ]);
        }

    });

    Route::post('/v1/group/create', function (Request $request) {
        
    
        \DB::transaction(function() use($request) {

            if ($request->hasFile('file')) {
                $imagePath = $request->file('file')->store('avatars','public'); // Store the image in the 'avatars' directory
                // You may need to configure the storage disk and path according to your requirements
            }
            
            $group = Group::create([
                'admin_id' => Auth::id(),
                'name' => request('name'),
                'avatar' => $imagePath ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
    
            $insertGroupUsers = [];
            // print_r(request('users'));
            // dd(explode(',',request('users')));
            foreach(explode(',',request('users')) as $userId)
            {
                $insertGroupUsers[] = [
                    'group_id' => $group->id,
                    'user_id'  => $userId 
                ];
            }
    
            \DB::table('group_user')->insert($insertGroupUsers);

        });

    
    });

    Route::post('/v1/markAsSeen', function (Request $request) {

        $receiver_id = request('receiver_id');
        $last_seen_message_id = $request->input('last_seen_message_id');

        $chat_id = $request->input('chat_id');

        $chatContact = ChatContact::where('id', $chat_id)->first();

        if ($chatContact) {

            $chatContact->update([
                'last_seen_message_id' => $last_seen_message_id,
                'reciever_id' => $receiver_id
            ]);

            return response()->json(['message' => 'Message marked as seen.']);
        }

        return response()->json(['message' => 'Chat contact not found.'], 404);
    });

    Route::get('/v1/check/new-messages', function (Request $request) {

        $receiver_id = request('receiver_id');
        $last_seen_message_id = $request->input('last_seen_message_id');

        $chat_id = $request->input('chat_id');

        $chatContact = ChatContact::where('id', $chat_id)->first();

              
        $maxId = Message::where('channel_id', request('chat_id'))
        ->where('receiver_id',$receiver_id)
        ->selectRaw('MAX(id) as max_id')
        ->value('max_id');
        if($maxId != $last_seen_message_id)
        {

            $count = Message::where('channel_id', request('chat_id'))
                ->where('receiver_id', $receiver_id)
                ->whereBetween('id', [$last_seen_message_id, $maxId])
                ->count();
                 
            $count = $count - 1;

        }else{

            $count = 0;
        }

        
        return response()->json(['message' => 'Message marked as seen.', 'data' => ['max_id' => $maxId,'new_messages_count' => $count]]);

    });
});
