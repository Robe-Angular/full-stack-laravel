<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                        'name' => 'required'
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
                $category->name = $params_array['name'];
                if($is_admin){
                    $category->save();
                    $data = [
                        'code' => 200,
                        'status' => 'success',
                        'message' => $category
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
    public function destroy($id,$request){
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
            $category->delete();
            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $category
            ];
            
        }
        return response()->json($data,$data['code']);
    }
}
