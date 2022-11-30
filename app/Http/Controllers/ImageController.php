<?php

namespace App\Http\Controllers;

use App\Image;
use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use Illuminate\Http\Response;
use App\Post;


class ImageController extends Controller
{
    public function __construct() {
        $this->middleware('api.auth', ['except' => [
            'getImage'
            
            ]]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function show(Image $image)
    {
        //
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function edit($image, Request $request)
    {
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        //
        if($is_admin){
            $make_main_image = Image::find($image);
            $post = $make_main_image->post();
            
            $post_update = $post->update(['image'=>$image]);


            if($post_update && !empty($post_update)){
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post_updated' => $post_update,
                    'image' => $image
                    
                ];
            }else{
                $data = [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'El Post que intentas actualizar no existe',
                    'post' => $post_id
                ];    
            }
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function update($id,Request $request)
    {
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        
        $data = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Datos enviados incorrectamente'
        ];
        
        if (!empty($params_array) && $is_admin) {
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'description' => 'required'
            ]);
            if ($validate->fails()) {
                $data['errors'] = $validate->errors();
                return response()->json($data, $data['code']);
            }
            //Eliminar lo aue no queremos actualizar
            unset($params_array['id']);
            unset($params_array['post_id']);
            unset($params_array['image_name']);
            unset($params_array['created_at']);
            unset($params_array['updated_at']);
            $image_update = Image::where('id', $id)
                    ->update($params_array);
            $image = Image::find($id);

            if($image_update && !empty($image)){
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'image' => $image,
                    'changes' => $params_array
                ];
            }
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'Server error'
            ];    
        }
        return response()->json($data, $data['code']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Image  $image
     * @return \Illuminate\Http\Response
     */
    public function destroy($image,Request $request)
    {
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $data = [
            'code'  => 404,
            'status' => 'error',
            'message' => 'server Error'
        ];
        if($is_admin){
            $posts = Post::where('image',$image);
            
            $posts_updated = $posts->update(['image'=>NULL]);
            
            $image_to_delete = Image::find($image);
            $file_name = $image_to_delete->image_name;
            $isset = \Storage::disk('images')->exists($file_name);            
            $image_to_delete->delete();
            if($isset){
                \Storage::disk('images')->delete($file_name);
                
                $data = [
                    'code'  => 200,
                    'status' => 'success',
                    'deleted' => $file_name
                ];
            }
            
            
        }
        return response()->json($data, $data['code']);
        
    }
    
    public function getImage($file_description){
        
        $images = Image::where('description',$file_description)->get();
        if(count($images) > 1){
            $data = [
                'code'  => 404,
                'status' => 'error',
                'message' => 'images description duplicated'
            ];
            return response()->json($data,$data['code']);
        }
        $filename = "";
        
        foreach($images as $image){
            
            $filename = $image->image_name;
            
        }
        
        //Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);
        
        if($isset){
        //Conseguir la imagen
        $file = \Storage::disk('images')->get($filename);
        $type = \Storage::disk('images')->mimeType($filename);
        //Devolver la imagen

        return \Illuminate\Support\Facades\Response::make($file, 200)
                ->header('Content-Type',$type);
        }else{
            $data = [
                'code'  => 404,
                'status' => 'error',
                'message' => 'la imagen no existe',
                'filename' => $filename
            ];
        }
        //Mostrar error
        return response()->json($data,$data['code']);
    }
    
    private function getIdentity($request){
        //Conseguir usuario identificado
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }
    public function saveImage(Request $request){
        $user = $this->getIdentity($request);
        $json = $request->input('json', null);
        $params = json_decode($json);
        $is_admin = $user->sub == 1;
        $data = [
              'code'  => 400,
              'status'  => 'error',
              'message'  => 'Error al subir la imagen'              

        ];
        if(!$is_admin){
               return response()->json($data,$data['code']);
        }   
        
        $params_array = json_decode($json, true);
        
        if (!empty($params_array)) {
            $new_image = new Image();
            $new_image->image_name = $params->image_name;
            $new_image->post_id = $params->post_id;
            $new_image->save();
            $data = [
                    'code' => 200,
                    'status' => 'success',
                    'image' => $new_image
            ];
        }
        return response()->json($data,$data['code']);
    }
    
    public function getImagesByPost($post_id,Request $request){
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $images_on_posts = Image::where('post_id',$post_id)->get();
        
        $data = [
              'code'  => 400,
              'status'  => 'error',
              'message'  => 'Error al subir la imagen'              

        ];
        if($is_admin){
            $data = [
                    'code' => 200,
                    'status' => 'success',
                    'images' => $images_on_posts
            ];
        }
        
        return response()->json($data,$data['code']);
    }
}
