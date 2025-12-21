<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $roles = $query->withCount('users')->paginate($request->input('per_page', 15));

        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:30|unique:roles,code|regex:/^[a-z_]+$/',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'name.max' => 'Le nom ne peut pas dépasser 50 caractères.',
            'code.required' => 'Le code du rôle est obligatoire.',
            'code.unique' => 'Ce code existe déjà.',
            'code.regex' => 'Le code doit contenir uniquement des lettres minuscules et des underscores.',
        ]);

        $role = Role::create($validated);

        return response()->json([
            'message' => 'Rôle créé avec succès.',
            'data' => $role,
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        $role->loadCount('users');
        $role->load('users:id,name,email');

        return response()->json($role);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'code' => ['required', 'string', 'max:30', 'regex:/^[a-z_]+$/', Rule::unique('roles')->ignore($role->id)],
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'code.unique' => 'Ce code existe déjà.',
        ]);

        $role->update($validated);

        return response()->json([
            'message' => 'Rôle mis à jour avec succès.',
            'data' => $role,
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce rôle car des utilisateurs y sont associés.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Rôle supprimé avec succès.',
        ]);
    }
}
