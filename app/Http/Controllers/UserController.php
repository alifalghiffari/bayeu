<?php

namespace App\Http\Controllers;

use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    protected $user;

    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
    }

    /**
     * Get all users with either 'cashier' or 'waiter' role.
     */
    public function getCashiersAndWaiters(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        } else {
            $query->whereIn('role', ['cashier', 'waiter']);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('sort_by')) {
            $sortField = $request->sort_by;
            $sortOrder = $request->get('sort_order', 'asc');

            if (in_array($sortField, ['name'])) {
                $query->orderBy($sortField, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'User crew list fetched successfully',
            'data' => $users,
        ], 200);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $data = $request->only('name', 'profile_url', 'email', 'username', 'role');

        $validator = Validator::make($data, [
            'name' => 'required',
            'profile_url' => 'nullable|string',
            'email' => 'required|email|unique:users',
            'username' => 'required|unique:users',
            'role' => 'required|in:CASHIER,WAITER'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data['password'] = bcrypt('password');

        DB::beginTransaction();
        try {

            $crew = User::create($data);

            DB::commit();
            return response()->json([
                'message' => 'User Created Successfully',
                'data' => $crew
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'User Create Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->only('name', 'username', 'profile_url');

        $validator = Validator::make($data, [
            'name' => 'required',
            'username' => 'required',
            'profile_url' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->update($data);

            DB::commit();
            return response()->json([
                'message' => 'User Updated Successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Update Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(User $user)
    {
        DB::beginTransaction();
        try {
            $user = User::lockForUpdate()->findOrFail($user->id);

            $user->delete();

            DB::commit();
            return response()->json([
                'message' => 'User Deleted Successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Delete Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
