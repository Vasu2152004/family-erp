<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate environment configuration for production readiness';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Validating environment configuration...');
        $this->newLine();

        $errors = [];
        $warnings = [];

        // Check APP_ENV
        if (config('app.env') !== 'production') {
            $warnings[] = 'APP_ENV is not set to "production"';
        }

        // Check APP_DEBUG
        if (config('app.debug') === true) {
            $errors[] = 'APP_DEBUG is set to true (must be false in production)';
        }

        // Check APP_KEY
        if (empty(config('app.key'))) {
            $errors[] = 'APP_KEY is not set';
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
            $this->info('✓ Database connection: OK');
        } catch (\Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        // Check mail configuration
        $mailMailer = config('mail.default');
        if ($mailMailer !== 'log') {
            $this->info('✓ Mail driver: ' . $mailMailer);
        }

        // Check required environment variables
        $requiredVars = [
            'APP_NAME',
            'APP_URL',
            'DB_HOST',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
        ];

        foreach ($requiredVars as $var) {
            if (empty(env($var))) {
                $errors[] = "Required environment variable {$var} is not set";
            }
        }

        // Check session security in production
        if (config('app.env') === 'production') {
            if (!config('session.encrypt')) {
                $warnings[] = 'Session encryption is disabled (recommended in production)';
            }
            if (!config('session.secure')) {
                $warnings[] = 'Session secure cookie is disabled (required for HTTPS)';
            }
        }

        // Check cache driver
        $cacheDriver = config('cache.default');
        if ($cacheDriver === 'array') {
            $warnings[] = 'Cache driver is set to "array" (not suitable for production)';
        }

        // Check queue driver
        $queueDriver = config('queue.default');
        if ($queueDriver === 'sync') {
            $warnings[] = 'Queue driver is set to "sync" (not suitable for production)';
        }

        // Check mail configuration
        $mailMailer = config('mail.default');
        if ($mailMailer === 'log' && config('app.env') === 'production') {
            $warnings[] = 'Mail driver is set to "log" (not suitable for production - emails will not be sent)';
        }

        // Check mail from address
        $mailFromAddress = config('mail.from.address');
        if (empty($mailFromAddress)) {
            $errors[] = 'MAIL_FROM_ADDRESS is not set';
        } elseif (!filter_var($mailFromAddress, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'MAIL_FROM_ADDRESS is not a valid email address';
        }

        // Check mail from name
        if (empty(config('mail.from.name'))) {
            $warnings[] = 'MAIL_FROM_NAME is not set (emails will use default name)';
        }

        // Check SMTP configuration if using SMTP
        if ($mailMailer === 'smtp') {
            $smtpRequired = ['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD'];
            foreach ($smtpRequired as $var) {
                if (empty(env($var))) {
                    $errors[] = "SMTP configuration: {$var} is not set";
                }
            }

            // Validate port is numeric if set
            $mailPort = env('MAIL_PORT');
            if (!empty($mailPort) && !is_numeric($mailPort)) {
                $errors[] = 'MAIL_PORT must be a numeric value';
            }
        }

        // Display results
        if (empty($errors) && empty($warnings)) {
            $this->info('✓ All checks passed! Environment is production-ready.');
            return Command::SUCCESS;
        }

        if (!empty($errors)) {
            $this->error('Errors found:');
            foreach ($errors as $error) {
                $this->error("  ✗ {$error}");
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn('Warnings:');
            foreach ($warnings as $warning) {
                $this->warn("  ⚠ {$warning}");
            }
            $this->newLine();
        }

        if (!empty($errors)) {
            $this->error('Environment validation failed. Please fix the errors above.');
            return Command::FAILURE;
        }

        $this->warn('Environment validation completed with warnings.');
        return Command::SUCCESS;
    }
}






