<?php

use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Jobs\VerificarTemplateStatusJob;
use App\Models\Template;

uses(TestCase::class, RefreshDatabase::class);

it('atribui corretamente o template no construtor', function () {
    $template = Template::factory()->make();
    $job = new VerificarTemplateStatusJob($template);

    expect($job->getTemplate())->toBe($template);
});

it('atualiza status e mantém motivo de rejeição nulo quando aprovado', function () {
    $template = Template::factory()->create([
        'status'          => 'PENDING',
        'motivo_rejeicao' => null,
    ]);

    Http::fake([
        "https://api.zenvia.com/v2/templates/{$template->id}" => Http::response([
            'status'   => 'APPROVED',
            'comments' => [],
        ], 200),
    ]);

    (new VerificarTemplateStatusJob($template))->handle();

    $template->refresh();
    expect($template->status)->toBe('APPROVED')
        ->and($template->motivo_rejeicao)->toBeNull();
});

it('atualiza status e define motivo de rejeição quando rejeitado', function () {
    $template = Template::factory()->create([
        'status'          => 'PENDING',
        'motivo_rejeicao' => null,
    ]);

    $expectedComment = 'Formato inválido';

    Http::fake([
        "https://api.zenvia.com/v2/templates/{$template->id}" => Http::response([
            'status'   => 'REJECTED',
            'comments' => [
                ['text' => 'Comentário inicial'],
                ['text' => $expectedComment],
            ],
        ], 200),
    ]);

    (new VerificarTemplateStatusJob($template))->handle();

    $template->refresh();
    expect($template->status)->toBe('REJECTED')
        ->and($template->motivo_rejeicao)->toBe($expectedComment);
});

it('gera log de aviso ao receber resposta com erro', function () {
    Log::spy();
    $template = Template::factory()->create();

    Http::fake([
        "https://api.zenvia.com/v2/templates/{$template->id}" => Http::response([], 500),
    ]);

    (new VerificarTemplateStatusJob($template))->handle();

    Log::shouldHaveReceived('warning')->once();
});

it('gera log de erro ao lançar exceção', function () {
    Log::spy();
    $template = Template::factory()->create();

    Http::fake(fn () => throw new \Exception('Erro simulado'));

    (new VerificarTemplateStatusJob($template))->handle();

    Log::shouldHaveReceived('error')->once();
});

it('não quebra se resposta da API não tiver campo status', function () {
    $template = Template::factory()->create([
        'status' => 'WAITING_APROVAL',
    ]);

    Http::fake([
        "https://api.zenvia.com/v2/templates/{$template->id}" => Http::response([
            'other_field' => 'unexpected_value',
        ], 200),
    ]);

    (new VerificarTemplateStatusJob($template))->handle();

    $template->refresh();
    expect($template->status)->toBe('WAITING_APROVAL'); // Status não muda
});
