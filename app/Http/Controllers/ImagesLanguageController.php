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
}
