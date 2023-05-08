<?php

namespace App\Http\Controllers;

use App\Images_Language;
use Illuminate\Http\Request;

use App\Helpers\JwtAuth;

class ImagesLanguageController extends Controller
{
    private function getIdentity($request){
        //Conseguir usuario identificado
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }
    
    public function submitImageLanguage($image_id ,Request $request){
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        
        if($is_admin){
            $json = $request->input('json', null);
            $params = json_decode($json);
            $params_array = json_decode($json, true);
            
            $image_language = new Images_Language();
            $image_language->image_id = $image_id;
            $image_language->language_symbol = $params->language_symbol;
            $image_language->description_language = $params->description_language;
            $image_language->save();
            
            $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $image_language
            ];
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'admin not logged'
            ];
        }
        return response()->json($data, $data['code']);
    }
    
    public function imagesLanguageOnImage(Request $request,$image_id,$language){
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'admin not logged'
        ];
        if($is_admin){
            $images_language = Images_Language::where('image_id',$image_id)
                ->where('language_symbol',$language)
            ->get();
            $data = [
                'code' => 200,
                'images_language'=>$images_language
            ];
        }
        return response()->json($data, $data['code']);
    }
    
    public function deleteImageLanguage(Request $request,$image_language_id){
        $user = $this->getIdentity($request);
        $is_admin = $user->sub == 1;
        $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'admin not logged'
        ];
        if($is_admin){
            $image_language_deleted = Images_Language::find($image_language_id)->delete();
            $data = [
                'code' => 200,
                'image_language_deleted'=>$image_language_deleted
            ];
        }
        return response()->json($data, $data['code']);
    }
}
