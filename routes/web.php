<?php

use App\Models\Guest;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});
Route::get("fixbd", function () {
   $guests=Guest::all();
   foreach($guests as $guest){
         $guest->phone=preg_replace('/^8/', '+7', $guest->phone);
         $guest->save();
   }
});


Route::get("/discount/{hwid}", [\App\Http\Controllers\GuestKeychainController::class, 'get'])
    ->name('guest.discount');

Route::middleware('auth')->group(function () {
    Route::get('/make_keychain_first/{guest}', [\App\Http\Controllers\GuestKeychainController::class, 'make_keychain_first'])
        ->name('keychain.make.first');

    Route::get('/make_keychain_second/{guest}/{point}', [\App\Http\Controllers\GuestKeychainController::class, 'make_keychain_second'])
        ->name('keychain.make.second');
    Route::get("/keychain_status/{keychain_pending}", [\App\Http\Controllers\GuestKeychainController::class, 'get_pending_status']);
});

// А эти два лучше кинуть в api.php или добавить в исключения CSRF:
Route::get('/keychain_controller/get_pending/{point}', [\App\Http\Controllers\GuestKeychainController::class, 'check_pending']);
Route::post('/keychain_controller/set_status/{point}', [\App\Http\Controllers\GuestKeychainController::class, 'set_status']);

Route::get("/testgit", function() {
    return "OKe";
});