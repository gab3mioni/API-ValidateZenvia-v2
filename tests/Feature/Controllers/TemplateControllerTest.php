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

    it('envia template válido com dados mínimos', function () {
        Http::fake(['*' => Http::response(['message' => 'enviado_com_sucesso'])]);

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_basico',
            'text' => 'Olá {{nome}}'
        ]);

        $response->assertStatus(200);
    });


        $response->assertStatus(422)
            ->assertJson([
                'erro' => 'Formato inválido de botão'
            ]);
    });

    it('retorna erro com comentário detalhado da Zenvia', function () {
        Http::fake(['*' => Http::response([
            'message' => 'Template rejeitado',
            'comments' => [['text' => 'Texto inadequado para o canal']]
        ], 400)]);

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_rejeitado',
            'text' => 'Texto ofensivo?'
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'erro' => 'Falha ao enviar template'
            ]);
    });

    it('envia template mesmo com buttons sendo array vazio', function () {
        Http::fake(['*' => Http::response(['message' => 'ok'], 200)]);

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_sem_botoes',
            'text' => 'Mensagem simples',
            'buttons' => []
        ]);

        $response->assertStatus(200);
    });

    it('log de erro crítico é registrado em exceção inesperada', function () {
        Http::fake(function () {
            throw new Exception('Exceção inesperada');
        });

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'quebra',
            'text' => 'Erro no servidor'
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'erro' => 'Falha ao enviar template',
                'detalhes' => 'Erro inesperado ao enviar template.'
            ]);

        Log::shouldHaveReceived('critical')->once();
    });
});
