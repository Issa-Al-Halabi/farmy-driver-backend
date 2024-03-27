<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Driver;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use PDF;

class DriverService
{
    public function getDriverDues($driver_id)
    {
        $dues = Order::where('driver_id', $driver_id)->sum('delivery_fee');

        $orders = Order::where([['driver_id', $driver_id], ['status', OrderStatus::Deliverd]])
            ->orderBy('created_at', 'Desc')
            ->take(5)
            ->select('order_number', 'created_at', 'total')->get();
        return  ['driver_dues' => $dues, 'orders' => $orders];
    }

    public function acceptOrderByDriver($order, $driver_id)
    {
        if ($order->driver_id == null && $order->status == OrderStatus::Pending) {

            $order->driver_id = $driver_id;
            $order->status =  OrderStatus::Confirmed;
            $order->save();

            return  true;
        }

        return  false;
    }

    public function generatePdfAllOrdersForDriver($driver_id)
    {

        $time = now();
        $orders = Order::where('driver_id', $driver_id)->orderBy('created_at', "desc")->select('order_number', 'created_at', 'total')->get()->toArray();

        $pdf = PDF::loadView('inv', ['data' => $orders]);
        // $repository = 'storage/app/public/ordesPdf'; //comment this line for 000webhost
        // if (!File::exists($repository)) {
        //     File::makeDirectory($repository, 0777, true);
        // }
        // $fileName = 'orders.pdf';



        Storage::put('public/pdf/' . $time . '.pdf', $pdf->output());

        return $pdf->download('invoice.pdf');


        // $filePath = $repository . Carbon::now()->format('Y_m_d_u') . '_' . $fileName;

        // $pdf->
        // // return $pdf->download( $filePath);

    }

    public function updateDriverInfo($driver_id, $data)
    {

        $res = [];
        $driver = Driver::find($driver_id);
        if ($driver) {
            $driver->first_name = $data['first_name'];
            $driver->last_name =  $data['last_name'];
            $driver->save();
            $res['status'] = true;
            $res['message'] = "success";
            return  $res;
        } else {
            $res['status'] = false;
            $res['message'] = "driver not found";
            return $res;
        }
    }

    public function getLastFiveOrdersPending()
    {
        $orders = Order::where('status', OrderStatus::Pending)
            ->orderBy('created_at', 'Desc')
            ->take(5)->with('userAddress')->get();

        $data =  $orders->map(function ($order) {
            $hour = Carbon::parse($order->created_at)->hour;
            $time = $hour >= 12 ? "PM " . Carbon::parse($order->created_at)->subHours(12)->format('H:i')
                : "AM " . Carbon::parse($order->created_at)->format('H:i');

            return [
                'order_number' => $order->order_number,
                'location' => $order->userAddress ? $order->userAddress->address : null,
                'status' => $order->status,
                'date' =>  Carbon::parse($order->created_at)->format('d/m/y'),
                'time' => $time,
            ];
        });

        return $data;
    }
    public function getDriverOrders($driver_id)
    {
        $orders = Order::where([['driver_id', $driver_id], ['status', [OrderStatus::Confirmed, OrderStatus::OnDelivery]]])
            ->orderBy('created_at', 'Desc')
            ->get();
        return OrderResource::collection($orders);
    }
}
