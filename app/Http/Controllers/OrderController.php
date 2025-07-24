<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Notifications\OrderNotification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
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
        $orders = Order::with('orderItems.menu', 'table', 'waiter', 'cashier', 'paymentMethod')
                    ->get();

        return response()->json([
            'data' => $orders
        ], 200);
    }

    public function graph() {
        $totalRevenue = Order::sum('total_price');
        
        $totalOrder = Order::count();
        
        $dineIn = Order::whereNotNull('table_id')
                ->whereNotNull('area_id')
                ->count();

        $takeAway = Order::whereNull('table_id')
                ->whereNull('area_id')
                ->count();

        return response()->json([
            'totalRevenue' => $totalRevenue,
            'totalOrder' => $totalOrder,
            'dineIn' => $dineIn,
            'takeAway' => $takeAway
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
        $data = $request->only(['waiters_id','table_id', 'area_id', 'total_price', 'customer', 'remarks']);

        $validator = Validator::make($data, [
            'waiters_id' => 'nullable|exists:users,id,role,WAITER',
            'table_id' => 'nullable|exists:tables,id',
            'area_id' => 'nullable|exists:areas,id',
            'total_price' => 'required|numeric',
            'customer' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

       $monthYear = date('m-Y');

        $lastOrderForMonth = Order::where('no_order', 'like', '%#' . $monthYear)
            ->orderByDesc('no_order')
            ->first();

        if ($lastOrderForMonth) {
            $lastNumber = (int) explode('#', $lastOrderForMonth->no_order)[0]; // <- diperbaiki
            $newOrderNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newOrderNumber = '0001';
        }

        $data['no_order'] = $newOrderNumber . '#' . $monthYear;

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        if (auth()->user()->hasRole('WAITER')) {
            $data['waiters_id'] = auth()->id();
        }

        $order = Order::create($data);

        $orderItems = $request->input('order_items', []);
        $validatedOrderItems = [];

        foreach ($orderItems as $orderItem) {
            $validator = Validator::make($orderItem, [
                'menu_id' => 'required|exists:menus,id',
                'quantity' => 'required|numeric',
                'paid_quantity' => 'nullable|numeric',
                'is_paid' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $validatedOrderItems[] = $orderItem;
        }

        $order->orderItems()->createMany(array_map(function ($item) use ($order) {
            $item['order_id'] = $order->id;
            $item['created_by'] = auth()->id();
            $item['updated_by'] = auth()->id();
            return $item;
        }, $validatedOrderItems));

        $order->load('orderItems.menu', 'table', 'creator', 'updater');

        $tenantIds = $order->orderItems
            ->pluck('menu.tenant_id')
            ->filter()
            ->unique()
            ->toArray();

        $tenants = User::whereHas('userTenants', function ($query) use ($tenantIds) {
            $query->whereIn('tenant_id', $tenantIds);
        })
        ->where('role', 'TENANT')
        ->get();

        Notification::send($tenants, new OrderNotification($order));

        return response()->json([
            'message' => 'Order Created Success',
            'data' => $order->load('orderItems'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load('orderItems.menu', 'table', 'waiter', 'cashier', 'paymentMethod');

        return response()->json([
            'data' => $order
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $data = $request->only('table_id', 'area_id', 'waiters_id', 'cashier_id', 'payment_id', 'customer', 'total_price', 'is_paid', 'remarks');
        $validator = Validator::make($data, [
            'table_id' => 'nullable|exists:tables,id',
            'area_id' => 'nullable|exists:areas,id',
            'waiters_id' => 'nullable|exists:users,id,role,WAITER',
            'cashier_id' => 'nullable|exists:users,id,role,CASHIER',
            'payment_id' => 'nullable|exists:payment_methods,id',
            'customer' => 'nullable|string',
            'total_price' => 'nullable|numeric',
            'is_paid' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data['updated_by'] = auth()->id();

        if (auth()->user()->hasRole('CASHIER')) {
            $data['cashier_id'] = auth()->id();
        }

        $order->update($data);

        // Update order items
        $orderItems = $request->input('order_items', []);
        foreach ($orderItems as $orderItem) {
            $validator = Validator::make($orderItem, [
                'id' => 'nullable|exists:order_items,id,order_id,' . $order->id,
                'menu_id' => 'nullable|exists:menus,id',
                'quantity' => 'nullable|numeric',
                'paid_quantity' => 'nullable|numeric',
                'is_paid' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $orderItem['updated_by'] = auth()->id();
            if (isset($orderItem['id'])) {
                OrderItem::where('id', $orderItem['id'])->update($orderItem);
            } else {
                $orderItem['order_id'] = $order->id;
                $orderItem['created_by'] = auth()->id();
                OrderItem::create($orderItem);
            }
        }

        $order->load('orderItems.menu', 'table', 'creator', 'updater');

        $tenantIds = $order->orderItems
            ->pluck('menu.tenant_id')
            ->filter()
            ->unique()
            ->toArray();

        $tenants = User::whereHas('userTenants', function ($query) use ($tenantIds) {
            $query->whereIn('tenant_id', $tenantIds);
        })
        ->where('role', 'TENANT')
        ->get();

        Notification::send($tenants, new OrderNotification($order, 'updated'));

        return response()->json([
            'message' => 'Order Updated Success',
            'data' => $order,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->orderItems()->delete();
        $order->delete();

        return response()->json([
            'message' => 'Order Deleted Success'
        ], 200);
    }
}