<?php

namespace App\Http\Controllers;

use App\Post;
use App\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Helpers\JwtAuth;

class PostController extends Controller {

    public function __construct() {
        $this->middleware('api.auth', ['except' => [
            'index', 
            'show', 
            'getImage',
            'getPostsByUser',
            'getPostsByCategory'
            ]]);
    }

    public function index() {
        $posts = Post::where('published',true)->with('category')->with('image')->get();

        return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function show($id,Request $request) {
        $post = Post::find($id);
        $post_is_published = $post->published == true;
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        
        if (is_object($post) && ($post_is_published || $is_admin)) {
            $post_with_category = $post->load('category');
            $image_id = $post->image;
            if($image_id){
                $image_description = Image::find($image_id)->description;
            }else{
                $image_description = 'no_main_yet';
            }
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post,
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
                        'title' => 'required',
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
                $post->title = $params->title;
                $post->content = "";
                $post->published = false;
                $post->save();
                
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

    public function update($id, Request $request) {
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

            //Actualizar el registro en concreto
            
            $post_update = Post::find($id)->update($params_array);
            $post = Post::find($id);

            if($post_update && !empty($post)){
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post,
                'changes' => $params_array
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
    
    
    
    
    
    public function getPostsByCategory($id) {
        $posts = Post::where('category_id', $id)->where('published',true)->with('image')->get();
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
    
    public function getPostsInAdmin(Request $request) {
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $data = [
            'code'=>400,
            'status' => 'error',
            'message' => 'server error'
        ];
        if($is_admin){
            $posts = Post::all()->load('image');
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
