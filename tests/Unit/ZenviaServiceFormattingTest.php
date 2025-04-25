<?php

use App\Services\ZenviaService;

describe('ZenviaServiceFormatting', function () {
    beforeEach(function () {
        $this->zenvia = new ZenviaService();
    });

    it('monta componentes com botão simples', function () {
        $components = $this->zenvia->formatarComponentes('Mensagem', ['Confirmar']);
        expect($components['buttons']['items'][0]['type'])->toBe('QUICK_REPLY');
    });

    it('monta componentes com botão tipo URL', function () {
        $components = $this->zenvia->formatarComponentes('Mensagem', [
            ['type' => 'URL', 'text' => 'Site', 'url' => 'https://site.com']
        ]);
        expect($components['buttons']['items'][0]['url'])->toBe('https://site.com');
    });

    it('monta componentes com botão tipo PHONE_NUMBER', function () {
        $components = $this->zenvia->formatarComponentes('Mensagem', [
            ['type' => 'PHONE_NUMBER', 'text' => 'Ligar', 'phone_number' => '+5511999999999']
        ]);
        expect($components['buttons']['items'][0]['phoneNumber'])->toBe('+5511999999999');
    });

    it('gera examples a partir do texto', function () {
        $examples = $this->zenvia->gerarExamples('Olá {{nome}}, pedido {{pedido}} enviado.');
        expect($examples)->toHaveKeys(['nome', 'pedido']);
    });

    it('retorna erro se botão string ultrapassar 20 caracteres', function () {
        $buttons = [
            'Este texto é muito longo para ser aceito',
        ];

        $components = $this->zenvia->formatarComponentes('Mensagem', $buttons);

        expect($components)->toBe(['erro' => 'Formato inválido de botão']);
    });

    it('retorna erro se botão array ultrapassar 20 caracteres', function () {
        $buttons = [
            [
                'type' => 'URL',
                'text' => 'Texto com mais de vinte caracteres',
                'url' => 'https://exemplo.com'
            ]
        ];

        $components = $this->zenvia->formatarComponentes('Mensagem', $buttons);

        expect($components)->toBe(['erro' => 'Formato inválido de botão']);
    });

    it('retorna erro se URL do botão for inválida', function () {
        $buttons = [
            [
                'type' => 'URL',
                'text' => 'Site',
                'url' => 'isso_nao_e_uma_url'
            ]
        ];

        $components = $this->zenvia->formatarComponentes('Mensagem', $buttons);

        expect($components)->toBe(['erro' => 'Formato inválido de botão URL']);
    });

    it('retorna erro se URL do botão for inválida (contendo https no POST)', function () {
        $buttons = [
            [
                'type' => 'URL',
                'text' => 'Site',
                'url' => 'https...'
            ]
        ];

        $components = $this->zenvia->formatarComponentes('Mensagem', $buttons);

        expect($components)->toBe(['erro' => 'Formato inválido de botão URL']);
    });

});
