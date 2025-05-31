<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
use App\Http\Resources\UserResource;
use App\Repositories\Interfaces\AuthRepositoryInterface;

class AuthController extends Controller
{
    use ResponseTrait;

    protected AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * Login user and return access token.
     *
     * @OA\Post(
     *     path="/auth/login",
     *     operationId="loginUser",
     *     tags={"Authentication"},
     *     summary="User Login",
     *     description="Authenticate user using email and password.",
     * @OA\RequestBody(
     *     required=true,
     *     description="Login credentials",
     *     @OA\JsonContent(
     *         required={"email", "password"},
     *         @OA\Property(
     *             property="email",
     *             oneOf={
     *                 @OA\Schema(type="string", format="email", example="user@gmail.com"),
     *                 @OA\Schema(type="string", format="email", example="manager@gmail.com")
     *             }
     *         ),
     *         @OA\Property(
     *             property="password",
     *             type="string",
     *             format="password",
     *             example="123456"
     *         )
     *     )
     * ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User logged in successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Manager"),
     *                     @OA\Property(property="email", type="string", example="manager@gmail.com"),
     *                     @OA\Property(property="role", type="string", example="manager"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-30 16:46:09"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-05-30 16:46:09")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=401),
     *             @OA\Property(property="message", type="string", example="Invalid credentials"),
     *             @OA\Property(property="data", type="object", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=422),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="errors", type="object",
     *                     @OA\Property(property="email", type="array",
     *                         @OA\Items(type="string", example="The email field is required.")
     *                     ),
     *                     @OA\Property(property="password", type="array",
     *                         @OA\Items(type="string", example="The password field is required.")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="code", type="integer", example=500),
     *             @OA\Property(property="message", type="string", example="Something went wrong"),
     *             @OA\Property(property="data", type="object", example=null)
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = validator($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->error("Validation error", 422, [
                'errors' => $validator->errors()
            ]);
        }

        try {
            $result = $this->authRepository->login($request->only('email', 'password'));
        } catch (\Exception $e) {
            return $this->error('Something went wrong', 500);
        }

        if ($result['success']) {
            return $this->success([
                'user'  => new UserResource($result['data']['user']),
                'token' => $result['data']['token'],
            ], $result['message'], $result['code']);
        }

        return $this->error($result['message'], $result['code']);
    }

    /**
     * Logout authenticated user.
     *
     * @OA\Post(
     *     path="/auth/logout",
     *     operationId="logoutUser",
     *     tags={"Authentication"},
     *     summary="Logout",
     *     description="Logout and revoke token",
     *     security={{"Bearer":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="User logged out successfully"),
     *             @OA\Property(property="data", type="object", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *         )
     *     ),
     * )
     */
    public function logout()
    {
        try {
            $result = $this->authRepository->logout();

            return $result['success']
                ? $this->success(null, $result['message'], $result['code'])
                : $this->error($result['message'], $result['code']);
        } catch (\Exception $e) {
            return $this->error('Something went wrong', 500);
        }
    }
}
