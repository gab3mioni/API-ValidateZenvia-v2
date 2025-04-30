<?php

namespace App\Services;

use App\Models\Template;
use Illuminate\Support\Collection;

class TemplateService
{
    /**
     * Retorna todos os templates com status 'WAITING_APROVAL'.
     *
     * @return Collection
     */
    public function buscarTemplatesAguardandoAprovacao(): Collection
    {
        return Template::where('status', 'WAITING_APROVAL')->get();
    }
}
