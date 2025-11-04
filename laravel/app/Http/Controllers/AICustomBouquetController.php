<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AICustomBouquetController extends Controller
{
    // Rekomendasi bunga berdasarkan input user
    public function recommend(Request $request)
    {
        $request->validate([
            'event' => 'required|string',
            'color' => 'required|string',
            'style' => 'required|string',
        ]);

        $prompt = "Kamu adalah florist profesional. Berdasarkan acara '" . $request->event . "', warna '" . $request->color . "', dan gaya '" . $request->style . "', rekomendasikan jenis bunga yang cocok.";

        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);

        if ($response->failed()) {
            return response()->json(['success' => false, 'message' => 'Gagal mendapatkan rekomendasi AI'], 500);
        }

        $result = $response->json();
        $recommendation = $result['choices'][0]['message']['content'] ?? '';

        return response()->json([
            'success' => true,
            'recommendation' => $recommendation,
        ]);
    }

    // Generate ucapan bunga otomatis
    public function generateMessage(Request $request)
    {
        $request->validate([
            'event' => 'required|string',
            'style' => 'required|string',
        ]);

        $prompt = "Tulis ucapan bunga singkat dan hangat untuk acara '" . $request->event . "' dengan gaya '" . $request->style . "' dalam bahasa Indonesia. Tambahkan sedikit emoji bunga.";

        $response = Http::withToken(config('services.openai.key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 80,
                'temperature' => 0.8,
            ]);

        if ($response->failed()) {
            return response()->json(['success' => false, 'message' => 'Gagal membuat ucapan AI'], 500);
        }

        $result = $response->json();
        $message = $result['choices'][0]['message']['content'] ?? '';

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
