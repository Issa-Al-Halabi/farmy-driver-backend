<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Helpers\AuthHelper;
use App\Http\Resources\OrderDetailResource;
use App\Models\OrderDetail;

class OrderDetailService
{
    public function getDriverOrders()
    {
        $driver_id = AuthHelper::userAuth()->id;

        $orders = Order::where([['driver_id', $driver_id], ['status', [OrderStatus::Confirmed, OrderStatus::OnDelivery]]])
            ->orderBy('created_at', 'Desc')
            ->get();

        return OrderResource::collection($orders);
    }
    public function getDriverOrderDetail($order_id)
    {
        $orders = Order::where("id", $order_id)
            ->orderBy('created_at', 'Desc')
            ->get();

        return OrderDetailResource::collection($orders);
    }
    public function updateDriverOrderDetail($order_id, $request)
    {
        OrderDetail::whereIn("id", $request->approved_products_ids)
            ->update([
                "status" => 1
            ]);
        OrderDetail::whereIn("id", $request->canceled_products_ids)
            ->update([
                "status" => 0
            ]);
    }
}