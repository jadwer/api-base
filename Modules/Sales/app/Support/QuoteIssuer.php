<?php

namespace Modules\Sales\Support;

use Modules\AppConfig\Services\AppSettingResolver;
use Modules\Billing\Models\CompanySetting;

/**
 * Datos del emisor que imprimen las cotizaciones (PDF de cotizacion y PDF
 * informativo del carrito).
 *
 * Peticion del cliente (revision 2026-09-23): los datos de cotizacion deben
 * ir SEPARADOS de los de facturacion. Antes ambos PDFs leian CompanySetting
 * (la configuracion fiscal de Billing), que en produccion sigue con el
 * emisor de prueba porque el cliente aun no factura; las cotizaciones salian
 * con ese dato y no se podian enviar.
 *
 * Origen configurable en app_settings (grupo documents):
 *   documents.issuer_source = company | manual | billing
 *   - company (default): ajustes de empresa (company.*), que el cliente ya
 *     edita en Configuracion > Empresa. RFC solo si documents.issuer_rfc.
 *   - manual: los campos documents.issuer_*; lo vacio cae a company.*.
 *   - billing: comportamiento anterior (CompanySetting activa), para cuando
 *     el cliente decida facturar con datos reales.
 * Cuentas bancarias: documents.quote_bank_accounts (una por linea:
 * "Banco | Cuenta o CLABE | Moneda") salvo en billing, que usa las de la
 * configuracion fiscal.
 *
 * Expone los mismos nombres que usaban las plantillas ($company->company_name,
 * ->rfc, ->logo_path, ->phone, ->email) para no reescribir los blades.
 */
final class QuoteIssuer
{
    public const SOURCE_COMPANY = 'company';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_BILLING = 'billing';

    public const SOURCES = [self::SOURCE_COMPANY, self::SOURCE_MANUAL, self::SOURCE_BILLING];

    public function __construct(
        public readonly string $source,
        public readonly ?string $company_name,
        public readonly ?string $rfc,
        public readonly ?string $logo_path,
        public readonly ?string $phone,
        public readonly ?string $email,
        /** ['street','city','state','postal_code'] o null si no hay nada */
        public readonly ?array $address,
        /** [['bank','clabe','currency'], ...] */
        public readonly array $bank_accounts,
        /** Condiciones comerciales de CompanySetting (no son dato del emisor) */
        public readonly ?array $conditions,
    ) {
    }

    public static function resolve(?AppSettingResolver $settings = null): self
    {
        $settings ??= app(AppSettingResolver::class);
        $source = $settings->getString('documents.issuer_source', self::SOURCE_COMPANY);
        if (! in_array($source, self::SOURCES, true)) {
            $source = self::SOURCE_COMPANY;
        }

        if ($source === self::SOURCE_BILLING) {
            return self::fromBilling($settings);
        }

        return self::fromSettings($settings, $source);
    }

    private static function fromSettings(AppSettingResolver $settings, string $source): self
    {
        $manual = $source === self::SOURCE_MANUAL;
        $pick = function (string $manualKey, ?string $companyKey) use ($settings, $manual): ?string {
            $value = $manual ? trim($settings->getString("documents.{$manualKey}")) : '';
            if ($value === '' && $companyKey !== null) {
                $value = trim($settings->getString("company.{$companyKey}"));
            }

            return $value === '' ? null : $value;
        };

        $address = array_filter([
            'street' => $pick('issuer_address', 'address'),
            'city' => $pick('issuer_city', 'city'),
            'state' => $pick('issuer_state', 'state'),
            'postal_code' => $pick('issuer_postal_code', 'postal_code'),
        ]);

        // El RFC no existe en company.*: solo se imprime si el cliente lo captura.
        $rfc = trim($settings->getString('documents.issuer_rfc'));

        return new self(
            source: $source,
            company_name: $pick('issuer_name', 'name'),
            rfc: $rfc === '' ? null : $rfc,
            logo_path: $pick('issuer_logo_path', 'logo_path'),
            phone: $pick('issuer_phone', 'phone'),
            email: $pick('issuer_email', 'email'),
            address: $address === [] ? null : $address,
            bank_accounts: self::parseBankAccounts($settings->getString('documents.quote_bank_accounts')),
            // Las condiciones comerciales no son dato del emisor: se conserva la
            // cadena historica (leyenda configurada -> condiciones de CompanySetting
            // -> defaults del generador) en todos los origenes.
            conditions: CompanySetting::getActive()?->getCommercialConditions() ?: null,
        );
    }

    private static function fromBilling(AppSettingResolver $settings): self
    {
        $company = CompanySetting::getActive();
        if (! $company) {
            // Sin configuracion fiscal activa: cae a los ajustes de empresa
            // para no imprimir una cotizacion sin emisor.
            $fallback = self::fromSettings($settings, self::SOURCE_COMPANY);

            return new self(self::SOURCE_BILLING, $fallback->company_name, $fallback->rfc, $fallback->logo_path,
                $fallback->phone, $fallback->email, $fallback->address, $fallback->bank_accounts, null);
        }

        $extra = $company->additional_settings ?? [];
        $address = null;
        if ($company->address || $company->city || $company->state) {
            $address = array_filter([
                'street' => $company->address,
                'city' => $company->city,
                'state' => $company->state,
                'postal_code' => $company->postal_code,
            ]);
        } elseif (! empty($extra['address'])) {
            $address = (array) $extra['address'];
        }

        return new self(
            source: self::SOURCE_BILLING,
            company_name: $company->company_name,
            rfc: $company->rfc,
            logo_path: $company->logo_path,
            phone: $company->phone ?? $extra['phone'] ?? null,
            email: $company->email ?? $extra['email'] ?? null,
            address: $address ?: null,
            bank_accounts: array_values($company->getBankAccounts() ?? []),
            conditions: $company->getCommercialConditions() ?: null,
        );
    }

    /**
     * "Banco | Cuenta o CLABE | Moneda" por linea. Lineas vacias se ignoran;
     * la moneda es opcional (MXN por defecto).
     */
    public static function parseBankAccounts(string $raw): array
    {
        $accounts = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));
            if ($parts[0] === '') {
                continue;
            }
            $account = ['bank' => $parts[0]];
            if (isset($parts[1]) && $parts[1] !== '') {
                $account['clabe'] = $parts[1];
            }
            $account['currency'] = isset($parts[2]) && $parts[2] !== '' ? strtoupper($parts[2]) : 'MXN';
            $accounts[] = $account;
        }

        return $accounts;
    }
}
