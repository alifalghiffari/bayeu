<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentMethodController extends Controller
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
        $payment = PaymentMethod::get();

        return response()->json([
            'data' => $payment->items()
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
        $data = $request->only('name', 'remarks');

        $validator = Validator::make($data, [
            'name' => 'required',
        ]);

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $payment = PaymentMethod::create($data);

        return response()->json([
            'message' => 'Payment Method Created Success',
            'data' => $payment
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentMethod $payment)
    {
        $payment->load('order');

        return response()->json([
            'data' => $payment
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaymentMethod $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentMethod $payment)
    {
        $data = $request->only('name', 'remarks');

        $validator = Validator::make($data, [
            'name' => 'required'
        ]);

        $data['updated_by'] = auth()->id();

        if($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $payment->update($data);

        return response()->json([
            'message' => 'Payment Update Success',
            'data' => $payment
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentMethod $payment)
    {
        $payment->delete();

        return response()->json([
            'message' => 'Payment Deleted Success'
        ], 200);
    }
}
