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

});
