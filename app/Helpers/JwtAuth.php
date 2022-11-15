<?php
namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class JwtAuth{
    
    public $key;
    
    public function __construct() {
        $this->key = env('SECRET_KEY');
    }
    /*
    public function signup($email, $password, $getToken = null){
    
    //Buscar si existe el usuario con las credenciales
    $user = User::where([
       'email'  => $email,
       'password' => $password
    ])->first();
    //Comprobar si son correctas
    $signup = false;
    if(is_object($user)){
        $signup = true;
    }
    //Generar el token con los datos del usuario identificado
    if($signup){
        $token = [
            'sub'       => $user->id,
            'email'     => $user->email,
            'name'      => $user->name,
            'surname'   => $user->surname,
            'description'   => $user->description,
            'image'   => $user->image,
            'iat'       => time(),
            'exp'       => time()+(7*24*60*60)
        ];
        
        $jwt = JWT::encode($token, $this->key, 'HS256');
        $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        
        //Devolver los datos decodificados o el token en función de un parámetro
        if(is_null($getToken)){
            $data = $jwt;
        }else{
            $data = $decoded;
        }
    }else{
        $data = [
            'status' => 'error',
            'message' => 'Login incorrecto',
        ];
    }
    
    
    
    return $data;
    }
    */
    
public function signup($email, $password, $getToken = null){
    
    //Buscar si existe el usuario con las credenciales
    /*
    $user = User::where([
       'email'  => $email,
       'password' => $password
    ])->first();
    //Comprobar si son correctas
    $signup = false;
    if(is_object($user)){
        $signup = true;
    }
     * */
    $hashed_password = env('ADMIN_PSW');
    $email_on_env = env('ADMIN');
    $signup = false;
    
    $checked = Hash::check($password,$hashed_password);
    $email_eq = $email == $email_on_env;
    
    if(
        $checked && $email_eq
    ){
        $signup = true;
    }
     
    //Generar el token con los datos del usuario identificado
    if($signup){
        /*
        $token = [
            'sub'       => $user->id,
            'email'     => $user->email,
            'name'      => $user->name,
            'surname'   => $user->surname,
            'description'   => $user->description,
            'image'   => $user->image,
            'iat'       => time(),
            'exp'       => time()+(7*24*60*60)
        ];
         * 
         */
        $token = [
            'sub'       => 1,
            'email'     => env('ADMIN'),
            'name'      => 'Hola',
            'surname'   => 'Admin',
            'iat'       => time(),
            'exp'       => time()+(7*24*60*60)
        ];
        
        $jwt = JWT::encode($token, $this->key, 'HS256');
        $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        
        //Devolver los datos decodificados o el token en función de un parámetro
        if(is_null($getToken)){
            $data = $jwt;
        }else{
            $data = $decoded;
        }
    }else{
        $data = [
            'status' => 'error',
            'message' => 'Login incorrecto'            
        ];
    }
    
    
    
    return $data;
    }
    
    public function checkToken($jwt, $getIdentity = false) {
        $auth = false;
        try{
            $jwt = str_replace('"', '', $jwt);
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        } catch (\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }
        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }
        if($getIdentity){
            return $decoded;
        }
        return $auth;
    }
}
