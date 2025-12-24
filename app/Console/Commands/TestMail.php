<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TestMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email? : The email address to send the test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing mail configuration...');
        $this->newLine();

        // Validate mail configuration
        $mailMailer = config('mail.default');
        $mailFromAddress = config('mail.from.address');
        $mailFromName = config('mail.from.name');

        $this->line("Mail Driver: {$mailMailer}");
        $this->line("From Address: {$mailFromAddress}");
        $this->line("From Name: {$mailFromName}");
        $this->newLine();

        // Check if mail driver is log (development only)
        if ($mailMailer === 'log') {
            $this->warn('⚠ Mail driver is set to "log" - emails will be written to log files only.');
            $this->warn('   This is suitable for development but not production.');
            $this->newLine();
        }

        // Validate from address
        if (empty($mailFromAddress)) {
            $this->error('✗ MAIL_FROM_ADDRESS is not set');
            return Command::FAILURE;
        }

        if (!filter_var($mailFromAddress, FILTER_VALIDATE_EMAIL)) {
            $this->error('✗ MAIL_FROM_ADDRESS is not a valid email address');
            return Command::FAILURE;
        }

        // Get recipient email
        $recipientEmail = $this->argument('email');

        if (empty($recipientEmail)) {
            // Try to get authenticated user's email
            if (auth()->check()) {
                $recipientEmail = auth()->user()->email;
                $this->line("No email provided, using authenticated user's email: {$recipientEmail}");
            } else {
                $recipientEmail = $this->ask('Please enter the email address to send the test email to');
            }
        }

        // Validate recipient email
        if (empty($recipientEmail)) {
            $this->error('✗ Recipient email address is required');
            return Command::FAILURE;
        }

        $validator = Validator::make(
            ['email' => $recipientEmail],
            ['email' => 'required|email']
        );

        if ($validator->fails()) {
            $this->error('✗ Invalid recipient email address: ' . $recipientEmail);
            return Command::FAILURE;
        }

        // Check SMTP configuration if using SMTP
        if ($mailMailer === 'smtp') {
            $smtpConfig = [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'username' => config('mail.mailers.smtp.username'),
                'encryption' => config('mail.mailers.smtp.encryption'),
            ];

            $missing = [];
            foreach (['host', 'port', 'username'] as $key) {
                if (empty($smtpConfig[$key])) {
                    $missing[] = "MAIL_" . strtoupper($key);
                }
            }

            if (!empty($missing)) {
                $this->error('✗ Missing SMTP configuration: ' . implode(', ', $missing));
                return Command::FAILURE;
            }

            if (empty(env('MAIL_PASSWORD'))) {
                $this->warn('⚠ MAIL_PASSWORD is not set - authentication may fail');
            }
        }

        // Send test email
        $this->info('Sending test email...');
        $this->newLine();

        try {
            Mail::raw(
                "This is a test email from your Family ERP application.\n\n" .
                "If you received this email, your mail configuration is working correctly!\n\n" .
                "Mail Configuration:\n" .
                "- Driver: {$mailMailer}\n" .
                "- From: {$mailFromName} <{$mailFromAddress}>\n" .
                "- Sent at: " . now()->toDateTimeString(),
                function ($message) use ($recipientEmail, $mailFromAddress, $mailFromName) {
                    $message->to($recipientEmail)
                        ->subject('Family ERP - Test Email')
                        ->from($mailFromAddress, $mailFromName);
                }
            );

            $this->info('✓ Test email sent successfully!');
            $this->newLine();
            $this->line("Recipient: {$recipientEmail}");
            $this->line("Please check the inbox (and spam folder) for the test email.");

            if ($mailMailer === 'log') {
                $this->newLine();
                $this->line("Note: Since mail driver is 'log', check the log file at:");
                $this->line("  storage/logs/laravel.log");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('✗ Failed to send test email');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->line('Please check your mail configuration:');
            $this->line('  - Verify SMTP credentials are correct');
            $this->line('  - Check firewall/network settings');
            $this->line('  - Ensure queue workers are running (if using queues)');
            $this->line('  - Review application logs for more details');

            return Command::FAILURE;
        }
    }
}


