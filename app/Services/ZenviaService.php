<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZenviaService
{
    public function enviarTemplate(array $templateData): array
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-TOKEN' => config('zenvia.token'),
            ])->post(config('zenvia.url'), $templateData);

            if ($response->failed()) {
                $erro = $response->json('message') ?? 'Erro desconhecido';
                $comentarios = $response->json('comments') ?? [];

                if (!empty($comentarios)) {
                    $ultimo = end($comentarios);
                    $erro .= is_array($ultimo) && isset($ultimo['text']) ? ' — ' . $ultimo['text'] : (string) $ultimo;
                }

                Log::warning('Zenvia retornou erro', [
                    'status' => $response->status(),
                    'erro' => $erro,
                    'dados_enviados' => $templateData
                ]);

                return [null, $response->status(), $erro];
            }

            return [$response->json(), $response->status(), null];

        } catch (ConnectionException $e) {
            Log::error('Falha de conexão com Zenvia', ['mensagem' => $e->getMessage()]);
            return [null, 503, 'Não foi possível conectar ao servidor da Zenvia.'];
        } catch (Throwable $e) {
            Log::critical('Erro inesperado ao enviar template para Zenvia', [
                'mensagem' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [null, 500, 'Erro inesperado ao enviar template.'];
        }
    }

    public function formatarComponentes(string $text, array $buttons): array
    {
        $components = [
            'body' => [
                'type' => 'TEXT_FIXED',
                'text' => $text
            ]
        ];

        if (empty($buttons)) {
            return $components;
        }

        $buttonItems = [];

        foreach ($buttons as $button) {
            if (is_string($button)) {
                if (mb_strlen($button) > 20) {
                    return ['erro' => 'Formato inválido de botão'];
                }

                $buttonItems[] = [
                    'type' => 'QUICK_REPLY',
                    'text' => $button,
                    'payload' => $button
                ];
                continue;
            }

            if (is_array($button) && isset($button['text'], $button['type'])) {
                if (mb_strlen($button['text']) > 20) {
                    return ['erro' => 'Formato inválido de botão'];
                }

                $item = [
                    'type' => $button['type'],
                    'text' => $button['text'],
                    'payload' => $button['text']
                ];

                if ($button['type'] === 'URL') {
                    if (empty($button['url']) || !filter_var($button['url'], FILTER_VALIDATE_URL)) {
                        return ['erro' => 'Formato inválido de botão URL'];
                    }
                    $item['url'] = $button['url'];
                } elseif ($button['type'] === 'PHONE_NUMBER') {
                    if (empty($button['phone_number'])) {
                        continue;
                    }
                    $item['phoneNumber'] = $button['phone_number'];
                }

                $buttonItems[] = $item;
            }
        }

        $components['buttons'] = [
            'type' => 'MIXED',
            'items' => $buttonItems
        ];

        return $components;
    }


    public function gerarExamples(string $text): array
    {
        preg_match_all('/{{(.*?)}}/', $text, $matches);
        $variaveis = $matches[1] ?? [];

        return collect($variaveis)->mapWithKeys(fn($v) => [trim($v) => 'Exemplo'])->toArray();
    }
}
