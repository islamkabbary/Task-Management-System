<?php


namespace App\Repositories;

use App\Traits\ResponseFormatter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Repositories\Interfaces\AuthRepositoryInterface;

class AuthRepository implements AuthRepositoryInterface
{
    use ResponseFormatter;

    public function login(array $credentials)
    {
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return $this->formatError('Invalid credentials', 401);
            }
            return $this->formatSuccess([
                'user' => Auth::user() ,
                'token' => $token
            ], 'User logged in successfully');
        } catch (JWTException $e) {
            return $this->formatError('Could not create token', 500);
        }

        return response()->json(compact('token'));
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->formatSuccess(null, 'User logged out successfully');
        } catch (JWTException $e) {
            return $this->formatError('Failed to logout. Token might be invalid.', 500);
        }
    }
}
