<?php

namespace App\Services;

use App\Models\ExchangeRateCache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ExchangeRateService
{
    private const CACHE_HOURS = 1;

    public function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $cached = ExchangeRateCache::where('base_currency', $from)
            ->where('target_currency', $to)
            ->first();

        if ($cached && $cached->fetched_at->diffInHours(now()) < self::CACHE_HOURS) {
            return (float) $cached->rate;
        }

        $rate = $this->fetchFromApi($from, $to);

        ExchangeRateCache::updateOrCreate(
            ['base_currency' => $from, 'target_currency' => $to],
            ['rate' => $rate, 'fetched_at' => now()]
        );

        return $rate;
    }

    private function fetchFromApi(string $from, string $to): float
    {
        $apiKey = config('services.exchangerate.key');

        $response = Http::get("https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$from}/{$to}");

        if ($response->failed() || !isset($response->json()['conversion_rate'])) {
            // Fallback: kalau API gagal dan ada cache lama, tetap pakai itu daripada error total
            $fallback = ExchangeRateCache::where('base_currency', $from)
                ->where('target_currency', $to)
                ->first();

            if ($fallback) {
                return (float) $fallback->rate;
            }

            throw new \Exception('Gagal mengambil kurs mata uang dan tidak ada cache tersedia.');
        }

        return (float) $response->json()['conversion_rate'];
    }
}