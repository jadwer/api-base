<?php

namespace Modules\Billing\JsonApi\V1\CompanySettings;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CompanySettingRequest extends ResourceRequest
{
    public function rules(): array
    {
        $settingId = $this->route('company_setting');
        $creating = $this->isMethod('POST');

        return [
            'companyName' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],
            'rfc' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'size:13',
                'regex:/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/',
                Rule::unique('company_settings', 'rfc')->ignore($settingId),
            ],
            'taxRegime' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'max:10',
            ],
            'postalCode' => [
                $creating ? 'required' : 'sometimes',
                'string',
                'size:5',
                'regex:/^[0-9]{5}$/',
            ],
            'invoiceSeries' => [
                'sometimes',
                'string',
                'max:10',
            ],
            'creditNoteSeries' => [
                'sometimes',
                'string',
                'max:10',
            ],
            'nextInvoiceFolio' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'nextCreditNoteFolio' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'pacProvider' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'pacUsername' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'pacPassword' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'pacProductionMode' => [
                'sometimes',
                'boolean',
            ],
            'certificateFile' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'keyFile' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'keyPassword' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'logoPath' => [
                'sometimes',
                'nullable',
                'string',
            ],
            // Contact Information
            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'state' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],
            'website' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            // Commercial Settings
            'bankAccounts' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'bankAccounts.*.bank' => [
                'required_with:bankAccounts',
                'string',
                'max:100',
            ],
            'bankAccounts.*.account_number' => [
                'sometimes',
                'string',
                'max:50',
            ],
            'bankAccounts.*.clabe' => [
                'sometimes',
                'string',
                'max:20',
            ],
            'bankAccounts.*.currency' => [
                'sometimes',
                'string',
                'in:MXN,USD,EUR',
            ],
            'bankAccounts.*.swift' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
            'commercialConditions' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'commercialConditions.*' => [
                'string',
                'max:500',
            ],
            'quoteSettings' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'additionalSettings' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'isActive' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'companyName.required' => 'El nombre de la empresa es obligatorio.',
            'companyName.string' => 'El nombre de la empresa debe ser un texto.',
            'companyName.max' => 'El nombre de la empresa no puede exceder 255 caracteres.',
            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.string' => 'El RFC debe ser un texto.',
            'rfc.size' => 'El RFC debe tener exactamente 13 caracteres.',
            'rfc.regex' => 'El RFC no tiene un formato válido.',
            'rfc.unique' => 'Ya existe una configuración con este RFC.',
            'taxRegime.required' => 'El régimen fiscal es obligatorio.',
            'taxRegime.string' => 'El régimen fiscal debe ser un texto.',
            'taxRegime.max' => 'El régimen fiscal no puede exceder 10 caracteres.',
            'postalCode.required' => 'El código postal es obligatorio.',
            'postalCode.string' => 'El código postal debe ser un texto.',
            'postalCode.size' => 'El código postal debe tener exactamente 5 dígitos.',
            'postalCode.regex' => 'El código postal debe contener solo números.',
            'invoiceSeries.string' => 'La serie de factura debe ser un texto.',
            'invoiceSeries.max' => 'La serie de factura no puede exceder 10 caracteres.',
            'creditNoteSeries.string' => 'La serie de nota de crédito debe ser un texto.',
            'creditNoteSeries.max' => 'La serie de nota de crédito no puede exceder 10 caracteres.',
            'nextInvoiceFolio.integer' => 'El siguiente folio de factura debe ser un número entero.',
            'nextInvoiceFolio.min' => 'El siguiente folio de factura debe ser al menos 1.',
            'nextCreditNoteFolio.integer' => 'El siguiente folio de nota debe ser un número entero.',
            'nextCreditNoteFolio.min' => 'El siguiente folio de nota debe ser al menos 1.',
            'pacProvider.string' => 'El proveedor PAC debe ser un texto.',
            'pacUsername.string' => 'El usuario PAC debe ser un texto.',
            'pacPassword.string' => 'La contraseña PAC debe ser un texto.',
            'pacProductionMode.boolean' => 'El modo de producción PAC debe ser verdadero o falso.',
            'certificateFile.string' => 'La ruta del certificado debe ser un texto.',
            'keyFile.string' => 'La ruta de la llave debe ser un texto.',
            'keyPassword.string' => 'La contraseña de la llave debe ser un texto.',
            'logoPath.string' => 'La ruta del logo debe ser un texto.',
            'additionalSettings.array' => 'La configuración adicional debe ser un objeto JSON.',
            'isActive.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }
}
