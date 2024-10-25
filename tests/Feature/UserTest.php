<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;


class UserTest extends TestCase
{
    // Client creado desde seeders
    private $clientId = 100;
    private $clientSecret = "wsBa0mp4jwSTYssUGHX5xoqD9IC0X95Gfpg0w3uY";

     // Usuario creado desde seeders
    private $userName = "usuario@email.com";
    private $userPassword = "12345678";

    public function test_ObtenerTokenConClientIdValido()
    {
        $response = $this->post('/oauth/token',[
            "username" => $this -> userName,
            "password" => $this -> userPassword,
            "grant_type" => "password",
            "client_id" => $this -> clientId,
            "client_secret" => $this -> clientSecret
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            "token_type",
            "expires_in",
            "access_token",
            "refresh_token"
        ]);
        $response->assertJsonFragment([
            "token_type" => "Bearer"
        ]);
    }

    public function test_ObtenerTokenConClientIdInvalido()
    {
        $response = $this->post('/oauth/token',[
            "grant_type" => "password",
            "client_id" => "234",
            "client_secret" => Str::Random(8)
        ]);
        $response->assertStatus(401);
        $response->assertJsonFragment([
            "error" => "invalid_client",
            "error_description" => "Client authentication failed",
            "message" => "Client authentication failed"
        ]);
    }

    public function test_ValidarTokenSinEnviarToken()
    {
        $response = $this->get('/api/validate');
        $response->assertStatus(500);
    }

    public function test_ValidarTokenConTokenInvalido()
    {
        $response = $this->get('/api/validate',[
            [ "Authorization" => "Bearer " . Str::Random(40)]
        ]);
        $response->assertStatus(500);
    }

    public function test_ValidarTokenConTokenValido()
    {
        $tokenResponse = $this->post('/oauth/token',[
            "username" => $this -> userName,
            "password" => $this -> userPassword,
            "grant_type" => "password",
            "client_id" => $this -> clientId,
            "client_secret" => $this -> clientSecret
        ]);
        $token = json_decode($tokenResponse -> content(),true);
        $response = $this->get('/api/validate',
            [ "Authorization" => "Bearer " . $token ['access_token']]
        );
        $response->assertStatus(200);
    }

    public function test_LogoutSinToken()
    {
        $response = $this->get('/api/logout');
        $response->assertStatus(500);
    }

    public function test_LogoutConTokenInvalido()
    {
        $response = $this->get('/api/logout',[
            [ "Authorization" => "Bearer " . Str::Random(40)]
        ]);
        $response->assertStatus(500);
    }

    public function test_LogoutConTokenValido()
    {
        $tokenResponse = $this->post('/oauth/token',[
            "username" => $this -> userName,
            "password" => $this -> userPassword,
            "grant_type" => "password",
            "client_id" => $this -> clientId,
            "client_secret" => $this -> clientSecret
        ]);
        $token = json_decode($tokenResponse -> content(),true);
        $response = $this->get('/api/logout',
            [ "Authorization" => "Bearer " . $token ['access_token']],
        );
        $response->assertStatus(200);
        $response->assertJsonFragment(
            ['message' => 'Token Revoked']
        );
    }
}