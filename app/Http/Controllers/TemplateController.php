<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateRequest;
use App\Services\ZenviaService;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    private ZenviaService $zenvia;

    public function __construct(ZenviaService $zenvia)
    {
        $this->zenvia = $zenvia;
    }

    public function enviar(TemplateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $templateData = [
            'name' => $data['name'],
            'locale' => 'pt_BR',
            'channel' => env('CHANNEL', 'WHATSAPP'),
            'senderId' => env('SENDER_PHONE'),
            'notificationEmail' => env('SENDER_EMAIL'),
            'category' => 'UTILITY',
            'components' => [
                'body' => [
                    'type' => 'TEXT_FIXED',
                    'text' => $data['text']
                ]
            ]
        ];

        if (!empty($data['buttons'])) {
            $buttonItems = [];

            foreach ($data['buttons'] as $button) {
                if (is_string($button)) {
                    $buttonItems[] = [
                        'type' => 'QUICK_REPLY',
                        'text' => $button,
                        'payload' => $button
                    ];
                } elseif (is_array($button) && isset($button['text'], $button['type'])) {
                    $item = [
                        'type' => $button['type'],
                        'text' => $button['text'],
                        'payload' => $button['text']
                    ];

                    if ($button['type'] === 'URL' && isset($button['url'])) {
                        $item['url'] = $button['url'];
                    } elseif ($button['type'] === 'PHONE_NUMBER' && isset($button['phone_number'])) {
                        $item['phoneNumber'] = $button['phone_number'];
                    }

                    $buttonItems[] = $item;
                }
            }

            $templateData['components']['buttons'] = [
                'type' => 'MIXED',
                'items' => $buttonItems
            ];
        }

        [$resultado, $status, $erro] = $this->zenvia->enviarTemplate($templateData);

        if ($erro) {
            return response()->json(['erro' => 'Falha ao enviar template', 'detalhes' => $erro], $status);
        }

        return response()->json($resultado, 200);
    }
}
