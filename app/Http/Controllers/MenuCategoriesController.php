<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\MenuCategories;
use ImageKit\ImageKit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MenuCategoriesController extends Controller
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
        $category = MenuCategories::get();

        return response()->json([
            'data' => $category
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
        $data = $request->only('name', 'image');

        $validator = Validator::make($data, [
            'name' => 'required',
            'image' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $imageKit = new ImageKit(
                "public_KuN5avQKzxmcVfFvEf0O0EhQV5Y=",
                "private_DKSHmIiZRjQh705vC7CKstexMzU=",
                "https://ik.imagekit.io/mlmeg56yl"
            );

            if (!empty($data['image'])) {
                $uploadResponse = $imageKit->upload([
                    "file" => $data['image'],
                    "fileName" => Str::slug($data['name']) . '-' . time(),
                    "folder" => "/category"
                ]);
        
                if (isset($uploadResponse->result->url)) {
                    $data['image'] = $uploadResponse->result->url;
                } else {
                    return response()->json([
                        'message' => 'Image upload failed',
                        'error' => isset($uploadResponse->error) ? json_encode($uploadResponse->error) : 'Unknown error'
                    ], 500);
                }
            }

            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            $category = MenuCategories::create($data);

            DB::commit();
            return response()->json([
                'message' => 'Menu Category Created Success',
                'data' => $category
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
    public function show(MenuCategories $category)
    {
        $category->load('menu');

        return response()->json([
            'data' => $category
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MenuCategories $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MenuCategories $category)
    {
        $data = $request->only('name', 'image');

        $validator = Validator::make($data, [
            'name' => 'required',
            'image' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            $category = MenuCategories::lockForUpdate()->findOrFail($category->id);

            $imageKit = new ImageKit(
                "public_KuN5avQKzxmcVfFvEf0O0EhQV5Y=",
                "private_DKSHmIiZRjQh705vC7CKstexMzU=",
                "https://ik.imagekit.io/mlmeg56yl"
            );

            if (!empty($data['image'])) {
                $uploadResponse = $imageKit->upload([
                    "file" => $data['image'],
                    "fileName" => Str::slug($data['name'] ?? $category->name) . '-' . time(),
                    "folder" => "/category"
                ]);

                if (isset($uploadResponse->result->url)) {
                    if ($category->image) {
                        $this->deleteImageKit($category->image);
                    }
                    $data['image'] = $uploadResponse->result->url;
                } else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Image upload failed'
                    ], 500);
                }
            }

            $data['updated_by'] = auth()->id();

            $category->update($data);

            DB::commit();
            return response()->json([
                'message' => 'Menu Category Update Success',
                'data' => $category
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
    public function destroy(MenuCategories $category)
    {
        DB::beginTransaction();
        try {
            $category = MenuCategories::lockForUpdate()->findOrFail($category->id);

            if ($category->image) {
                $this->deleteImageKit($category->image);
            }

            $category->delete();

            DB::commit();
            return response()->json([
                'message' => 'Menu Category Delete Success'
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
