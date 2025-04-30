<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

describe('TemplateController', function () {
    beforeEach(function () {
        config()->set('zenvia.token', 'valid_token');
        config()->set('zenvia.url', 'https://api.zenvia.com/v2/templates');
        config()->set('zenvia.sender_phone', '+5511999999999');
        config()->set('zenvia.sender_email', 'test@example.com');
        config()->set('zenvia.channel', 'WHATSAPP');
        Http::fake();
        Log::spy();
    });

    it('envia template com sucesso', function () {
        Http::fake(['*' => Http::response(['message' => 'ok'], 200)]);

        $response = $this->withHeaders([
            'X-API-TOKEN' => config('zenvia.token'),
        ])->postJson('/api/enviar-template', [
            'name' => 'template_basico',
            'text' => 'Olá {{nome}}'
        ]);

        $response->assertStatus(200);
    });

    it('retorna 401 se X-API-TOKEN estiver vazio', function () {
        config()->set('zenvia.token', '');

        $response = $this->withHeaders([
            'X-API-TOKEN' => '',
        ])->postJson('/api/enviar-template', [
            'name' => 'template_basico',
            'text' => 'Olá {{nome}}'
        ]);

        $response->assertStatus(401);
    });

    it('retorna 401 se configuração da Zenvia estiver faltando', function () {
        config()->set('zenvia.token', null);
        config()->set('zenvia.url', null);

        $response = $this->withHeaders([
            'X-API-TOKEN' => 'valid_token',
        ])->postJson('/api/enviar-template', [
            'name' => 'template_basico',
            'text' => 'Olá {{nome}}'
        ]);

        $response->assertStatus(401);
    });

    it('retorna 401 se qualquer configuração obrigatória estiver vazia', function () {
        config()->set('zenvia.token', '');
        config()->set('zenvia.url', '');
        config()->set('zenvia.channel', '');
        config()->set('zenvia.sender_phone', '');
        config()->set('zenvia.sender_email', '');

        $response = $this->withHeaders([
            'X-API-TOKEN' => '',
        ])->postJson('/api/enviar-template', [
            'name' => 'template_falho',
            'text' => 'Mensagem teste'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'erro' => 'Configurações da Zenvia não estão corretamente definidas.'
            ]);
    });

    it('retorna 500 se ocorrer erro inesperado', function () {
        Http::fake(function () {
            throw new Exception('Erro inesperado');
        });

        $response = $this->withHeaders([
            'X-API-TOKEN' => config('zenvia.token'),
        ])->postJson('/api/enviar-template', [
            'name' => 'template_basico',
            'text' => 'Olá {{nome}}'
        ]);

        $response->assertStatus(500);
    });
});
