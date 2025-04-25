<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

describe('TemplateController', function () {
    beforeEach(function () {
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

    it('envia template com botão string simples', function () {
        Http::fake(['*' => Http::response(['result' => 'ok'])]);

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_com_botao_string',
            'text' => 'Escolha uma opção:',
            'buttons' => ['Confirmar']
        ]);

        $response->assertStatus(200);
    });

    it('envia template com botão tipo URL', function () {
        Http::fake(['*' => Http::response(['status' => 'ok'])]);

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_url',
            'text' => 'Clique no botão',
            'buttons' => [[
                'type' => 'URL',
                'text' => 'Site',
                'url' => 'https://exemplo.com'
            ]]
        ]);

        $response->assertStatus(200);
    });

    it('envia template com botão tipo PHONE_NUMBER', function () {
        Http::fake(['*' => Http::response(['message' => 'ok'])]);

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_phone',
            'text' => 'Ligue para nós',
            'buttons' => [[
                'type' => 'PHONE_NUMBER',
                'text' => 'Ligar',
                'phone_number' => '+5511999999999'
            ]]
        ]);

        $response->assertStatus(200);
    });

    it('envia template com múltiplos botões de tipos diferentes', function () {
        Http::fake(['*' => Http::response(['message' => 'ok'])]);

        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_misto',
            'text' => 'Escolha uma opção:',
            'buttons' => [
                'Confirmar',
                [
                    'type' => 'URL',
                    'text' => 'Site',
                    'url' => 'https://exemplo.com'
                ],
                [
                    'type' => 'PHONE_NUMBER',
                    'text' => 'Ligar',
                    'phone_number' => '+5511999999999'
                ]
            ]
        ]);

        $response->assertStatus(200);
    });

    it('falha se name estiver ausente', function () {
        $response = $this->postJson('/api/enviar-template', [
            'text' => 'Faltando name'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    });

    it('falha se text estiver ausente', function () {
        $response = $this->postJson('/api/enviar-template', [
            'name' => 'sem_text'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text']);
    });

    it('falha se buttons não for array', function () {
        $response = $this->postJson('/api/enviar-template', [
            'name' => 'template_invalido',
            'text' => 'Teste de buttons',
            'buttons' => 'isso_nao_e_um_array'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['buttons']);
    });

    it('falha se botão estiver malformado', function () {
        $response = $this->postJson('/api/enviar-template', [
            'name' => 'botao_invalido',
            'text' => 'Teste com botão malformado',
            'buttons' => [[ 'type' => 'URL' ]] // faltando 'text'
        ]);

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
