<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Register a homeowner (customer) account.
     * Contractors register through /business/onboarding/account instead.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $existing = User::where('email', $validated['email'])->first();

        // A guest who requested a quote already has a passwordless "pending" account; let them claim it
        $isGuest = $existing
            && $existing->status === 'pending'
            && $existing->hasRole('customer')
            && ! $existing->businessProfile()->exists();

        if ($existing && ! $isGuest) {
            throw ValidationException::withMessages(['email' => ['An account with this email already exists.']]);
        }

        $user = DB::transaction(function () use ($validated, $existing) {
            Role::findOrCreate('customer', 'api');

            $user = $existing ?? new User(['email' => $validated['email']]);
            $user->fill([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? $user->phone,
                'password' => Hash::make($validated['password']),
                'status' => 'active',
            ])->save();

            if (! $user->hasRole('customer')) {
                $user->assignRole('customer');
            }

            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                array_filter(['city' => $validated['city'] ?? null, 'state' => $validated['state'] ?? null]),
            );

            return $user;
        });

        return $this->tokenResponse($user, 'Account created successfully.', 201);
    }

    /**
     * Log in a customer or contractor and return a JWT.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $token = auth('api')->attempt($credentials);

        if (! $token) {
            return $this->unauthorized('The email or password is incorrect.');
        }

        /** @var User $user */
        $user = auth('api')->user();

        if ($user->hasRole(['admin', 'super_admin'])) {
            auth('api')->logout();

            return $this->forbidden('Admin accounts sign in through the admin panel.');
        }

        if ($user->status === 'suspended') {
            auth('api')->logout();

            return $this->forbidden('This account has been suspended. Please contact support.');
        }

        return $this->tokenResponse($user, 'Logged in successfully.', 200, $token);
    }

    public function me(): JsonResponse
    {
        return $this->success(new AuthUserResource($this->loadUser(auth('api')->user())), 'Authenticated user retrieved.');
    }

    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();

        return $this->tokenResponse(auth('api')->setToken($token)->user(), 'Token refreshed.', 200, $token);
    }

    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return $this->success(null, 'Logged out successfully.');
    }

    /**
     * Email a password reset link. Always responds the same way so emails can't be enumerated.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker()->sendResetLink($request->only('email'));

        return $this->success(null, 'If an account exists for that email, a reset link has been sent.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        return $status === Password::PASSWORD_RESET
            ? $this->success(null, 'Your password has been reset. You can now log in.')
            : throw ValidationException::withMessages(['email' => ['This password reset link is invalid or has expired.']]);
    }

    /**
     * Change the password of the logged-in user (customer or contractor settings).
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8), 'different:current_password'],
        ]);

        /** @var User $user */
        $user = auth('api')->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages(['current_password' => ['Your current password is incorrect.']]);
        }

        $user->update(['password' => Hash::make($request->input('password'))]);

        return $this->success(null, 'Password updated successfully.');
    }

    private function tokenResponse(User $user, string $message, int $code, ?string $token = null): JsonResponse
    {
        $token ??= auth('api')->login($user);

        return $this->success([
            'user' => new AuthUserResource($this->loadUser($user)),
            'token' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ], $message, $code);
    }

    private function loadUser(User $user): User
    {
        return $user->load(['roles', 'businessProfile', 'customerProfile', 'subscription.plan']);
    }
}
