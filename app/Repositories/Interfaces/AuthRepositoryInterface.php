<?php

namespace App\Repositories\Interfaces;

interface AuthRepositoryInterface {
    public function login(array $credentials);
    public function logout();
}