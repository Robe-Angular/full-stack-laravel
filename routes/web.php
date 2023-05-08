<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Cargando clases
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\Cors;

//Rutas de prueba
/*
Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/pruebas/{nombre?}', function($nombre = null){
    $texto = '<h2>Texto desde una ruta</h2>';
    $texto .= "Nombre: ".$nombre;
    
    return view('pruebas', array(
        'texto' => $texto
    ));
});

Route::get('/animaal', function(){
    
    return view('pruebas.index', array(
    ));
});

Route::get('/animales', 'PruebasController@index');

Route::get('/testOrm', 'PruebasController@testOrm');

//Rutas de API
    /*Métodos HTTP comunes
     * GET: Conseguir datos o recursos
     * Post: Guardar datos o recursos o hacer lógica desde un formulario
     * PUT: Actualizar recursos o datos
     * DELETE: Eliminar datos o recursos
     */
/*
    //Rutas de prueba
    Route::get('/usuario/pruebas','UserController@pruebas');
    Route::get('/categoria/pruebas','CategoryController@pruebas');
    Route::get('/entrada/pruebas','PostController@pruebas');
  */  
   //Route::group(['middleware' => ['cors']], function () {
        //Rutas del controlador de usuario
        //Route::post('/api/register', 'UserController@register');
        Route::post('/api/login', 'UserController@login');
        //Route::put('/api/user/update', 'UserController@update');
        //Route::post('/api/user/upload','UserController@upload')->middleware(ApiAuthMiddleware::class);
        //Route::get('/api/user/avatar/{filename}', 'UserController@getImage');
        //Route::get('/api/user/detail/{id}', 'UserController@detail');

        //Rutas del controlador categorías
        Route::resource('/api/category', 'CategoryController');
        


        //Rutas del controlador entradas
        Route::resource('/api/post', 'PostController');
        
        Route::get('/api/post-language/{id}','PostController@postLanguage');
        Route::get('/api/post-lang/{language}','PostController@postsByLanguage');
        Route::get('/api/posts-language-on-post/{post_id}','PostController@postsLanguageOnPost');
        Route::get('/api/posts-language-on-post-admin/{post_id}','PostController@postsLanguageOnPostAdmin');
        Route::put('/api/post/update/{post_language_id}/{post_id}','PostController@updateLanguage');
        Route::post('/api/post/upload','PostController@upload');
        
        
        Route::get('/api/post/category/{id}/{language}', 'PostController@getPostsByCategory');
        Route::get('/api/post/user/{id}', 'PostController@getPostsByUser');
        
        Route::get('/api/list-post-admin/{language}', 'PostController@getPostsInAdminByLanguage');
        
        Route::get('/api/post/publish/{id}/{value}', 'PostController@setPublished');

        //imageController
        Route::resource('/api/image', 'ImageController');
        Route::get('/api/post/image/{file_description}', 'ImageController@getImage');
        Route::post('/api/image/save', 'ImageController@saveImage');
        Route::get('api/images-by-post/{post_id}','ImageController@getImagesByPost');
        Route::put('api/save-image-language/{image_id}','ImagesLanguageController@submitImageLanguage');
        Route::get('/api/post/image-language/{description_language}', 'ImageController@getImageFromDescriptionLanguage');
        Route::get('/api/images-language-on-image/{image_id}/{language}', 'ImagesLanguageController@imagesLanguageOnImage');
        Route::delete('/api/delete-image-language/{image_language_id}', 'ImagesLanguageController@deleteImageLanguage');
        
        
        
        
        //CategoriesLanguage
        Route::get('api/getCategoriesLanguage/{lang}','CategoriesLanguageController@getCategoriesForLanguage');
        Route::get('api/getCategoriesLanguageFromOne/{category_id}','CategoriesLanguageController@getCategoriesLanguageFromOne');
        Route::put('api/categoryLanguage/{category_id}','CategoriesLanguageController@saveCategoryLanguage');
        Route::delete('api/categoryLanguage/{category_language_id}','CategoriesLanguageController@deleteCategoryLanguage');
    //});