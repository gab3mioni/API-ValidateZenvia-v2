<?php

namespace App\Console\Commands;

use App\Services\TemplateService;
use Illuminate\Console\Command;
use App\Models\Template;
use App\Jobs\VerificarTemplateStatusJob;

class VerificarTemplates extends Command
{
    protected $signature = 'verificar:templates';
    protected $description = 'Verifica o status dos templates pendentes na Zenvia';

    private TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        parent::__construct();
        $this->templateService = $templateService;
    }

    public function handle(): int
    {
        try {
            $templates = $this->templateService->buscarTemplatesAguardandoAprovacao();
        } catch (\Throwable $e) {
            $this->error('Erro ao buscar templates: ' . $e->getMessage());
            return 1;
        }

        if ($templates->isEmpty()) {
            $this->info('Nenhum template aguardando aprovação.');
            return 0;
        }

        foreach ($templates as $template) {
            VerificarTemplateStatusJob::dispatch($template);
        }

        $this->info('Jobs para verificação de templates foram enviados para a fila.');
        return 0;
    }
}
