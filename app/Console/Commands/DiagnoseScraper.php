<?php

namespace App\Console\Commands;

use App\Services\Scraping\Core\BrowserFactory;
use App\Services\Scraping\Scrapers\Sites\CodecourseScraper;
use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

class DiagnoseScraper extends Command
{
    protected $signature = 'diagnose:scraper';
    protected $description = 'Diagnose scraper issues for production deployment';

    public function handle(): int
    {
        $this->info('🔍 Diagnosing Scraper System...');
        $this->newLine();

        $issues = [];
        $warnings = [];

        // 1. Check PHP environment
        $this->info('1. PHP Environment Check');
        $this->line("   PHP Version: " . PHP_VERSION);
        $this->line("   Memory Limit: " . ini_get('memory_limit'));
        $this->line("   Max Execution Time: " . ini_get('max_execution_time'));
        $this->line("   User: " . get_current_user());
        
        if (ini_get('max_execution_time') < 300) {
            $warnings[] = "Max execution time might be too low for scraping";
        }
        $this->newLine();

        // 2. Check required extensions
        $this->info('2. Required Extensions');
        $extensions = ['curl', 'json', 'dom', 'libxml'];
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->line("   {$ext}: " . ($loaded ? '✅' : '❌'));
            if (!$loaded) {
                $issues[] = "Missing PHP extension: {$ext}";
            }
        }
        $this->newLine();

        // 3. Check Node.js and npm
        $this->info('3. Node.js & NPM Check');
        try {
            $nodeVersion = shell_exec('node --version 2>/dev/null');
            $npmVersion = shell_exec('npm --version 2>/dev/null');
            
            if ($nodeVersion) {
                $this->line("   Node.js: " . trim($nodeVersion) . " ✅");
            } else {
                $this->line("   Node.js: ❌ Not found");
                $issues[] = "Node.js is not installed or not in PATH";
            }
            
            if ($npmVersion) {
                $this->line("   NPM: " . trim($npmVersion) . " ✅");
            } else {
                $this->line("   NPM: ❌ Not found");
                $issues[] = "NPM is not installed or not in PATH";
            }
        } catch (\Exception $e) {
            $this->line("   Error checking Node.js/NPM: " . $e->getMessage());
            $issues[] = "Cannot execute shell commands to check Node.js";
        }
        $this->newLine();

        // 4. Check Puppeteer installation
        $this->info('4. Puppeteer Check');
        try {
            $puppeteerPath = shell_exec('npm list puppeteer -g --depth=0 2>/dev/null');
            $localPuppeteer = shell_exec('npm list puppeteer --depth=0 2>/dev/null');
            
            if ($puppeteerPath && strpos($puppeteerPath, 'puppeteer@') !== false) {
                $this->line("   Global Puppeteer: ✅ Found");
            } elseif ($localPuppeteer && strpos($localPuppeteer, 'puppeteer@') !== false) {
                $this->line("   Local Puppeteer: ✅ Found");
            } else {
                $this->line("   Puppeteer: ❌ Not found");
                $issues[] = "Puppeteer is not installed";
            }
        } catch (\Exception $e) {
            $warnings[] = "Could not check Puppeteer installation";
        }
        $this->newLine();

        // 5. Check Chrome/Chromium
        $this->info('5. Chrome/Chromium Check');
        $chromePaths = [
            '/usr/bin/google-chrome',
            '/usr/bin/chromium',
            '/usr/bin/chromium-browser',
            '/opt/google/chrome/chrome',
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        ];
        
        $chromeFound = false;
        foreach ($chromePaths as $path) {
            if (file_exists($path)) {
                $this->line("   Chrome found: {$path} ✅");
                $chromeFound = true;
                break;
            }
        }
        
        if (!$chromeFound) {
            $this->line("   Chrome/Chromium: ❌ Not found in common paths");
            $issues[] = "Chrome/Chromium executable not found";
        }
        $this->newLine();

