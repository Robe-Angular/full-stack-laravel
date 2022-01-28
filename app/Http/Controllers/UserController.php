<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller {

    public function pruebas(Request $request) {
        return "Acción de pruebas User-Controller";
    }

    public function register(Request $request) {

        //Recoger los datos del usuario con post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        //Limpiar datos
        if (!empty($params) && !empty($params_array)) {
            $params_array = array_map('trim', $params_array);
            //Validar los datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users', //Comprobar si el usuario existe ya (duplicado)        
                        'password' => 'required',
            ]);

            if ($validate->fails()) {
                //La validación ha fallado
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                );
            } else {

                //Validación completada correctamente
                //Cifrar la contraseña
                $pwd = hash('sha256', $params->password);

                //Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';
                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user
                );

                //Guardar el usuario
                $user->save();
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'Los datos enviados no son correctos'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function login(Request $request) {

        $jwtAuth = new \JwtAuth();
        //Recibir datos por Post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        // Validar  los datos
        $validate = \Validator::make($params_array, [
                    'email' => 'required|email',
                    'password' => 'required'
        ]);

        if ($validate->fails()) {
            //La validación ha fallado
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'El usuario no se ha podido identificar',
                'errors' => $validate->errors()
            );
        } else {
            //Cifrar Password
            $pwd = hash('sha256', $params->password);
            //Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);
            
            if (isset($params->getToken)) {
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }


        return response()->json($signup, 200);
    }

    public function update(Request $request) {
        //Comprobar si está identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);
        //Recoger datos por POST
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        if ($checkToken && !empty($params_array)) {

            //Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            //Validar datos
            $validate = \Validator::make($params_array, [
                        'name' => 'required|alpha',
                        'surname' => 'required|alpha',
                        'email' => 'required|email|unique:users,' . $user->sub, //Comprobar si el usuario existe ya (duplicado)        
            ]);

            //Quitar campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Actualizar usuario en la BDD
            $user_update = User::where('id', $user->sub)->update($params_array);

            //Devolver array con resultado
            $data = [
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'changes' => $params_array,
                'json' => $json
            ];
        } else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al actualizar los datos',
                'params' => $params_array,
                'json' => $json
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {
        //Recoger los datos de la petición
        $image = $request->file('file0');
        //Validación de la imagen
        $validate = \Validator::make($request->all(), [
                    'file0' => 'required|mimes:jpg,jpeg,png,gif'
        ]);

        //Guardar imagen
        if (!$image || $validate->fails()) {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            ];
        } else {
            $image_name = time() . $image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            );
        }

        //Devolver el resultado
        return response()->json($data, $data['code']);
    }

    public function getImage($filename) {
        $isset = \Storage::disk('users')->exists($filename);
        if ($isset) {
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'image' => 'La imagen no existe'
            ];
            return response()->json($data, $data['code']);
        }
    }

    public function detail($id) {
        $user = User::find($id);

        if (is_object($user)) {
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        } else {
            $data = array(
                'code' => 404,
                'status' => 'error',
                'user' => 'El usuario no existe' 
            );
        }
        
        return response()->json($data, $data['code']);
    }

}
