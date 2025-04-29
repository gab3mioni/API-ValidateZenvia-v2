<?php

use App\Jobs\VerificarTemplateStatusJob;
use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Log::spy();
});

it('atualiza o status do template se a resposta for bem sucedida', function () {
    $template = Template::factory()->create([
        'status' => 'WAITING_APROVAL',
    ]);

    Http::fake([
        "https://api.zenvia.com/v2/templates/{$template->id}" => Http::response([
            'status' => 'APPROVED',
        ], 200)
    ]);

    VerificarTemplateStatusJob::dispatchSync($template);

    $template->refresh();

    expect($template->status)->toBe('APPROVED');
});

it('não altera o template se a resposta for falha', function () {
    $template = Template::factory()->create([
        'status' => 'WAITING_APROVAL',
    ]);

    Http::fake([
        "https://api.zenvia.com/v2/templates/{$template->id}" => Http::response([], 404)
    ]);

    VerificarTemplateStatusJob::dispatchSync($template);

    $template->refresh();

    expect($template->status)->toBe('WAITING_APROVAL');
});

it('loga erro se uma exceção for lançada durante a requisição', function () {
    $template = Template::factory()->create([
        'status' => 'WAITING_APROVAL',
    ]);

    Http::fake(function () {
        throw new Exception('Falha simulada');
    });

    VerificarTemplateStatusJob::dispatchSync($template);

    Log::shouldHaveReceived('error')->once();
});

it('persiste alterações no banco de dados após execução do job', function () {
    $template = Template::factory()->create([
        'status' => 'PENDING',
    ]);

    Http::fake([
        "https://api.zenvia.com/v2/templates/{$template->id}" => Http::response([
            'status' => 'APPROVED',
        ], 200),
    ]);

    (new VerificarTemplateStatusJob($template))->handle();

    $template->refresh();
    expect($template->status)->toBe('APPROVED');
});
