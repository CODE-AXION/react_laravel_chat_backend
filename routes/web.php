<?php

use App\Http\Resources\MessageResource;
use CodeAxion\NestifyX\Models\CategoryTree;
use Illuminate\Support\Facades\Route;

use App\Models\Category;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    
    $categories = Category::with('products')->get();
    return view('welcome',['categories' => $categories]);
});




require __DIR__.'/auth.php';