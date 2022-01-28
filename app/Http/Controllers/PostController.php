<?php

namespace App\Http\Controllers;

use App\Post;
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
        $post = Post::find($id)->load('category')
                    ->load('user');

        if (is_object($post)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
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

        if (!empty($params_array)) {
            //Conseguir usuario identificado
            $user = $this->getIdentity($request);
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'title' => 'required',
                        'content' => 'required',
                        'category_id' => 'required',
                        'image' => 'required'
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
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
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
        
        //Recoger los datos por Post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        $data = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Datos enviados incorrectamente'
        ];
        if (!empty($params_array)) {
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
            
            $post_update = Post::where('id', $id)
                    ->where('user_id', $user->sub)
                    ->update($params_array);
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
        //Conseguir el registro
        $post = Post::where('id', $id)
                ->where('user_id', $user->sub)
                ->first();
        
        if(!empty($post)){
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
            
            $data = [
                'code' =>200,
                'status' => 'success',
                'image' => $image_name
            ];
        }
        //Devolver datos
        return response()->json($data, $data['code']);
    }
    
    public function getImage($filename){
        //Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);
        
        if($isset){
        //Conseguir la imagen
        $file = \Storage::disk('images')->get($filename);
        //Devolver la imagen
        return new Response($file, 200);
        }else{
            $data = [
                'code'  => 404,
                'status' => 'error',
                'message' => 'la imagen no existe'
            ];
        }
        //Mostrar error
        return response()->json($data,$data['code']);
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
