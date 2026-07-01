<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

class MailSettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        try {
            $this->applyMailSettings();
        } catch (\Exception $e) {
            // Settings table might not exist yet (before migration)
            // Fall back to .env values silently
        }
    }

    private function applyMailSettings(): void
    {
        $settings = Setting::where('group', 'mail')->get()->keyBy('key');

        if ($settings->isEmpty()) {
            return;
        }

        // Apply the default mailer
        $mailer = $settings->get('mail_mailer')?->value ?: 'smtp';
        config(['mail.default' => $mailer]);

        // Apply SMTP settings
        $smtpConfig = [];

        if ($settings->has('mail_host') && $settings->get('mail_host')->value) {
            $smtpConfig['host'] = $settings->get('mail_host')->value;
        }
        if ($settings->has('mail_port') && $settings->get('mail_port')->value) {
            $smtpConfig['port'] = (int) $settings->get('mail_port')->value;
        }
        if ($settings->has('mail_username') && $settings->get('mail_username')->value) {
            $smtpConfig['username'] = $settings->get('mail_username')->value;
        }
        if ($settings->has('mail_password') && $settings->get('mail_password')->value) {
            $smtpConfig['password'] = $settings->get('mail_password')->value;
        }
        if ($settings->has('mail_encryption') && $settings->get('mail_encryption')->value) {
            $smtpConfig['encryption'] = $settings->get('mail_encryption')->value;
        }

        if (!empty($smtpConfig)) {
            config(['mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), $smtpConfig)]);
        }

        // Apply from address and name
        $fromAddress = $settings->get('mail_from_address')?->value;
        $fromName = $settings->get('mail_from_name')?->value;

        if ($fromAddress) {
            config(['mail.from.address' => $fromAddress]);
        }
        if ($fromName) {
            config(['mail.from.name' => $fromName]);
        }
    }
}
