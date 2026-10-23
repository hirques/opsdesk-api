<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role'])]);
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $input = $request->validate(['role' => ['required', Rule::in(['viewer', 'operator', 'admin'])]]);
        abort_if($user->id === $request->user()->id, 403, 'Self-demotion or promotion is not allowed.');

        DB::transaction(function () use ($user, $input): void {
            $user->forceFill(['role' => $input['role']])->save();
            $user->tokens()->delete(); // Privilege changes require fresh tokens.
        });

        return response()->json(['data' => $user->only(['id', 'name', 'email', 'role'])]);
    }
}
