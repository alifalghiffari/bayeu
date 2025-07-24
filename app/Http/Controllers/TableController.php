<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Table;
use App\Models\Area;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TableController extends Controller
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
        $request->validate([
            'area' => 'required|exists:areas,id',
        ], [
            'area.required' => 'Area is required.',
            'area.exists' => 'Selected area does not exist.',
        ]);

        // Get the area (fail if not found)
        $area = Area::findOrFail($request->area);

        // Fetch tables in that area, with optional search filter
        $tablesQuery = Table::where('area_id', $request->area);

        $tables = $tablesQuery->get();

        return response()->json([
            'data' => [
                'area' => $area,
                'tables' => $tables,
            ]
        ], 200);
    }


    public function tableAvailable()
    {
        $tables = Table::whereDoesntHave('order', function ($query) {
            $query->where('is_paid', 0);
        })->get();

        return response()->json([
            'data' => $tables
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
        $data = [
            'number' => $request->number
        ];

        $validator = Validator::make($data, [
            'number' => 'required|unique:tables,number'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $table = Table::create($data);

        return response()->json([
            'message' => 'Table Number Created',
            'data' => $table
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Table $table)
    {
        $table->load('order');

        return response()->json([
            'data' => $table
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Table $table)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Table $table)
    {
        $data = $request->validate([
            'areaId' => 'required|exists:areas,id',
            'layout' => 'required|array',
            'layout.*.i' => 'required|string',
            'layout.*.x' => 'required|integer',
            'layout.*.y' => 'required|integer',
            'layout.*.w' => 'required|integer',
            'layout.*.h' => 'required|integer',
        ]);

        // Update the main table's area_id if needed
        $table->update([
            'area_id' => $data['areaId'],
            'updated_by' => auth()->id(),
        ]);

        // 1. Delete all existing tables in this area
        Table::where('area_id', $data['areaId'])->delete();

        // 2. Create new tables based on the layout
        foreach ($data['layout'] as $item) {
            Table::create([
                'id' => $item['i'],
                'x' => $item['x'],
                'y' => $item['y'],
                'w' => $item['w'],
                'h' => $item['h'],
                'area_id' => $data['areaId'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'message' => 'Table layout updated successfully.',
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Table $table)
    {
        $table->delete();

        return response()->json([
            'message' => 'Table Number Deleted Success'
        ], 200);
    }
}
