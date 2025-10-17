<?php

namespace App\Services\Scraping\Core;

use Spatie\Browsershot\Browsershot;

class BrowserFactory
{
    public static function create(array $config = []): Browsershot
    {
        $browser = Browsershot::html('<html></html>');

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

        $browser->ignoreHttpsErrors();
        $browser->dismissDialogs();

        return $browser;
    }

    public static function scrapeUrl(string $url, array $config = []): string
    {
        $browser = self::create($config);
        
        try {
            return $browser->url($url)->bodyHtml();
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to scrape URL: {$url}. Error: " . $e->getMessage(), 0, $e);
        }
    }
}