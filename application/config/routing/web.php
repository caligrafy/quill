<?php
/**
 * web.php
 * Web Routes are defined in this file
 * @author Dory A.Azar
 * @copyright 2019
 * @version 1.0
 *
*/

try {
    
    // API ACCESS - add comments if you don't want to allow API access on routes
    Auth::activateAPI();
    
    // AUTHENTICATION - Remove comment if you have an authentication implemented
    //Auth::authentication('User', 'users');
    
    
    // ROUTE DEFINITION: get, post, put and delete
	Route::get('/', function(){ echo 'Hello world';});
    Route::get('/view', function() { return view('default/index'); });
    
    Route::get('/mail', 'MailController');
    // Auth Routes - Uncomment only if AUTHENTICATION activated above
    //Route::get('/home', 'AuthController');
    //Route::get('/login', function() { return view('Auth/login'); });
    //Route::get('/logout', 'AuthController@logout');
    //Route::get('/register', function() { return view('Auth/register'); });
    //Route::post('/login', 'AuthController@login');
    //Route::post('/register', 'AuthController@register');

    
    // MUST NOT BE REMOVED - Routes need to be run after being defined 
    Route::run(); 
}
catch (Exception $e) {
    
    // Error handling 
    echo $e->getMessage();
}



