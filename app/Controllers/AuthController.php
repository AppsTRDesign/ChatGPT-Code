<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): string
    {
        if (isset($_SESSION['user_id'], $_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                header('Location: /admin');
                exit;
            }

            if ($_SESSION['role'] === 'client') {
                header('Location: /client');
                exit;
            }
        }

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
            if (!empty($user['is_blocked'])) {
                return $this->loginError('Hesabınız kilitlenmiştir.');
            }

            if (!empty($user['login_banned_until'])) {
                $banUntil = strtotime((string) $user['login_banned_until']);
                if ($banUntil > time()) {
                    return $this->loginError('Hesabınız geçici olarak askıya alınmıştır.');
                }

                User::updateById((int) $user['id'], ['login_banned_until' => null]);
            }

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

    protected function loginError(string $message): string
    {
        http_response_code(403);
        header('Content-Type: application/json');

        return json_encode([
            'status' => 'error',
            'message' => $message
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
