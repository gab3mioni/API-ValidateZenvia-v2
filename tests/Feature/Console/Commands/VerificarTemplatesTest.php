<?php

use App\Models\Template;
use App\Jobs\VerificarTemplateStatusJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
});

it('despacha jobs para templates com status WAITING_APROVAL', function () {
    Queue::fake();

    $template = Template::factory()->create([
        'status' => 'WAITING_APROVAL',
    ]);

    $this->artisan('verificar:templates')
        ->expectsOutput('Jobs para verificação de templates foram enviados para a fila.')
        ->assertExitCode(0);

    Queue::assertPushed(VerificarTemplateStatusJob::class, function ($job) use ($template) {
        return $job->getTemplate()->id === $template->id;
    });
});

it('não despacha jobs se não houver templates pendentes', function () {
    Queue::fake();

    $this->artisan('verificar:templates')
        ->expectsOutput('Nenhum template aguardando aprovação.')
        ->assertExitCode(0);

    Queue::assertNotPushed(VerificarTemplateStatusJob::class);
});

it('exibe mensagem se não houver templates aguardando aprovação', function () {
    $this->artisan('verificar:templates')
        ->expectsOutput('Nenhum template aguardando aprovação.')
        ->assertExitCode(0);

    Queue::assertNothingPushed();
});

it('despacha jobs para cada template pendente', function () {
    $template1 = Template::factory()->create(['status' => 'WAITING_APROVAL']);
    $template2 = Template::factory()->create(['status' => 'WAITING_APROVAL']);

    $this->artisan('verificar:templates')
        ->expectsOutput('Jobs para verificação de templates foram enviados para a fila.')
        ->assertExitCode(0);

    Queue::assertPushed(VerificarTemplateStatusJob::class, 2);
    Queue::assertPushed(VerificarTemplateStatusJob::class, function ($job) use ($template1) {
        return $job->getTemplate()->id === $template1->id;
    });
    Queue::assertPushed(VerificarTemplateStatusJob::class, function ($job) use ($template2) {
        return $job->getTemplate()->id === $template2->id;
    });
});
