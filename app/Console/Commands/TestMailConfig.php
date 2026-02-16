<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailConfig extends Command
{
    protected $signature = 'mail:test {email?}';
    protected $description = 'Test mail configuration and send a test email';

    public function handle()
    {
        $this->info('Mail Configuration Check');
        $this->line('─────────────────────────────────────────────');

        // Display current configuration
        $this->table(
            ['Setting', 'Value'],
            [
                ['MAIL_MAILER', config('mail.default')],
                ['MAIL_HOST', config('mail.mailers.smtp.host')],
                ['MAIL_PORT', config('mail.mailers.smtp.port')],
                ['MAIL_USERNAME', config('mail.mailers.smtp.username') ? '***' . substr(config('mail.mailers.smtp.username'), -10) : 'NOT SET'],
                ['MAIL_PASSWORD', config('mail.mailers.smtp.password') ? '*** (hidden)' : 'NOT SET'],
                ['MAIL_FROM_ADDRESS', config('mail.from.address')],
                ['MAIL_FROM_NAME', config('mail.from.name')],
                ['MAIL_ENCRYPTION', config('mail.mailers.smtp.encryption') ?? 'NONE'],
            ]
        );

        $this->line('─────────────────────────────────────────────');

        if (config('mail.default') === 'log') {
            $this->warn('⚠ Mail is set to LOG mode. Emails will be logged, not sent.');
            $this->line('To send real emails, set MAIL_MAILER=smtp in your .env file');
        }

        $email = $this->argument('email');

        if (!$email) {
            $email = $this->ask('Enter email address to send test email (or press Enter to skip)');
        }

        if (!$email) {
            $this->info('Skipping test email send.');
            return 0;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address!');
            return 1;
        }

        $this->info("Sending test email to: {$email}");

        try {
            Mail::raw('This is a test email from AD Perfumes. If you receive this, your mail configuration is working correctly!', function ($message) use ($email) {
                $message->to($email)
                    ->subject('Test Email - AD Perfumes');
            });

            $this->line('─────────────────────────────────────────────');
            $this->info('✓ Test email sent successfully!');

            if (config('mail.default') === 'log') {
                $this->line('Check storage/logs/laravel.log for the email content');
            } else {
                $this->line('Check the inbox for: ' . $email);
            }

            return 0;

        } catch (\Exception $e) {
            $this->line('─────────────────────────────────────────────');
            $this->error('✗ Failed to send test email');
            $this->error('Error: ' . $e->getMessage());

            $this->line("\nCommon issues:");
            $this->line('1. Check SMTP credentials in .env file');
            $this->line('2. Verify MAIL_HOST and MAIL_PORT are correct');
            $this->line('3. Check if firewall is blocking SMTP port');
            $this->line('4. Ensure MAIL_ENCRYPTION matches your SMTP server (tls/ssl)');

            return 1;
        }
    }
}
