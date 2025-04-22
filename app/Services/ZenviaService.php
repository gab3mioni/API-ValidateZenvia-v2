<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ZenviaService
{
    public function enviarTemplate(array $templateData): array
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-TOKEN' => env('ZENVIA_TOKEN')
        ])->post(env('ZENVIA_URL', 'https://api.zenvia.com/v2/templates'), $templateData);

        if ($response->failed()) {
            return [null, $response->status(), $response->json('message') ?? 'Erro desconhecido'];
        }

        return [$response->json(), $response->status(), null];
    }
}
