<?php

use App\Services\ZenviaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(Tests\TestCase::class);

describe('ZenviaService', function () {
    beforeEach(function () {
        $this->zenvia = new ZenviaService();
        Log::spy();
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

    it('trata falha de conexão', function () {
        Http::fake(function () {
            throw new Illuminate\Http\Client\ConnectionException('Falha');
        });

        [$resultado, $status, $erro] = $this->zenvia->enviarTemplate(['dados' => 'teste']);
        expect($status)->toBe(503)
            ->and($erro)->toBe('Não foi possível conectar ao servidor da Zenvia.');
    });

    it('trata resposta HTTP com erro', function () {
        Http::fake([
            '*' => Http::response(['message' => 'Erro', 'comments' => [['text' => 'Detalhe']]], 400)
        ]);

        [$resultado, $status, $erro] = $this->zenvia->enviarTemplate(['dados' => 'teste']);
        expect($status)->toBe(400)
            ->and($erro)->toContain('Erro')
            ->and($erro)->toContain('Detalhe');
    });

    it('retorna sucesso quando a resposta for OK', function () {
        Http::fake([
            '*' => Http::response(['message' => 'enviado_com_sucesso'], 200)
        ]);

        [$resultado, $status, $erro] = $this->zenvia->enviarTemplate(['dados' => 'teste']);
        expect($status)->toBe(200)
            ->and($erro)->toBeNull();
    });
});
