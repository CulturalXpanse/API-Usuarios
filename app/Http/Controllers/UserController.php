<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Ciudad;
use App\Models\Perfil;

class UserController extends Controller
{
    public function Register(Request $request){

        $validation = Validator::make($request->all(),[
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed'
        ]);

        if($validation->fails())
            return $validation->errors();

        return $this -> createUser($request);

    }

    private function createUser($request) {
        try {
            DB::beginTransaction();
    
            // Crear el usuario
            $user = new User();
            $user->name = $request->post("name");
            $user->email = $request->post("email");
            $user->password = Hash::make($request->post("password"));
            $user->save();
    
            $this->createPerfil($user);

            DB::commit();
    
            return $user;
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo crear el usuario y perfil' . $e->getMessage()], 500);
        }
    }

    private function createPerfil($user) {
        $perfil = new Perfil();
        $perfil->user_id = $user->id;
        $perfil->nombre_completo = $user->name;
        $perfil->ciudad_id = null;
        $perfil->fecha_nacimiento = null;
        $perfil->foto_perfil = 'perfilDefault.png';
        $perfil->biografia = '';
        $perfil->save();
    }

    public function ValidateToken(Request $request){
        return auth('api')->user();
    }

    public function Logout(Request $request){
        $request->user()->token()->revoke();
        return ['message' => 'Token Revoked'];
    }

    public function obtenerUsuarioActual()
    {
        $usuario = auth()->user();
    
        if ($usuario) {

            $perfil = $usuario->perfil;
    
            return response()->json([
                'name' => $usuario->name,
                'nombre_completo' => $perfil->nombre_completo,
                'ciudad' => $perfil->ciudad_id,
                'biografia' => $perfil->biografia
            ], 200);
        }
    
        return response()->json(['error' => 'No autenticado'], 401);
    }
    

    public function obtenerCiudades() {
        try {
            $ciudades = Ciudad::all();
            return response()->json($ciudades);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las ciudades.'], 500);
        }
    }

    public function show($id) {
        $ciudad = Ciudad::with('pais')->find($id);
        return response()->json($ciudad);
    }

    public function actualizar(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'string|max:255',
        ]);

        $usuario = auth()->user();

        $perfil = Perfil::where('user_id', $usuario->id)->firstOrFail();
        $perfil->nombre_completo = $request->input('nombre_completo');
        $perfil->save();

        return response()->json(['message' => 'Perfil actualizado exitosamente'], 200);
    }
}