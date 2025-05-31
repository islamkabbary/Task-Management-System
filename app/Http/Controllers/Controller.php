<?php

namespace App\Http\Controllers;

/**
 * @OA\OpenApi(
 *     @OA\Server(
 *         url="http://127.0.0.1:8000/api",
 *         description="Base URL for Task Management System"
 *     )
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="Bearer",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * 
 * @OA\Info(
 *     title="Task Management System API",
 *     version="1.0.0",
 *     description="API for managing tasks in SOFTXPERT platform",
 *     @OA\Contact(
 *         email="islamkabbary@gmail.com",
 *         name="Islam Kabbary",
 *         url="https://www.linkedin.com/in/islam-kabbary-2b35a814b"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 */
abstract class Controller
{
    //
}
