<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Snap;
use Midtrans\Config;

class PaymentController extends Controller
{
    /**
     * Webhook Midtrans untuk notifikasi status pembayaran
     */
    public function notification(Request $request)
    {
        $serverKey = config('services.midtrans.server_key');
        $signatureKey = $request->signature_key;
        $orderId = $request->order_id;
        $statusCode = $request->status_code;
        $grossAmount = $request->gross_amount;
        $transactionStatus = $request->transaction_status;
        $paymentType = $request->payment_type;

        // Validasi signature (opsional, best practice)
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($signatureKey !== $expectedSignature) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // Update status pembayaran di database
        $order = \App\Models\PublicOrder::where('public_code', $orderId)->first();
        if ($order) {
            if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
                $order->payment_status = 'paid';
            } elseif ($transactionStatus === 'pending') {
                $order->payment_status = 'pending';
            } elseif ($transactionStatus === 'cancel' || $transactionStatus === 'expire' || $transactionStatus === 'deny') {
                $order->payment_status = 'failed';
            }
            $order->save();
        }

        return response()->json(['message' => 'Notification processed']);
    }

    public function createSnapTransaction(Request $request)
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Data transaksi
        $params = [
            'transaction_details' => [
                'order_id' => uniqid(),
                'gross_amount' => $request->amount, // nominal pembayaran
            ],
            'customer_details' => [
                'first_name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
