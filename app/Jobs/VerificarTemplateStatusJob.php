<?php

namespace App\Jobs;

use App\Models\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerificarTemplateStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Template $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function getTemplate(): Template
    {
        return $this->template;
    }

    public function handle(): void
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-TOKEN' => config('zenvia.token'),
            ])->get("https://api.zenvia.com/v2/templates/{$this->template->id}");

            if ($response->successful()) {
                $data = $response->json();

                $motivoRejeicao = null;
                if (!empty($data['comments']) && is_array($data['comments'])) {
                    $ultimoComentario = end($data['comments']);
                    if (isset($ultimoComentario['text'])) {
                        $motivoRejeicao = $ultimoComentario['text'];
                    }
                }

                $this->template->update([
                    'status' => $data['status'] ?? $this->template->status,
                    'motivo_rejeicao' => $motivoRejeicao,
                ]);
            } else {
                Log::warning('Erro ao consultar template Zenvia', [
                    'id' => $this->template->id,
                    'status_code' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Falha ao processar template', [
                'id' => $this->template->id,
                'mensagem' => $e->getMessage(),
            ]);
        }
    }

}
