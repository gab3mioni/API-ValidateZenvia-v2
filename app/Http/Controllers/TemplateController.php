<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateRequest;
use App\Services\ZenviaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class TemplateController extends Controller
{
    private ZenviaService $zenvia;

    public function __construct(ZenviaService $zenvia)
    {
        $this->zenvia = $zenvia;
    }

    public function enviar(TemplateRequest $request): JsonResponse
    {

        if (
            !config('zenvia.token') ||
            !config('zenvia.url') ||
            !config('zenvia.channel') ||
            !config('zenvia.sender_phone') ||
            !config('zenvia.sender_email')
        ) {
            return response()->json([
                'erro' => 'Configurações da Zenvia não estão corretamente definidas.'
            ], 401);
        }

        try {
            $data = $request->validated();
            $templateData = $this->montarTemplateData($data);

            [$resultado, $status, $erro] = $this->zenvia->enviarTemplate($templateData);

            if ($erro) {
                Log::error('Erro ao enviar template para Zenvia', compact('erro', 'templateData'));
                return response()->json([
                    'erro' => 'Falha ao enviar template',
                    'detalhes' => $erro
                ], $status ?? 500);
            }

            return response()->json($resultado, 200);

        } catch (Throwable $e) {
            Log::critical('Erro inesperado ao processar envio de template', [
                'mensagem' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'erro' => 'Erro interno do servidor',
                'mensagem' => 'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.'
            ], 500);
        }
    }

    private function configNotValid($value): bool
    {
        return empty($value) || trim($value) === '';
    }

    private function montarTemplateData(array $data): array
    {
        $components = $this->zenvia->formatarComponentes($data['text'], $data['buttons'] ?? []);
        $examples = $this->zenvia->gerarExamples($data['text']);

        return [
            'name' => $data['name'],
            'locale' => 'pt_BR',
            'channel' => config('zenvia.channel'),
            'senderId' => config('zenvia.sender_phone'),
            'notificationEmail' => config('zenvia.sender_email'),
            'category' => 'UTILITY',
            'components' => $components,
            'examples' => $examples
        ];
    }

}
