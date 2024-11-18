<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KcpInformation extends Model
{
    use HasFactory;

    public function login()
    {
        try {
            $response = Http::timeout(60)->asForm()->post('http://36.91.145.235/kcpapi/auth/login', [
                'username' => config('api.username'),
                'password' => config('api.password'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getNonAopParts($token)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get('http://36.91.145.235/kcpapi/api/master-part/non-aop');

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

    public function getIntransitBySpb($token, $spb)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Accept' => 'application/json',
            ])
                ->timeout(60)
                ->get("http://36.91.145.235/kcpapi/api/intransit/$spb");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Failed to get LKH data", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Exception occurred while fetching LKH data", [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function getInvoices($token)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get("http://36.91.145.235/kcpapi/api/sales-order");

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

    public function getInvoice($token, $invoice)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get("http://36.91.145.235/kcpapi/api/sales-order/detail/$invoice");

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

    public function getAddress($token, $kdToko)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get("36.91.145.235/kcpapi/api/sales-order/address/$kdToko");

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }


    public function getLkh($token)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Accept' => 'application/json',
            ])
                ->timeout(60)
                ->get("http://36.91.145.235/kcpapi/api/sales-order/lkh");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Failed to get LKH data", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Exception occurred while fetching LKH data", [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }


    public function getLkhHeader($token, $lkh)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get("36.91.145.235/kcpapi/api/sales-order/lkh-header/$lkh");

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

    public function getBonusHeader($token)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get("36.91.145.235/kcpapi/api/sales-order/bonus-header");

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }

    public function getBonusDetail($token, $idBonus)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
        ])->get("36.91.145.235/kcpapi/api/sales-order/bonus-detail/$idBonus");

        if ($response->successful()) {
            return $response->json();
        }

        return false;
    }
}
