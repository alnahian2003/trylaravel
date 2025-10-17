<?php

namespace App\Services\Scraping\Contracts;

interface ConfigurableInterface
{
    public function loadConfiguration(string $siteKey): array;
    
    public function validateConfiguration(array $config): bool;
    
    public function getDefaultConfiguration(): array;
}