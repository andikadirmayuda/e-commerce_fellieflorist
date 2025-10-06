<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.sanitized');
        Config::$is3ds = config('midtrans.3ds');
    }

    /**
     * Membuat Snap Token untuk pembayaran
     * @param array $params
     * @return string $snapToken
     */
    public function createSnapToken(array $params)
    {
        return Snap::getSnapToken($params);
    }
}
