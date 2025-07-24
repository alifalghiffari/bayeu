<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\UserTenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;

class UserTenantController extends Controller
{

    protected $user;
 
    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userTenant = UserTenant::with('tenant', 'user')->get();

        return response()->json([
            'data' => $userTenant
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->only('name', 'email', 'username', 'password', 'role');
        $validator = Validator::make($data, [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required|min:8',
            'role' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data['password'] = bcrypt($data['password']);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $user = User::create($data);

        $userTenants = $request->input('user_tenants', []);
        $validatedUserTenants = [];

        foreach ($userTenants as $userTenant) {
            $validator = Validator::make($userTenant, [
                'tenant_id' => 'required|exists:tenants,id',
                'is_admin' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $validatedUserTenant = $userTenant;
            $validatedUserTenant['user_id'] = $user->id;
            $validatedUserTenant['created_by'] = auth()->id();
            $validatedUserTenant['updated_by'] = auth()->id();
            $validatedUserTenants[] = $validatedUserTenant;
        }

        $user->userTenants()->createMany($validatedUserTenants);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user->load('userTenants.tenant'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserTenant $userTenant)
    {
        $userTenant->load('user', 'tenant');

        return response()->json([
            'data' => $userTenant,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserTenant $userTenant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserTenant $userTenant)
    {
        $data = $request->only('is_admin');
        $validator = Validator::make($data, [
            'is_admin' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data['updated_by'] = auth()->id();

        $userTenant->update($data);

        return response()->json([
            'message' => 'User Tenant updated successfully',
            'data' => $userTenant,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserTenant $userTenant)
    {
        $userTenant->delete();

        return response()->json([
            'message' => 'User Tenant deleted successfully',
        ], 200);
    }
}
