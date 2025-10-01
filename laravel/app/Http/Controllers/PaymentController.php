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
        $params = [
            'transaction_details' => [
                'order_id' => $order->public_code,
                'gross_amount' => $request->amount ?? ($order->total_amount ?? 0),
            ],
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email' => $order->wa_number ? $order->wa_number . '@fellieflorist.com' : ($request->email ?? 'customer@fellieflorist.com'),
            ],
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

        // Ambil payload dari Midtrans
        $payload = $request->all();

        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $paymentType = $payload['payment_type'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (!$orderId) {
            return response()->json(['error' => 'Order ID tidak ditemukan'], 400);
        }

        // Validasi signature Midtrans
        $serverKey = config('midtrans.server_key');
        $localSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if ($signatureKey !== $localSignature) {
            Log::warning('Signature Midtrans tidak valid', [
                'order_id' => $orderId,
                'signature_key' => $signatureKey,
                'local_signature' => $localSignature,
            ]);
            return response()->json(['error' => 'Signature tidak valid'], 403);
        }

        // Cari order di database (gunakan public_code atau id sesuai implementasi)
        $order = \App\Models\PublicOrder::where('public_code', $orderId)->first();
        if (!$order) {
            // Coba cari berdasarkan id jika public_code tidak ditemukan
            $order = \App\Models\PublicOrder::find($orderId);
        }
        if (!$order) {
            return response()->json(['error' => 'Order tidak ditemukan'], 404);
        }

        // Mapping status Midtrans ke status pembayaran di sistem
        $statusMap = [
            'settlement' => 'paid',
            'capture' => 'paid',
            'pending' => 'waiting_payment',
            'deny' => 'rejected',
            'cancel' => 'cancelled',
            'expire' => 'cancelled',
        ];
        $newPaymentStatus = $statusMap[$transactionStatus] ?? 'waiting_payment';

        $order->payment_status = $newPaymentStatus;
        if ($newPaymentStatus === 'paid') {
            // Untuk status lunas, set amount_paid = total pesanan
            $totalOrder = $order->items()->sum(DB::raw('quantity * price'));
            $order->amount_paid = $totalOrder;
            $order->status = 'confirmed';
        }
        // Simpan data transaksi (opsional, tambahkan field jika perlu)
        $order->midtrans_transaction_id = $transactionId;
        $order->midtrans_payment_type = $paymentType;
        $order->save();

        // Log untuk audit
        Log::info('Midtrans callback processed', [
            'order_id' => $orderId,
            'transaction_status' => $transactionStatus,
            'payment_status' => $newPaymentStatus,
            'transaction_id' => $transactionId,
            'payment_type' => $paymentType,
        ]);

        return response()->json(['status' => 'success', 'payment_status' => $newPaymentStatus]);
    }
}
