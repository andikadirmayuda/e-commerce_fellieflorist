<?php

if (!function_exists('format_rupiah')) {
    function format_rupiah($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}

if (!function_exists('app_name')) {
    function app_name()
    {
        return config('app.name');
    }
}

if (!function_exists('app_logo')) {
    function app_logo()
    {
        return asset(config('app.logo'));
    }
}


if (!function_exists('send_fonnte_wa')) {
    function send_fonnte_wa($target, $message)
    {
        $apiKey = env('FONNTE_API_KEY');
        $response = Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => $apiKey
        ])->post('https://api.fonnte.com/send', [
            'target' => $target,
            'message' => $message,
        ]);
        return $response->json();
    }
}
