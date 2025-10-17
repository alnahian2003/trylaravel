<?php

namespace App\Services\Scraping\Core;

use Spatie\Browsershot\Browsershot;

class BrowserFactory
{
    public static function create(array $config = []): Browsershot
    {
        // Create browser instance without initial HTML if we have a URL
        if (isset($config['url'])) {
            $browser = Browsershot::url($config['url']);
            unset($config['url']); // Remove URL from config to avoid conflicts
        } else {
            $browser = Browsershot::html('<html></html>');
        }

        if (isset($config['timeout'])) {
            $browser->timeout($config['timeout']);
        }

        if (isset($config['user_agent'])) {
            $browser->setUserAgent($config['user_agent']);
        }

        if (isset($config['wait_until_network_idle'])) {
            $browser->waitUntilNetworkIdle($config['wait_until_network_idle']);
        }

        if (isset($config['mobile']) && $config['mobile']) {
            $browser->mobile();
        }

        if (isset($config['device'])) {
            $browser->device($config['device']);
        }

        if (isset($config['headers']) && is_array($config['headers'])) {
            foreach ($config['headers'] as $name => $value) {
                $browser->setExtraHttpHeader($name, $value);
            }
        }

        // Production-ready Chrome arguments
        $defaultArgs = [
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-default-apps',
            '--disable-sync',
            '--metrics-recording-only',
            '--no-first-run',
            '--safebrowsing-disable-auto-update',
            '--disable-crash-reporter',
            '--disable-in-process-stack-traces',
            '--disable-logging',
            '--log-level=3',
            '--silent',
        ];

        // Add custom args if provided, merging with defaults
        $customArgs = $config['args'] ?? [];
        $args = array_unique(array_merge($defaultArgs, $customArgs));
        
        // Use setOption method to ensure args are properly set
        $browser->setOption('args', $args);

        // Try to use system Chrome if available (both local and production)
        $chromePaths = [
            '/usr/bin/chromium-browser',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/opt/google/chrome/chrome',
        ];

        foreach ($chromePaths as $path) {
            if (file_exists($path)) {
                $browser->setChromePath($path);
                break;
            }
        }

        $browser->ignoreHttpsErrors();
        $browser->dismissDialogs();

        return $browser;
    }

    public static function scrapeUrl(string $url, array $config = []): string
    {
        // Pass URL in config so it's used during browser creation
        $config['url'] = $url;
        $browser = self::create($config);
        
        try {
            return $browser->bodyHtml();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to scrape URL: {$url}. Error: " . $e->getMessage(), 0, $e);
        }
    }

    public static function debugArgs(array $config = []): array
    {
        $defaultArgs = [
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-extensions',
            '--disable-plugins',
            '--disable-default-apps',
            '--disable-sync',
            '--metrics-recording-only',
            '--no-first-run',
            '--safebrowsing-disable-auto-update',
            '--disable-crash-reporter',
            '--disable-in-process-stack-traces',
            '--disable-logging',
            '--log-level=3',
            '--silent',
        ];

        $customArgs = $config['args'] ?? [];
        return array_unique(array_merge($defaultArgs, $customArgs));
    }
}