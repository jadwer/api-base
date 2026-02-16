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
            ['key' => 'company.phone', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Telefono'],
            ['key' => 'company.email', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Email de contacto'],
            ['key' => 'company.address', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Direccion'],
            ['key' => 'company.city', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Ciudad'],
            ['key' => 'company.state', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Estado'],
            ['key' => 'company.postal_code', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Codigo postal'],
            ['key' => 'company.website', 'value' => '', 'type' => 'string', 'group' => 'company', 'label' => 'Sitio web'],
            ['key' => 'company.logo_path', 'value' => '/images/laborwasser/labor-wasser-mexico-logo.webp', 'type' => 'string', 'group' => 'company', 'label' => 'Ruta del logotipo'],

            // Branding
            ['key' => 'branding.primary_color', 'value' => '#8AC905', 'type' => 'string', 'group' => 'branding', 'label' => 'Color primario'],

            // Auth
            ['key' => 'auth.require_email_verification', 'value' => 'false', 'type' => 'boolean', 'group' => 'auth', 'label' => 'Requiere verificacion de email', 'description' => 'Si esta activo, los usuarios deben verificar su correo antes de acceder a funciones protegidas'],
        ];

        foreach ($settings as $setting) {
            AppSetting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
