<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\AppConfig\Models\AppSetting;

class MailConfigService
{
    /**
     * Configure Laravel mailer from AppConfig settings.
     * Called on boot - overrides .env values with database values if set.
     */
    public static function configureMailer(): void
    {
        try {
            $host = AppSetting::get('mail.smtp_host');
            $port = AppSetting::get('mail.smtp_port');
            $username = AppSetting::get('mail.smtp_username');
            $password = AppSetting::get('mail.smtp_password');
            $encryption = AppSetting::get('mail.smtp_encryption');
            $fromEmail = AppSetting::get('mail.system_email');
            $fromName = AppSetting::get('mail.system_name');

            // Only override if SMTP credentials are configured
            if ($host && $username && $password) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp.host', $host);
                Config::set('mail.mailers.smtp.port', (int) ($port ?: 465));
                Config::set('mail.mailers.smtp.username', $username);
                Config::set('mail.mailers.smtp.password', $password);
                Config::set('mail.mailers.smtp.encryption', $encryption ?: 'ssl');
            }

            // Always override from address if configured
            if ($fromEmail) {
                Config::set('mail.from.address', $fromEmail);
            }
            if ($fromName) {
                Config::set('mail.from.name', $fromName);
            }
        } catch (\Exception $e) {
            // Silently fail - table may not exist during migrations
            Log::debug('MailConfigService: Could not load mail settings from database', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the system from email address.
     */
    public static function getFromAddress(): string
    {
        try {
            $email = AppSetting::get('mail.system_email');
            if ($email) {
                return $email;
            }
        } catch (\Exception) {
            // Fallback
        }

        return config('mail.from.address', 'noreply@laborwasserdemexico.com');
    }

    /**
     * Get the system from name.
     */
    public static function getFromName(): string
    {
        try {
            $name = AppSetting::get('mail.system_name');
            if ($name) {
                return $name;
            }
        } catch (\Exception) {
            // Fallback
        }

        return config('mail.from.name', 'Labor Wasser de Mexico');
    }

    /**
     * Get the admin notification email.
     */
    public static function getAdminEmail(): string
    {
        try {
            $email = AppSetting::get('mail.admin_notification_email');
            if ($email) {
                return $email;
            }
        } catch (\Exception) {
            // Fallback
        }

        return config('mail.from.address', 'admin@laborwasserdemexico.com');
    }
}
