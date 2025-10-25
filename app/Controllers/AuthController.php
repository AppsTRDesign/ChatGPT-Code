<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): string
    {
        return $this->view('client/auth/login', [
            'title' => 'Giriş Yap'
        ]);
    }

    public function login(): string
    {
        $username = $this->request->input('username');
        $password = $this->request->input('password');

        $user = User::findByUsername($username);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            header('Content-Type: application/json');
            return json_encode([
                'status' => 'success',
                'redirect' => $user['role'] === 'admin' ? '/admin' : '/client'
            ]);
        }

        http_response_code(401);
        header('Content-Type: application/json');
        return json_encode([
            'status' => 'error',
            'message' => 'Geçersiz kullanıcı adı veya şifre'
        ]);
    }

    public function logout(): string
    {
        session_destroy();
        header('Content-Type: application/json');
        return json_encode([
            'status' => 'success',
            'redirect' => '/login'
        ]);
    }
}
