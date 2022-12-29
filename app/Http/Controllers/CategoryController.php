<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Image;
use App\Category_Language;
use App\Helpers\JwtAuth;
use App\Category;


class CategoryController extends Controller {
    
    
    public function __construct() {
        $this->middleware('api.auth', ['except' => ['index', 'show']]);
    
        
    }
    
    

    public function index() {
        $categories = Category::all();
        
        return response()->json([
                    'code' => 200,
                        'status' => 'success',
                    'categories' => $categories
        ]);
    }

    public function show($id) {
        $category = Category::find($id);
        
        
        if (is_object($category)) {
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $category
            ];
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La categoría no existe'
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        // Recoger los datos por Post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        
        $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado la categoría'
                    
        ];
        
        if (!empty($params_array)) {
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'description' => 'required'
            ]);

            //Guardar la categoría
            if ($validate->fails()) {
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado la categoría'
                    
                ];
            } else {
                $category = new Category();
                        
                $category->description = $params_array['description'];
                
                if($is_admin){
                    $category->save();
                    
                    
                    
                    $data = [
                        'code' => 200,
                        'status' => 'success',
                        'category' => $category                    
                    ];
                }
                
            }
        }else{
            $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No has enviado ninguna categoría',
                    'json' => $json
                ];
        }
        //Devolver el resultado

        return response()->json($data, $data['code']);
    }
    
    public function update($id, Request $request) {
        //Recoger los datos que legan por Post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        $user = $this->getIdentity($request);
        
        $is_admin = $user->sub == 1;
        
        $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Server error'
        ];
        
        
        if(!empty($params_array)){
        //Validar los datos
        $validate = \Validator::make($params_array,[
           'name' => 'required'
        ]);
        //Quitar lo que no quiero actualizar
        unset($params_array['id']);
        unset($params_array['created_at']);
        if($is_admin){
            //Actualizar el registro(categoría)
            $category = Category::where('id', $id)->update($params_array);
            $data = [
                    'code' => 200,
                    'status' => 'success',
                    'category' => $params_array
            ];


            }

        }
        
        //Devolver respuesta
        return response()->json($data, $data['code']);
    }
    
    private function getIdentity($request){
        //Conseguir usuario identificado
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }
    
    public function destroy($id,Request $request){
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $data = [
            'code'  => 400,
            'status' => 'error',
            'message' => 'Server error'
        ];
        if($is_admin){
            $category = new Category();
            $category = Category::find($id);
            $posts_to_delete = Post::where('category_id',$id)->get();
            $concat_img_file = '';
            
            foreach($posts_to_delete as $post){
                
                $deleted_images = Image::where('post_id', $post->id)->get();
                $images_to_delete = Image::where('post_id', $post->id);
                
                foreach($deleted_images as $image){
                    $file_name = $image->image_name;
                    $concat_img_file .= $file_name;
                    $isset = \Storage::disk('images')->exists($file_name);
                    if($isset){
                        \Storage::disk('images')->delete($file_name);
                    }
                }

                $images_to_delete->delete();
                $post->delete();
            }
            
            
            $category->delete();
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $category,
                'concat' => $concat_img_file
            ];
            
        }
        return response()->json($data,$data['code']);
    }
}
