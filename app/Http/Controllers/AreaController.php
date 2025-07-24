<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Area;
use ImageKit\ImageKit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AreaController extends Controller
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
        $query = Area::withCount('tables');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $areas = $query->get();

        return response()->json([
            'data' => $areas
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = [
            'name' => $request->name
        ];

        $validator = Validator::make($data, [
            'name' => 'required|unique:areas,name'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $area = Area::create($data);

        $area = Area::withCount('tables')->find($area->id);

        return response()->json([
            'message' => 'Area Created',
            'data' => $area
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Area $area)
    {
        $area->loadCount('tables');
        $area->load('tables');

        return response()->json([
            'data' => $area
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Area $area)
    {
        $data = $request->only('name');

        $validator = Validator::make($data, [
            'name' => 'required|unique:areas,name,' . $area->id
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data['updated_by'] = auth()->id();

        $area->update($data);

        return response()->json([
            'message' => 'Area Updated',
            'data' => $area
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Area $area)
    {
        $area->delete();

        return response()->json([
            'message' => 'Area Deleted Successfully'
        ], 200);
    }
}