        // 6. Test basic HTTP request
        $this->info('6. Basic HTTP Test');
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; DiagnosticBot/1.0)'
                ]
            ]);
            
            $html = file_get_contents('https://codecourse.com/articles', false, $context);
            if ($html && strlen($html) > 1000) {
                $this->line("   HTTP Request: ✅ Success (" . strlen($html) . " bytes)");
            } else {
                $this->line("   HTTP Request: ⚠️ Response too small");
                $warnings[] = "HTTP response seems incomplete";
            }
        } catch (\Exception $e) {
            $this->line("   HTTP Request: ❌ Failed - " . $e->getMessage());
            $issues[] = "Cannot make HTTP requests to target site";
        }
        $this->newLine();

        // 7. Test Browsershot
        $this->info('7. Browsershot Test');
        try {
            $browser = Browsershot::html('<h1>Test</h1>')
                ->timeout(30)
                ->setOption('args', ['--no-sandbox', '--disable-dev-shm-usage']);
                
            $pdf = $browser->pdf();
            if ($pdf && strlen($pdf) > 100) {
                $this->line("   Browsershot: ✅ Working");
            } else {
                $this->line("   Browsershot: ❌ Failed to generate PDF");
                $issues[] = "Browsershot cannot create PDF (Chrome issue)";
            }
        } catch (\Exception $e) {
            $this->line("   Browsershot: ❌ Error - " . $e->getMessage());
            $issues[] = "Browsershot error: " . $e->getMessage();
        }
        $this->newLine();

        // 8. Test scraper configuration
        $this->info('8. Scraper Configuration Test');
        try {
            $scraper = new CodecourseScraper();
            $config = $scraper->getConfiguration();
            $this->line("   Config loaded: ✅ " . $config['name']);
            $this->line("   Rate limit: " . $config['rate_limit'] . "ms");
        } catch (\Exception $e) {
            $this->line("   Configuration: ❌ Error - " . $e->getMessage());
            $issues[] = "Scraper configuration error: " . $e->getMessage();
        }
        $this->newLine();

        // 9. Test BrowserFactory with production settings
        $this->info('9. Production Browser Test');
        try {
            $html = BrowserFactory::scrapeUrl('https://example.com', [
                'timeout' => 15000,
                'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)',
                'args' => ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu']
            ]);
            
            if ($html && strlen($html) > 100) {
                $this->line("   Production Browser: ✅ Working");
            } else {
                $this->line("   Production Browser: ❌ Failed");
                $issues[] = "BrowserFactory failed with production settings";
            }
        } catch (\Exception $e) {
            $this->line("   Production Browser: ❌ Error - " . $e->getMessage());
            $issues[] = "BrowserFactory production error: " . $e->getMessage();
        }
        $this->newLine();

        // 10. Summary and recommendations
        $this->info('📋 Diagnosis Summary');
        
        if (empty($issues)) {
            $this->info('✅ All critical checks passed!');
        } else {
            $this->error('❌ Critical Issues Found:');
            foreach ($issues as $issue) {
                $this->line("   • {$issue}");
            }
        }
        
        if (!empty($warnings)) {
            $this->warn('⚠️ Warnings:');
            foreach ($warnings as $warning) {
                $this->line("   • {$warning}");
            }
        }
        
        $this->newLine();
        $this->info('🛠️ Production Setup Commands:');
        $this->newLine();
        
        // Ubuntu/Debian commands
        $this->line('For Ubuntu/Debian servers:');
        $this->line('sudo apt update');
        $this->line('sudo apt install -y nodejs npm chromium-browser');
        $this->line('npm install puppeteer');
        $this->newLine();
        
        // CentOS/RHEL commands  
        $this->line('For CentOS/RHEL servers:');
        $this->line('sudo yum install -y nodejs npm chromium');
        $this->line('npm install puppeteer');
        $this->newLine();
        
        // Docker commands
        $this->line('For Docker deployments:');
        $this->line('Add to Dockerfile:');
        $this->line('RUN apt-get update && apt-get install -y nodejs npm chromium-browser');
        $this->line('RUN npm install puppeteer');
        
        return empty($issues) ? Command::SUCCESS : Command::FAILURE;
    }
}
