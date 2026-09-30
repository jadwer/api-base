<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Contacts\Support\ContactChannels;

/**
 * Varios correos y telefonos por contacto (peticion de Jasim, 2026-09-30).
 * `email` sigue siendo el principal (llave del portal); `phone` y
 * `phone_extension` quedan como reflejo del primer telefono para PDFs y
 * reportes. Los telefonos existentes se trasladan a `phones`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->json('additional_emails')->nullable()->after('email');
            $table->json('phones')->nullable()->after('phone_extension');
        });

        DB::table('contacts')
            ->whereNotNull('phone')
            ->where('phone', '<>', '')
            ->orderBy('id')
            ->select(['id', 'phone', 'phone_extension'])
            ->each(function ($row) {
                $phone = ContactChannels::fromLegacyPhone($row->phone, $row->phone_extension);
                if ($phone) {
                    DB::table('contacts')->where('id', $row->id)->update(['phones' => json_encode([$phone])]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['additional_emails', 'phones']);
        });
    }
};
