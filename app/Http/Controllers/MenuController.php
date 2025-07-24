<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Menu;
use ImageKit\ImageKit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
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

        $query = Menu::with(['category', 'tenant']);

        if ($request->has('category') && $request->category !== 'all') {
            $categories = explode(',', $request->category);
            $query->whereHas('category', function ($q) use ($categories) {
                $q->whereIn('name', $categories);
            });
        }


        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('tenant')) {
            $tenants = explode(',', $request->tenant);
            $query->whereHas('tenant', function ($q) use ($tenants) {
                $q->whereIn('name', $tenants);
            });
        }


        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('sort_by')) {
            $sortField = $request->sort_by;
            $sortOrder = $request->get('sort_order', 'asc');

            if (in_array($sortField, ['price', 'name'])) {
                $query->orderBy($sortField, $sortOrder);
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if (!$request->has('sort_by')) {
            $query->orderBy('created_at', 'desc');
        }


        $menu = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Menu list fetched successfully',
            'data' => $menu,
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
        $data = $request->only('category_id', 'tenant_id', 'name', 'image', 'price', 'tax', 'is_percent_tax');

        $validator = Validator::make($data, [
            'category_id' => 'required|exists:menu_categories,id',
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required',
            'image' => 'nullable|string',
            'price' => 'required|numeric',
            'tax' => 'required|numeric',
            'is_percent_tax' => 'required|boolean',
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
            //         "folder" => "/menu"
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

            $menu = Menu::create($data);
            $menu->load('category', 'tenant');

            DB::commit();
            return response()->json([
                'message' => 'Menu Created Successfully',
                'data' => $menu
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
    public function show(Menu $menu)
    {
        $menu->load('category', 'tenant', 'orderItems');

        return response()->json([
            'data' => $menu
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Menu $menu)
    {
        $data = $request->only('category_id', 'tenant_id', 'name', 'image', 'price', 'tax', 'is_percent_tax', 'is_active');

        $validator = Validator::make($data, [
            'category_id' => 'nullable|exists:menu_categories,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'name' => 'nullable',
            'image' => 'nullable|string',
            'price' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'is_percent_tax' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $menu = Menu::lockForUpdate()->findOrFail($menu->id);

            // $imageKit = new ImageKit(
            //     "public_KuN5avQKzxmcVfFvEf0O0EhQV5Y=",
            //     "private_DKSHmIiZRjQh705vC7CKstexMzU=",
            //     "https://ik.imagekit.io/mlmeg56yl"
            // );


            // if (!empty($data['image'])) {
            //     $uploadResponse = $imageKit->upload([
            //         "file" => $data['image'],
            //         "fileName" => Str::slug($data['name'] ?? $menu->name) . '-' . time(),
            //         "folder" => "/menu"
            //     ]);
            //     error_log(print_r($uploadResponse, true));
            //     error_log("hello");

            //     if (isset($uploadResponse->result->url)) {
            //         if ($menu->image) {
            //             $this->deleteImageKit($menu->image);
            //         }
            //         $data['image'] = $uploadResponse->result->url;
            //     } else {
            //         DB::rollBack();
            //         return response()->json([
            //             'message' => 'Image upload failed',
            //             'error' => isset($uploadResponse->error) ? json_encode($uploadResponse->error) : 'Unknown error'
            //         ], 500);
            //     }
            // }

            $data['updated_by'] = auth()->id();
            $menu->update($data);
            $menu->load('category', 'tenant');

            DB::commit();
            return response()->json([
                'message' => 'Menu Updated Successfully',
                'data' => $menu
            ], 200);
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
    public function destroy(Menu $menu)
    {
        DB::beginTransaction();
        try {
            $menu = Menu::lockForUpdate()->findOrFail($menu->id);

            if ($menu->image) {
                $this->deleteImageKit($menu->image);
            }

            $menu->delete();

            DB::commit();
            return response()->json([
                'message' => 'Menu Deleted Successfully'
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