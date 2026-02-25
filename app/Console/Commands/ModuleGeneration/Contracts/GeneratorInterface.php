<?php

namespace App\Console\Commands\ModuleGeneration\Contracts;

use App\Console\Commands\ModuleGeneration\EntityConfig;
use App\Console\Commands\ModuleGeneration\ModuleConfig;

interface GeneratorInterface
{
    public function generate(ModuleConfig $module, EntityConfig $entity): string;
}
