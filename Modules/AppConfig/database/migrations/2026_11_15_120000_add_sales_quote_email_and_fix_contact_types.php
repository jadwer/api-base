<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Junta con el cliente 2026-11-12 (J3): correo de ventas que recibe el aviso de cada
 * cotizacion nueva de la tienda o del portal; vacio, llega al de administracion.
 *
 * De paso (regla 7): el checkout y la solicitud de cotizacion del cliente
 * creaban contactos con contact_type 'individual', que la validacion de
 * contactos no acepta (person|company). Se normalizan a 'person'.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('app_settings')->where('key', 'mail.sales_quote_email')->exists()) {
            DB::table('app_settings')->insert([
                'key' => 'mail.sales_quote_email',
                'value' => '',
                'type' => 'string',
                'group' => 'mail',
                'label' => 'Correo de ventas (avisos de cotizaciones)',
                'description' => 'Recibe el aviso de cada cotización nueva de la tienda o del portal para asignar vendedor. Vacío: llega al correo de avisos de administración.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Cache::forget('app_settings:grouped');
        }

        DB::table('contacts')->where('contact_type', 'individual')->update(['contact_type' => 'person']);
    }

    public function down(): void
    {
        DB::table('app_settings')->where('key', 'mail.sales_quote_email')->delete();
        Cache::forget('app_settings:grouped');
    }
};
