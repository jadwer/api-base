<?php

namespace Modules\AppConfig\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppConfig\Models\AppSetting;

/**
 * Generic placeholder values for the api-base template.
 *
 * Production tenants override these in their own seeders (e.g.
 * clients/&lt;name&gt;/api/Modules/AppConfig/Database/Seeders/&lt;Name&gt;Seeder.php)
 * which run with firstOrCreate as well, so re-seeding never overwrites values
 * the customer has already edited from the dashboard.
 *
 * Why firstOrCreate: production cutover keeps real values intact when this
 * seeder runs alongside CleanDatabaseSeeder during a fresh install. Customer
 * edits via /dashboard/settings/app-config are never clobbered.
 */
class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Company
            ['key' => 'company.name', 'value' => 'Demo Company', 'type' => 'string', 'group' => 'company', 'label' => 'Nombre de la empresa'],
            ['key' => 'company.phone', 'value' => '+52 555 555 5555', 'type' => 'string', 'group' => 'company', 'label' => 'Telefono principal'],
            ['key' => 'company.phone_secondary', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Telefono secundario'],
            ['key' => 'company.phone_tertiary', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Telefono terciario'],
            ['key' => 'company.whatsapp_number', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'WhatsApp (numero internacional)'],
            ['key' => 'company.whatsapp_display', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'WhatsApp (texto visible)'],
            ['key' => 'company.email', 'value' => 'info@example.com', 'type' => 'string', 'group' => 'company', 'label' => 'Email de contacto'],
            ['key' => 'company.address', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Direccion'],
            ['key' => 'company.city', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Ciudad'],
            ['key' => 'company.state', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Estado'],
            ['key' => 'company.postal_code', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Codigo postal'],
            ['key' => 'company.website', 'value' => 'https://example.com', 'type' => 'string', 'group' => 'company', 'label' => 'Sitio web'],
            ['key' => 'company.logo_path', 'value' => '/images/brand/logo.png', 'type' => 'string', 'group' => 'company', 'label' => 'Logo principal'],
            ['key' => 'company.logo_path_alt', 'value' => '/images/brand/logo-alt.png', 'type' => 'string', 'group' => 'company', 'label' => 'Logo alternativo (header)'],
            ['key' => 'company.logo_path_footer', 'value' => '/images/brand/logo-footer.png', 'type' => 'string', 'group' => 'company', 'label' => 'Logo footer'],
            ['key' => 'company.contact_icon', 'value' => '/images/brand/contact-icon.svg', 'type' => 'string', 'group' => 'company', 'label' => 'Icono de contacto'],

            // Social
            ['key' => 'social.facebook', 'value' => '#', 'type' => 'string', 'group' => 'social', 'label' => 'Facebook URL'],
            ['key' => 'social.instagram', 'value' => '#', 'type' => 'string', 'group' => 'social', 'label' => 'Instagram URL'],
            ['key' => 'social.linkedin', 'value' => '#', 'type' => 'string', 'group' => 'social', 'label' => 'LinkedIn URL'],

            // Branding
            ['key' => 'branding.primary_color', 'value' => '#2563eb', 'type' => 'string', 'group' => 'branding', 'label' => 'Color primario'],
            ['key' => 'branding.cdn_base_url', 'value' => '', 'type' => 'string', 'group' => 'branding', 'label' => 'CDN base URL', 'description' => 'Optional CDN host for branded assets (logos, hero images). Leave empty to serve from local public/.'],

            // Auth
            ['key' => 'auth.require_email_verification', 'value' => 'false', 'type' => 'boolean', 'group' => 'auth', 'label' => 'Requiere verificacion de email', 'description' => 'Si esta activo, los usuarios deben verificar su correo antes de acceder a funciones protegidas'],

            // Mail / Email del sistema
            ['key' => 'mail.system_email', 'value' => 'info@example.com', 'type' => 'string', 'group' => 'mail', 'label' => 'Email del sistema (remitente)', 'description' => 'Direccion de correo que aparece como remitente en todos los emails del sistema'],
            ['key' => 'mail.system_name', 'value' => 'Demo Company', 'type' => 'string', 'group' => 'mail', 'label' => 'Nombre del remitente'],
            ['key' => 'mail.admin_notification_email', 'value' => 'admin@example.com', 'type' => 'string', 'group' => 'mail', 'label' => 'Email para notificaciones administrativas', 'description' => 'Email donde llegan avisos de nuevas cotizaciones, ordenes, etc.'],
            ['key' => 'mail.smtp_host', 'value' => '', 'type' => 'string', 'group' => 'mail', 'label' => 'SMTP Host'],
            ['key' => 'mail.smtp_port', 'value' => '465', 'type' => 'string', 'group' => 'mail', 'label' => 'SMTP Puerto'],
            ['key' => 'mail.smtp_username', 'value' => '', 'type' => 'string', 'group' => 'mail', 'label' => 'SMTP Usuario', 'description' => 'Email completo creado en cPanel'],
            ['key' => 'mail.smtp_password', 'value' => '', 'type' => 'string', 'group' => 'mail', 'label' => 'SMTP Contrasena'],
            ['key' => 'mail.smtp_encryption', 'value' => 'ssl', 'type' => 'string', 'group' => 'mail', 'label' => 'SMTP Encriptacion', 'description' => 'ssl (puerto 465) o tls (puerto 587)'],

            // Currency
            ['key' => 'currency.base_currency', 'value' => 'MXN', 'type' => 'string', 'group' => 'currency', 'label' => 'Moneda base', 'description' => 'Codigo ISO 4217 de la moneda base del sistema'],
            ['key' => 'currency.exchange_rate_margin', 'value' => '2.0', 'type' => 'string', 'group' => 'currency', 'label' => 'Margen sobre tipo de cambio (%)', 'description' => 'Porcentaje de margen aplicado sobre el tipo de cambio para ventas'],
            ['key' => 'currency.exchange_rate_source', 'value' => 'banxico', 'type' => 'string', 'group' => 'currency', 'label' => 'Fuente de tipo de cambio', 'description' => 'Proveedor de tipos de cambio: banxico o manual'],
            ['key' => 'currency.auto_update_rates', 'value' => 'true', 'type' => 'boolean', 'group' => 'currency', 'label' => 'Actualizar tipos de cambio automaticamente', 'description' => 'Si esta activo, los tipos de cambio se actualizan diariamente via API de Banxico'],
            ['key' => 'currency.banxico_api_token', 'value' => '', 'type' => 'string', 'group' => 'currency', 'label' => 'Token API Banxico', 'description' => 'Token de acceso para la API SIE de Banxico (obtener en banxico.org.mx)'],
        ];

        foreach ($settings as $setting) {
            AppSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
