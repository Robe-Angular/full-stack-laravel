<?php

namespace App\Http\Controllers;

use App\Post;
use App\Image;
use App\Posts_Language;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\JwtAuth;

class PostController extends Controller {

    public function __construct() {
        $this->middleware('api.auth', ['except' => [
            'index', 
            'postsByLanguage',
            'show', 
            'getImage',
            'getPostsByUser',
            'getPostsByCategory'
            ]]);
    }

    public function postsByLanguage($language) {
        $posts = Posts_Language::where('published',true)->where('language_symbol',$language)->with('post')->get();

        return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function postLanguage($id,Request $request, $language) {
        $post = Post::find($id);
        $user = $this->getIdentity($request);
        $is_admin = false;
        if(is_object($user)){
            $is_admin = $user->sub == 1;
        }
        
        if(is_object($post)){
            $post_with_category = null;
            if($is_admin){
                $post_with_category = $post->with('category')->with(
                    array('posts_language' => function($query) use ($language){ 
                        $query->where('posts_language.language_symbol', $language);
                    }))
                ->with(
                    array('category.categories_language' => function ($query) use ($language){
                        $query->where('categories_language.language_symbol',$language);
                    }))
                ->get();
            }else{
                $post_with_category = $post->with('category')->with(
                    array('posts_language' => function($query) use ($language){ 
                        $query->where('posts_language.language_symbol', $language);
                        $query->where('posts_language.published', true);
                    }))
                    ->with(
                    array('category.categories_language' => function ($query) use ($language){
                        $query->where('categories_language.language_symbol',$language);
                    }))
                ->get();
            }
            
            
            $image_id = $post->image;
            if($image_id){
                $image_description = Image::find($image_id)->description;
            }else{
                $image_description = 'no_main_yet';
            }
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post_with_category,
                
                'image_description' => $image_description,
                'image_id' => $image_id
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'posts' => 'La entrada no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        //Recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        $user = $this->getIdentity($request);
        
        $is_admin = $user->sub == 1;

        if (!empty($params_array) && $is_admin) {
            //Conseguir usuario identificado
            
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'category_id' => 'required'
            ]);
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'faltan datos'
                ];
            } else {
                //Guardar el artículo
                $post = new Post();
                $post->category_id = $params->category_id;                
                $post->save();
                
                $config_langs = config('static_arrays.languages');
                foreach ($config_langs as $lang) {
                    $post_language = new Posts_Language();
                    $post_language->post_id = $post->id;
                    $post_language->language_symbol = $lang;
                    $post_language->title_language = "";
                    $post_language->content_language = "";
                    $post_language->published = false;
                    $post_language->save();
                }
                
                
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ];
            }
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Envía los datos correctamente'
            ];
        }
        //Devolver la respuesta
        return response()->json($data, $data['code']);
    }

    public function updateLanguage($post_language_id,$post_id, Request $request) {
        //Conseguir usuario identificado
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        
        //Recoger los datos por Post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        
        $data = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Datos enviados incorrectamente',
            'params'=> $params_array
        ];
        
        if (!empty($params_array) && $is_admin) {
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required'
            ]);
            if ($validate->fails()) {
                $data['errors'] = $validate->errors();
                return response()->json($data, $data['code']);
            }
            //Eliminar lo aue no queremos actualizar
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);
            unset($params_array['category']);
            
            $post_language_update_array = array(
                'title_language' => $params_array['title'],
                'content_language' => $params_array['content']
            );
            
            $post_category_update_array = array(
                'category_id' => $params_array['category_id']
            );
            //Actualizar el registro en concreto
            
            $post_update = Post::find($post_id)->update($post_category_update_array);
            $post_language_update = Posts_Language::find($post_language_id)->update($post_language_update_array);

            if($post_update && !empty($post)){
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post_update,
                'post_language' => $post_language_update
            ];
            }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'El Post que intentas actualizar no existe'
            ];    
            }
        }
        //Devolver algo
        return response()->json($data, $data['code']);
    }
    
    public function destroy($id, Request $request){
        //Conseguir usuario identificado
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        //Conseguir el registro
        $post = Post::find($id);
        $concat_image_names = "";
        if(!empty($post) && $is_admin){
            
            $deleted_images = Image::where('post_id', $id)->get();
            
            $images_to_delete = Image::where('post_id', $id);
            
            foreach($deleted_images as $image){
                
                $file_name = $image->image_name;
                $concat_image_names .= $file_name;
                $isset = \Storage::disk('images')->exists($file_name);
                if($isset){
                    \Storage::disk('images')->delete($file_name);
                }
                
            }
            
            $images_to_delete->delete();
            
            //Borrarlo
            $post->delete();

            //Devolver algo
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post,
                'concat' => $concat_image_names
            ];
        } else{
            $data =[
                'code'  => 404,
                'status'  => 'error',
               'message'  => 'El Post que quieres borrar no existe'
        ];
        }
        return response()->json($data,$data['code']);
    }
    
    private function getIdentity($request){
        //Conseguir usuario identificado
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }
    
    public function upload(Request $request){
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        if(!$is_admin){
            $data = [
              'code'  => 400,
              'status'  => 'error',
              'message'  => 'Error al subir la imagen'              
                
            ];
            return response()->json($data,$data['code']);
        }
        
        //Recoger la imagen de la petición
        $image = $request->file('file0');        
        
        
        //Validar la imagen
        $validate = \Validator::make($request->all(),[
           'file0' => 'required|mimes:jpg,jpeg,png,gif'
        ]);
        //Guardar la imagen 
        if(!$image || $validate->fails()){
            $data = [
              'code'  => 400,
              'status'  => 'error',
              'message'  => 'Error al subir la imagen'              
                
            ];
        }else{
            $image_name = time().$image->getClientOriginalName();
            
            \Storage::disk('images')->put($image_name, \File::get($image));
            /*
            $new_image = new Image();
            $new_image->post_id = $post_id;
            
            $new_image->name = $image_name;
            $new_image->save();
             
             * 
             */
            $data = [
                'code' =>200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        //Devolver datos
        return response()->json($data, $data['code']);
    }
    
    
    
    
    
    public function getPostsByCategory($id,$language) {
        
        $posts = Post::where('category_id', $id)->with(
            array('posts_language' => function($query) use ($language){ 
                $query->where('posts_language.language_symbol', $language);
                $query->where('posts_language.published', true);
            }))
        
        ->get();
        //$posts = Post::all()->load('image');
        /*$posts = Post::with(['image' => function ($query) use ($id) {
            $query->where('category_id','=',$id);
        }])->get();*/
        
        /*
        $users = App\User::with(['posts' => function ($query) {
            $query->where('title', 'like', '%first%');
        }])->get();
         */
        
        
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
    
    public function getPostsByUser($id) {
        $posts = Post::where('user_id', $id)->where('published',true) ->load('image')->get();
        
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
    
    public function getPostsInAdminByLanguage(Request $request,$language) {
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $data = [
            'code'=>400,
            'status' => 'error',
            'message' => 'server error'
        ];
        if($is_admin){
            $posts = Post::all()->load(
                array('posts_language' => function($query) use ($language){ 
                    $query->where('posts_language.language_symbol', $language);
                }));
            $data = [
                'code' => 200,
                'status' => 'success',
                'posts' => $posts

            ];  
        }
        
        return response()->json($data,$data['code']);
        
        
    }
    
    public function setPublished($id,$value, Request $request){
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $data = [
            'code'=>400,
            'status' => 'error',
            'message' => 'server error'
        ];
        if($is_admin){
            $set_value = $value == 'true' ? true : false;
            $post = Post::find($id);
            $post_update = $post->update(['published'=>$set_value]);
            $data = [
                'code'=>200,
                'status' => 'success',
                'post_update' => $post_update,
                'value' => $value,
                'set_value' => $set_value,
                'post' => $post
            ];
        }
        
        return response()->json($data,$data['code']);
            
    }
}
