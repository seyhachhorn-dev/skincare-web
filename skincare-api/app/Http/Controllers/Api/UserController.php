<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()->orderBy('name')->get();

        return $this->respond(UserResource::collection($users), 'Users retrieved successfully');
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        // Direct property assignment — 'role' is intentionally not
        // mass-assignable (see User::$fillable), this endpoint (admin-gated
        // by route middleware) is the one trusted, explicit path to change it.
        $user->role = $request->validated('role');
        $user->save();

        return $this->respond(new UserResource($user), 'User role updated');
    }
}
