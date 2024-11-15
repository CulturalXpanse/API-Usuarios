<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ciudad;
use App\Models\Perfil;
use App\Models\Seguidor;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

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

            $ciudad = $perfil->ciudad;
    
            return response()->json([
                'id' => $usuario->id,
                'name' => $usuario->name,
                'nombre_completo' => $perfil->nombre_completo,
                'ciudad' => $ciudad ? $ciudad->nombre : 'Ubicación Desconocida',
                'pais' => $ciudad ? $ciudad->pais->nombre : 'Ubicación Desconocida',
                'fecha_nacimiento' => $perfil->fecha_nacimiento ? Carbon::parse($perfil->fecha_nacimiento)->format('d/m/Y') : null,
                'biografia' => $perfil->biografia ?: 'Sin biografía',
                'foto_perfil' => $perfil->foto_perfil
            ], 200);
        }
    
        return response()->json(['error' => 'No autenticado'], 401);
    }

    public function obtenerPerfilCompletoPorID($id)
    {
        $usuario = User::find($id);
    
        if ($usuario) {

            $perfil = $usuario->perfil;
            $ciudad = $perfil->ciudad;
    
            return response()->json([
                'id' => $usuario->id,
                'name' => $usuario->name,
                'nombre_completo' => $perfil->nombre_completo,
                'ciudad' => $ciudad ? $ciudad->nombre : 'Ubicación Desconocida',
                'pais' => $ciudad ? $ciudad->pais->nombre : 'Ubicación Desconocida',
                'fecha_nacimiento' => $perfil->fecha_nacimiento ? Carbon::parse($perfil->fecha_nacimiento)->format('d/m/Y') : null,
                'biografia' => $perfil->biografia ?: 'Sin biografía',
                'foto_perfil' => $perfil->foto_perfil
            ], 200);
        }
    
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }
    
    public function obtenerUsuarioPorId($id) {
        $usuario = User::find($id);
        
        if ($usuario) {
            $perfil = $usuario->perfil;
            return response()->json([
                'name' => $usuario->name,
                'foto_perfil' => $perfil ? $perfil->foto_perfil : 'default-profile.png'
            ], 200);
        }
    
        return response()->json(['error' => 'Usuario no encontrado'], 404);
    }

    public function obtenerCiudades() {
        try {
            $ciudades = Ciudad::all();
            return response()->json($ciudades);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudieron obtener las ciudades.'], 500);
        }
    }

    public function mostrarPais($id) {
        $ciudad = Ciudad::with('pais')->find($id);
        return response()->json($ciudad);
    }

    public function actualizar(Request $request)
    {

        $request->validate([
            'name' => 'string|max:255|unique:users,name,' . auth()->id(),
            'nombre_completo' => 'string|max:255|nullable',
            'fecha_nacimiento' => 'date|nullable',
            'biografia' => 'string|nullable',
            'ciudad' => 'nullable|exists:ciudades,id',
            'foto_perfil' => 'image|nullable|max:2048'
        ]);
    
        $usuario = auth()->user();
        $perfil = Perfil::where('user_id', $usuario->id)->firstOrFail();
    

        if ($request->filled('name')) {
            $usuario->name = $request->input('name');
        }
    
        if ($request->filled('nombre_completo')) {
            $perfil->nombre_completo = $request->input('nombre_completo');
        }
    
        if ($request->filled('fecha_nacimiento')) {
            $perfil->fecha_nacimiento = $request->input('fecha_nacimiento');
        }
    
        if ($request->filled('biografia')) {
            $perfil->biografia = $request->input('biografia');
        }

        if ($request->filled('ciudad')) {
            $perfil->ciudad_id = $request->input('ciudad');
        }

        if ($request->hasFile('foto_perfil')) {
            $file = $request->file('foto_perfil');
            $fileName = Str::random(50) . '.' . $file->getClientOriginalExtension();
            $destinationPath = 'imagenes/perfiles';
            $file->move($destinationPath, $fileName);
    
            $perfil->foto_perfil = $fileName;
        }
    
        $usuario->save();
        $perfil->save();
    
        return response()->json(['message' => 'Perfil actualizado exitosamente'], 200);
    }
    
    public function seguir($seguidorId, $seguidoId) {
    if ($seguidorId == $seguidoId) {
        return response()->json(['error' => 'No puedes seguirte a ti mismo.'], 400);
    }

    $seguidor = User::find($seguidorId);
    $seguido = User::find($seguidoId);

    if (!$seguidor || !$seguido) {
        return response()->json(['error' => 'Uno o ambos usuarios no existen.'], 404);
    }

    $existe = Seguidor::where('seguidor_id', $seguidorId)
                        ->where('seguido_id', $seguidoId)
                        ->exists();

    if ($existe) {
        return response()->json(['message' => 'Ya sigues a este usuario.'], 400);
    }

    Seguidor::create([
        'seguidor_id' => $seguidorId,
        'seguido_id' => $seguidoId,
    ]);

    return response()->json(['message' => 'Ahora sigues a este usuario.'], 201);
    }

    public function dejarDeSeguir($seguidorId, $seguidoId) {
        if ($seguidorId == $seguidoId) {
            return response()->json(['error' => 'No puedes dejar de seguirte a ti mismo.'], 400);
        }
    
        $seguidor = User::find($seguidorId);
        $seguido = User::find($seguidoId);
    
        if (!$seguidor || !$seguido) {
            return response()->json(['error' => 'Uno o ambos usuarios no existen.'], 404);
        }
    
        $seguimiento = Seguidor::where('seguidor_id', $seguidorId)
                                ->where('seguido_id', $seguidoId)
                                ->first();
    
        if (!$seguimiento) {
            return response()->json(['message' => 'No sigues a este usuario.'], 400);
        }
    
        $seguimiento->delete();
    
        return response()->json(['message' => 'Has dejado de seguir a este usuario.'], 200);
    }

    public function obtenerAmigos($usuarioId) {
        $amigos = DB::table('seguidores')
            ->join('users', 'seguidores.seguido_id', '=', 'users.id')
            ->join('perfiles', 'perfiles.user_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'perfiles.foto_perfil')
            ->where('seguidores.seguidor_id', $usuarioId)
            ->get();
    
        return response()->json($amigos);
    }

    public function verificarAmistad($userId, $profileId) {
        $sonAmigos = DB::table('seguidores')
                        ->where('seguidor_id', $userId)
                        ->where('seguido_id', $profileId)
                        ->exists();

        return response()->json(['sonAmigos' => $sonAmigos]);
    }
}