<?php

use App\Services\ZenviaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

describe('ZenviaServiceHttp', function () {
    beforeEach(function () {
        $this->zenvia = new ZenviaService();
        Log::spy();
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
