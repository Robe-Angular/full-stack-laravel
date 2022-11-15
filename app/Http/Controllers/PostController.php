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
        $posts = Post::all()->load('category');

        return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'posts' => $posts
                        ], 200);
    }

    public function show($id) {
        $post = Post::find($id)->load('category');
        $image_id = $post->image;
        if($image_id){
            $image_description = Image::find($image_id)->description;
        }else{
            $image_description = 'no_main_yet';
        }
        
        
                
        if (is_object($post)) {
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
                        'content' => 'required',
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
                $post->content = $params->content;
                $post->save();
                
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'message' => $post
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
            'message' => 'Datos enviados incorrectamente'
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
        
        if(!empty($post) && $is_admin){
            
            $deleted_images = Images::where('post_id', $id);
            foreach($deleted_images as $image){
                $file_name = $image->image_name;
                \Storage::disk('images')->delete($file_name);
            }
            
            $deleted_images->delete();
            
            //Borrarlo
            $post->delete();

            //Devolver algo
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
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
        $posts = Post::where('category_id', $id)->get();
        
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
    
    public function getPostsByUser($id) {
        $posts = Post::where('user_id', $id)->get();
        
        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
}
