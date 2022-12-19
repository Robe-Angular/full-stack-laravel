<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Categories_Language;

class CategoriesLanguageController extends Controller
{
    public function getCategoriesLanguageFromOne($category_id){
        $categories = Categories_Language::where('category_id',$category_id)->get();
        return response()->json([
                    'code' => 200,
                    'status' => 'success',
                    'categories' => $categories
        ], 200);
    }
    
    public function saveCategoryLanguage($category_id, Request $request){
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        $user = $this->getIdentity($request);
        
        $is_admin = $user->sub == 1;

        if (!empty($params_array) && $is_admin) {
            //Conseguir usuario identificado}
            $category_language = new Categories_Language();
            $category_language->category_id = $category_id;
            $category_language->name_language = $params->name_language;
            $category_language->language_symbol = $params->language_symbol;
            $category_language->save();
            $data = [
                    'code' => 200,
                    'status' => 'success',
                    'category_language' => $category_language
            ];
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Data not sended correctly'
            ];
        }
        return response()->json($data, $data['code']);
    }
    
    public function deleteCategoryLanguage($category_language_id, Request $request){
        $json = $request->input('json', null);
        $user = $this->getIdentity($request);
        
        $is_admin = $user->sub == 1;

        if ($is_admin) {
            //Conseguir usuario identificado}
            $category_language = Categories_Language::find($category_language_id);
            $category_language->delete();
            $data = [
                    'code' => 200,
                    'status' => 'success',
                    'category_language' => $category_language
            ];
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Data not sended correctly'
            ];
        }
        return response()->json($data, $data['code']);
    }
    
    private function getIdentity($request){
        //Conseguir usuario identificado
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }
}
