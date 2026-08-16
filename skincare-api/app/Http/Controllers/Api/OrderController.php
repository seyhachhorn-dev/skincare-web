<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()->with('items.product')->latest()->get();

        return $this->respond(OrderResource::collection($orders), 'Orders retrieved successfully');
    }

    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->placeOrder(
            $request->user(),
            $request->validated('address_id'),
            $request->validated('payment_method'),
            $request->validated('shipping_method'),
        );

        return $this->respond(new OrderResource($order), 'Order placed successfully', 201);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return $this->respond(new OrderResource($order->load(['items.product', 'address'])));
    }

    /**
     * Dashboard data. This route is admin-only (see routes/api.php), so it
     * intentionally includes the customer and shipping address alongside
     * the order data.
     */
    public function adminIndex(): JsonResponse
    {
        $orders = Order::query()
            ->with(['user', 'items.product', 'address'])
            ->latest()
            ->get();

        return $this->respond(OrderResource::collection($orders), 'Orders retrieved successfully');
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->updateFulfillmentStatus(
            $order,
            $request->validated('status'),
        );

        return $this->respond(new OrderResource($order), 'Order status updated successfully');
    }
}
