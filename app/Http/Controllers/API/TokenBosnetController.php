<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TokenBosnetController extends Controller
{
    public static function signInForSecretKey()
    {
        // Data request yang akan dikirim
        $payload = [
            'appId' => 'BDI.KCP',
            'secretKey' => config('api.secret_key'),
            'bSecretAsKey' => true,
        ];

        // Endpoint tujuan
        $url = 'http://103.54.218.250:3000/API/OC/NGE/v1/SM/Auth/SignInForSecretKey';

        try {
            // Kirim POST request menggunakan Http client Laravel
            $response = Http::post($url, $payload);

            // Periksa response
            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json(),
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Tangani error
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
