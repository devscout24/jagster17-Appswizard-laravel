<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Customer sign up. Contractors sign up through /business/onboarding/account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            Role::findOrCreate('customer', 'api');

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => $validated['password'],
                'phone'    => $validated['phone'] ?? null,
                'status'   => 'active',
            ]);

            $user->assignRole('customer');

            CustomerProfile::create([
                'user_id' => $user->id,
                'city'    => $validated['city'] ?? null,
                'state'   => $validated['state'] ?? null,
            ]);

            return $user;
        });

        $token = auth('api')->login($user);

        return $this->success($this->tokenPayload($user, $token), 'Account created successfully.', 201);
    }

    /**
     * Log in a customer or contractor and issue a JWT.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $token = auth('api')->attempt($credentials);
        if (! $token) {
            return $this->unauthorized('Invalid email or password.');
        }

        /** @var User $user */
        $user = auth('api')->user();

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            auth('api')->logout();

            return $this->forbidden('Admin accounts must sign in through the admin panel.');
        }

        if ($user->status === 'suspended') {
            auth('api')->logout();

            return $this->forbidden('Your account has been suspended. Please contact support.');
        }

        return $this->success($this->tokenPayload($user, $token), 'Logged in successfully.');
    }

    /**
     * Current authenticated user.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user()->load(['roles', 'customerProfile', 'businessProfile', 'subscription.plan']);

        return $this->success(new AuthUserResource($user), 'Authenticated user retrieved successfully.');
    }

    /**
     * Invalidate the current token.
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Exchange the current (possibly expired, within refresh window) token for a new one.
     */
    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();
        $user = auth('api')->setToken($token)->user();

        return $this->success($this->tokenPayload($user, $token), 'Token refreshed successfully.');
    }

    /**
     * Send a password reset link. Always responds the same way to avoid revealing which emails exist.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return $this->success(null, 'If an account exists for that email, a password reset link has been sent.');
    }

    /**
     * Reset the password using the emailed token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(__($status), 422);
        }

        return $this->success(null, 'Your password has been reset. You can now log in.');
    }

    private function tokenPayload(User $user, string $token): array
    {
        $user->load(['roles', 'customerProfile', 'businessProfile', 'subscription.plan']);

        return [
            'token'      => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user'       => new AuthUserResource($user),
        ];
    }
}
