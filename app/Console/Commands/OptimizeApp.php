<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class OptimizeApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize the application for production (cache routes, config, views)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Optimizing application...');

        // Cache configuration
        $this->info('Caching configuration...');
        Artisan::call('config:cache');
        $this->info('✓ Configuration cached');

        // Cache routes
        $this->info('Caching routes...');
        Artisan::call('route:cache');
        $this->info('✓ Routes cached');

        // Cache views
        $this->info('Caching views...');
        Artisan::call('view:cache');
        $this->info('✓ Views cached');

        // Optimize autoloader
        $this->info('Optimizing autoloader...');
        Artisan::call('optimize:clear');
        exec('composer dump-autoload --optimize --classmap-authoritative 2>&1', $output, $return);
        if ($return === 0) {
            $this->info('✓ Autoloader optimized');
        } else {
            $this->warn('⚠ Autoloader optimization skipped (composer not available)');
        }

        $this->info('');
        $this->info('Application optimization complete!');

        return Command::SUCCESS;
    }
}

