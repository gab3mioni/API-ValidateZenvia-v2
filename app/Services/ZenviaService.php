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
                'X-API-TOKEN' => config('Zenvia.token'),
            ])->post(config('Zenvia.url'), $templateData);

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

        }

    }
}
