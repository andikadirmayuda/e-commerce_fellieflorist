<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function getSnapToken(Request $request)
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.sanitized');
        Config::$is3ds = config('midtrans.3ds');

        // Validasi public_code dan order
        $publicCode = $request->input('order_id');
        if (!$publicCode) {
            return response()->json(['error' => 'order_id (public_code) wajib dikirim'], 400);
        }
        $order = \App\Models\PublicOrder::where('public_code', $publicCode)->first();
        if (!$order) {
            return response()->json(['error' => 'Order tidak ditemukan'], 404);
        }

        // Data transaksi
        // Ambil detail item dari relasi order->items (pastikan relasi sudah ada di model PublicOrder)
        $itemDetails = [];
        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' => $item->product_id ?? $item->id,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'name' => $item->product_name ?? $item->name,
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id' => $order->public_code,
                'gross_amount' => $request->amount ?? ($order->total_amount ?? 0),
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'last_name' => $order->receiver_name ?? '',
                'email' => $order->wa_number ? $order->wa_number . '@fellieflorist.com' : ($request->email ?? 'customer@fellieflorist.com'),
                'phone' => $order->wa_number ?? ($request->phone ?? ''),
                'shipping_address' => [
                    'address' => $order->destination ?? '',
                    'city' => $order->destination_city ?? '',
                    'postal_code' => $order->destination_postal_code ?? '',
                    'country_code' => 'IDN',
                ],
            ],
            'item_details' => $itemDetails,
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Endpoint untuk menerima callback dari Midtrans
    public function callback(Request $request)
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.sanitized');
        Config::$is3ds = config('midtrans.3ds');

        $notif = new \Midtrans\Notification();

        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $order_id = $notif->order_id;
        $fraud = $notif->fraud_status;

        // Cari order di database
        $order = \App\Models\PublicOrder::where('public_code', $order_id)->first();
        if (!$order) {
            $order = \App\Models\PublicOrder::find($order_id);
        }
        if (!$order) {
            Log::warning('Order tidak ditemukan untuk callback Midtrans', ['order_id' => $order_id]);
            return response()->json(['error' => 'Order tidak ditemukan'], 404);
        }

        // Mapping status Midtrans ke status pembayaran di sistem
        $statusMap = [
            'capture'    => ($type == 'credit_card' && $fraud == 'accept') ? 'paid' : 'waiting_payment',
            'settlement' => 'paid',
            'pending'    => 'waiting_payment',
            'deny'       => 'rejected',
            'expire'     => 'cancelled',
            'cancel'     => 'cancelled',
        ];
        $newPaymentStatus = $statusMap[$transaction] ?? 'waiting_payment';

        $order->payment_status = $newPaymentStatus;
        if ($newPaymentStatus === 'paid') {
            $totalOrder = $order->items()->sum(DB::raw('quantity * price'));
            $order->amount_paid = $totalOrder;
            $order->status = 'confirmed';
        }
        $order->midtrans_transaction_id = $notif->transaction_id ?? null;
        $order->midtrans_payment_type = $type;
        $order->save();

        Log::info('Midtrans callback processed', [
            'order_id' => $order_id,
            'transaction_status' => $transaction,
            'payment_status' => $newPaymentStatus,
            'payment_type' => $type,
        ]);

        return response()->json([
            'status' => 'success',
            'payment_status' => $newPaymentStatus,
            'order_id' => $order_id,
        ]);
    }
}
