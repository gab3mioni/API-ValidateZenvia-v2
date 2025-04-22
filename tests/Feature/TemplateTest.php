<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake(); // Evita chamadas reais à API Zenvia
});

it('envia template válido com dados mínimos', function () {
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $response = $this->postJson('/api/enviar-template', [
        'name' => 'template_basico',
        'text' => 'Texto simples'
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
});

it('envia template com botão string simples', function () {
    Http::fake(['*' => Http::response(['sent' => true], 200)]);

    $response = $this->postJson('/api/enviar-template', [
        'name' => 'template_com_botao_string',
        'text' => 'Escolha uma opção:',
        'buttons' => ['Confirmar']
    ]);

    $response->assertStatus(200)
        ->assertJson(['sent' => true]);
});

it('envia template com botão tipo URL', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $response = $this->postJson('/api/enviar-template', [
        'name' => 'template_url',
        'text' => 'Clique no botão',
        'buttons' => [[
            'type' => 'URL',
            'text' => 'Site',
            'url' => 'https://exemplo.com'
        ]]
    ]);

    $response->assertStatus(200)
        ->assertJson(['ok' => true]);
});

it('envia template com botão tipo PHONE_NUMBER', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $response = $this->postJson('/api/enviar-template', [
        'name' => 'template_fone',
        'text' => 'Clique para ligar',
        'buttons' => [[
            'type' => 'PHONE_NUMBER',
            'text' => 'Ligar',
            'phone_number' => '+5511988888888'
        ]]
    ]);

    $response->assertStatus(200)
        ->assertJson(['ok' => true]);
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

it('retorna erro se Zenvia responder com falha', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Token inválido'], 401),
    ]);

    $response = $this->postJson('/api/enviar-template', [
        'name' => 'template_erro',
        'text' => 'Teste falha'
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'erro' => 'Falha ao enviar template',
            'detalhes' => 'Token inválido'
        ]);
});
