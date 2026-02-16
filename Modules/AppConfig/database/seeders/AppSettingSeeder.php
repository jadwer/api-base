<?php

namespace Modules\AppConfig\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AppConfig\Models\AppSetting;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Company
            ['key' => 'company.name', 'value' => 'Labor Wasser de Mexico', 'type' => 'string', 'group' => 'company', 'label' => 'Nombre de la empresa'],
            ['key' => 'company.phone', 'value' => '55 7575 1661', 'type' => 'string', 'group' => 'company', 'label' => 'Telefono principal'],
            ['key' => 'company.phone_secondary', 'value' => '55 7575 1662', 'type' => 'string', 'group' => 'company', 'label' => 'Telefono secundario'],
            ['key' => 'company.phone_tertiary', 'value' => '55 7160 2454', 'type' => 'string', 'group' => 'company', 'label' => 'Telefono terciario'],
            ['key' => 'company.whatsapp_number', 'value' => '5215610400441', 'type' => 'string', 'group' => 'company', 'label' => 'WhatsApp (numero internacional)'],
            ['key' => 'company.whatsapp_display', 'value' => '56 1040 0441', 'type' => 'string', 'group' => 'company', 'label' => 'WhatsApp (texto visible)'],
            ['key' => 'company.email', 'value' => 'ventas@laborwasserdemexico.com', 'type' => 'string', 'group' => 'company', 'label' => 'Email de contacto'],
            ['key' => 'company.address', 'value' => 'CDMX y Area metropolitana', 'type' => 'string', 'group' => 'company', 'label' => 'Direccion'],
            ['key' => 'company.city', 'value' => 'Ciudad de Mexico', 'type' => 'string', 'group' => 'company', 'label' => 'Ciudad'],
            ['key' => 'company.state', 'value' => 'CDMX', 'type' => 'string', 'group' => 'company', 'label' => 'Estado'],
            ['key' => 'company.postal_code', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Codigo postal'],
            ['key' => 'company.website', 'value' => 'https://laborwasser.com', 'type' => 'string', 'group' => 'company', 'label' => 'Sitio web'],
            ['key' => 'company.logo_path', 'value' => '/images/laborwasser/labor-wasser-mexico-logo.webp', 'type' => 'string', 'group' => 'company', 'label' => 'Logo principal'],
            ['key' => 'company.logo_path_alt', 'value' => '/images/laborwasser/labor-wasser-mexico-logo2.webp', 'type' => 'string', 'group' => 'company', 'label' => 'Logo alternativo (header)'],
            ['key' => 'company.logo_path_footer', 'value' => '/images/laborwasser/labor-wasser-mexico-logo-1.png', 'type' => 'string', 'group' => 'company', 'label' => 'Logo footer'],
            ['key' => 'company.contact_icon', 'value' => '/images/laborwasser/labor-wasser-contacto.svg', 'type' => 'string', 'group' => 'company', 'label' => 'Icono de contacto'],

            // Social
            ['key' => 'social.facebook', 'value' => '#', 'type' => 'string', 'group' => 'social', 'label' => 'Facebook URL'],
            ['key' => 'social.instagram', 'value' => '#', 'type' => 'string', 'group' => 'social', 'label' => 'Instagram URL'],
            ['key' => 'social.linkedin', 'value' => '#', 'type' => 'string', 'group' => 'social', 'label' => 'LinkedIn URL'],

            // Branding
            ['key' => 'branding.primary_color', 'value' => '#8AC905', 'type' => 'string', 'group' => 'branding', 'label' => 'Color primario'],

            // Auth
            ['key' => 'auth.require_email_verification', 'value' => 'false', 'type' => 'boolean', 'group' => 'auth', 'label' => 'Requiere verificacion de email', 'description' => 'Si esta activo, los usuarios deben verificar su correo antes de acceder a funciones protegidas'],
        ];

        foreach ($settings as $setting) {
            AppSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
