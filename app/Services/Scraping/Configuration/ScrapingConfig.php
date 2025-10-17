<?php

namespace App\Services\Scraping\Configuration;

use App\Services\Scraping\Contracts\ConfigurableInterface;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

class ScrapingConfig implements ConfigurableInterface
{
    protected string $configPath;

    public function __construct()
    {
        $this->configPath = app_path('Services/Scraping/Configuration/configs');
    }

    public function loadConfiguration(string $siteKey): array
    {
        $configFile = "{$this->configPath}/{$siteKey}.yaml";
        
        if (!File::exists($configFile)) {
            throw new \InvalidArgumentException("Configuration file not found for site: {$siteKey}");
        }

        $config = Yaml::parseFile($configFile);
        
        if (!$this->validateConfiguration($config)) {
            throw new \InvalidArgumentException("Invalid configuration for site: {$siteKey}");
        }

        return array_merge($this->getDefaultConfiguration(), $config);
    }

    public function validateConfiguration(array $config): bool
    {
        $required = ['site_key', 'name', 'base_url', 'selectors'];
        
        foreach ($required as $key) {
            if (!isset($config[$key])) {
                return false;
            }
        }

        $requiredSelectors = ['title', 'content'];
        
        foreach ($requiredSelectors as $selector) {
            if (!isset($config['selectors'][$selector])) {
                return false;
            }
        }

        return true;
    }

    public function getDefaultConfiguration(): array
    {
        return [
            'rate_limit' => 2000,
            'browser' => [
                'timeout' => 30000,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'wait_until_network_idle' => true,
                'headers' => [
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                    'Upgrade-Insecure-Requests' => '1',
                ]
            ],
            'retry' => [
                'max_attempts' => 3,
                'delay_ms' => 1000,
            ],
            'selectors' => [
                'article_links' => 'a[href*="/articles/"]',
                'next_page' => '.pagination .next',
            ],
        ];
    }
}