<?php

use App\Models\Category;
use App\Http\Resources\MessageResource;
use App\Models\ChatContact;
use App\Models\Group;
use App\Models\Message;
use App\Models\User;
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
        if (request()->has('group_id')) {

            $messages = Message::with('receiver', 'sender')->where('group_id', request('group_id'))->paginate($pageSize);
        
        } else {

            $messages = Message::with('receiver', 'sender')->where('channel_id', request('contact_user_id'))->latest()->orderBy('created_at', 'asc')->paginate($pageSize);
            
            $messages = $messages->reverse();
        }

        $messages = MessageResource::collection($messages);

        return response()->json(['data' => $messages]);
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
    
          
    
            Message::insert([
                'chat_contact_id' => $chat_id,
                'sender_id' => Auth::id(),
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

});
