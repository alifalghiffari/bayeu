<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Tenant;
use ImageKit\ImageKit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{

    protected $user;

    public function __construct()
    {
        $this->user = JWTAuth::parseToken()->authenticate();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tenant::withCount('menu');


        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'true' ? 1 : 0);
        }

        if ($request->has('sort_by')) {
            $sortField = $request->sort_by;
            $sortOrder = $request->get('sort_order', 'asc');

            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if (!$request->has('sort_by')) {
            $query->orderBy('created_at', 'desc');
        }


        $tenant = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Tenant list fetched successfully',
            'data' => $tenant,
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
        $data = $request->only('name', 'image', 'is_active');

        $validator = Validator::make($data, [
            'name' => 'required',
            'image' => 'nullable|string',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            // $imageKit = new ImageKit(
            //     "public_KuN5avQKzxmcVfFvEf0O0EhQV5Y=",
            //     "private_DKSHmIiZRjQh705vC7CKstexMzU=",
            //     "https://ik.imagekit.io/mlmeg56yl"
            // );

            // if (!empty($data['image'])) {
            //     $uploadResponse = $imageKit->upload([
            //         "file" => $data['image'],
            //         "fileName" => Str::slug($data['name']) . '-' . time(),
            //         "folder" => "/tenant"
            //     ]);

            //     if (isset($uploadResponse->result->url)) {
            //         $data['image'] = $uploadResponse->result->url;
            //     } else {
            //         return response()->json([
            //             'message' => 'Image upload failed',
            //             'error' => isset($uploadResponse->error) ? json_encode($uploadResponse->error) : 'Unknown error'
            //         ], 500);
            //     }
            // }

            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            $tenant = Tenant::create($data);

            $tenant = Tenant::withCount('menu')->find($tenant->id);

            DB::commit();
            return response()->json([
                'message' => 'Tenant Created Successfully',
                'data' => $tenant
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Create Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        $tenant->load('menu', 'userTenant');

        return response()->json([
            'data' => $tenant
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->only('name', 'image', 'is_active');

        $validator = Validator::make($data, [
            'name' => 'nullable',
            'image' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $tenant = Tenant::lockForUpdate()->findOrFail($tenant->id);

            // $imageKit = new ImageKit(
            //     "public_KuN5avQKzxmcVfFvEf0O0EhQV5Y=",
            //     "private_DKSHmIiZRjQh705vC7CKstexMzU=",
            //     "https://ik.imagekit.io/mlmeg56yl"
            // );

            // if (!empty($data['image'])) {
            //     $uploadResponse = $imageKit->upload([
            //         "file" => $data['image'],
            //         "fileName" => Str::slug($data['name'] ?? $tenant->name) . '-' . time(),
            //         "folder" => "/tenant"
            //     ]);

            //     if (isset($uploadResponse->result->url)) {
            //         if ($tenant->image) {
            //             $this->deleteImageKit($tenant->image);
            //         }
            //         $data['image'] = $uploadResponse->result->url;
            //     } else {
            //         DB::rollBack();
            //         return response()->json([
            //             'message' => 'Image upload failed'
            //         ], 500);
            //     }
            // }

            $data['updated_by'] = auth()->id();

            $tenant->update($data);

            DB::commit();
            return response()->json([
                'message' => 'Tenant Update Success',
                'data' => $tenant
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Update Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        DB::beginTransaction();
        try {
            $tenant = Tenant::lockForUpdate()->findOrFail($tenant->id);

            if ($tenant->image) {
                $this->deleteImageKit($tenant->image);
            }

            $tenant->delete();

            DB::commit();
            return response()->json([
                'message' => 'Tenant Deleted Success'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Delete Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function deleteImageKit($imageUrl)
    {
        $imageKit = new ImageKit(
            "public_KuN5avQKzxmcVfFvEf0O0EhQV5Y=",
            "private_DKSHmIiZRjQh705vC7CKstexMzU=",
            "https://ik.imagekit.io/mlmeg56yl"
        );

        $filename = basename(parse_url($imageUrl, PHP_URL_PATH));

        $fileIdResponse = $imageKit->listFiles([
            'searchQuery' => "name = \"$filename\""
        ]);

        if (!empty($fileIdResponse->result) && isset($fileIdResponse->result[0]->fileId)) {
            $imageKit->deleteFile($fileIdResponse->result[0]->fileId);
        }
    }
}
