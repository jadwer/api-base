<?php

namespace Modules\Contacts\JsonApi\V1\Contacts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends ResourceRequest
{
    public function rules(): array
    {
        $contact = $this->model();
        $isCreating = $this->isCreating();
        
        $rules = [
            'contactType' => ['required', Rule::in(['person', 'company'])],
            'name' => ['required', 'string', 'max:255'],
            'legalName' => ['nullable', 'string', 'max:255'],
            'taxId' => [
                'nullable',
                'string',
                'max:13',
                'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
                Rule::unique('contacts', 'tax_id')->ignore($contact?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'isCustomer' => ['boolean'],
            'isSupplier' => ['boolean'],
            'creditLimit' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currentCredit' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'classification' => ['nullable', Rule::in(['premium', 'standard', 'basic'])],
            'paymentTerms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],

            // WS5 Commissions
            'defaultSalespersonId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'collectionsAgentId' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'commissionPctOverride' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // WS7.1 Bind fields (SAT basic catalogs)
            'regimenFiscal' => ['nullable', 'string', Rule::in([
                '601', '603', '605', '606', '607', '608', '610', '611', '612',
                '614', '615', '616', '620', '621', '622', '623', '624', '625', '626',
            ])],
            'usoCfdi' => ['nullable', 'string', Rule::in([
                'G01', 'G02', 'G03',
                'I01', 'I02', 'I03', 'I04', 'I05', 'I06', 'I07', 'I08',
                'D01', 'D02', 'D03', 'D04', 'D05', 'D06', 'D07', 'D08', 'D09', 'D10',
                'S01', 'CP01', 'CN01',
            ])],
            'creditMonths' => ['nullable', 'integer', 'min:0', 'max:120'],
            'bankAccountNumber' => ['nullable', 'string', 'max:34'],
            'referralSource' => ['nullable', 'string', 'max:255'],
            'cuentaContable' => ['nullable', 'string', 'max:255'],
            'discountPct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];

        // For updates, make some fields optional  
        if (!$isCreating) {
            $rules['contactType'] = ['sometimes', Rule::in(['person', 'company'])];
            $rules['name'] = ['sometimes', 'string', 'max:255'];
            $rules['status'] = ['sometimes', Rule::in(['active', 'inactive', 'suspended'])];
        }

        return $rules;
    }

    // Moved complex business logic to the model boot() method
    // This keeps the Request class focused on basic validation

    public function messages(): array
    {
        return [
            'contactType.required' => 'El tipo de contacto es obligatorio.',
            'contactType.in' => 'El tipo de contacto debe ser persona o empresa.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'legalName.string' => 'La razón social debe ser texto.',
            'legalName.max' => 'La razón social no puede tener más de 255 caracteres.',
            'taxId.string' => 'El RFC debe ser texto.',
            'taxId.max' => 'El RFC no puede tener más de 13 caracteres.',
            'taxId.unique' => 'El RFC ya está registrado para otro contacto.',
            'taxId.regex' => 'El formato del RFC no es válido.',
            'email.email' => 'El formato del email no es válido.',
            'email.max' => 'El email no puede tener más de 255 caracteres.',
            'phone.string' => 'El teléfono debe ser texto.',
            'phone.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'website.max' => 'El sitio web no puede tener más de 255 caracteres.',
            'status.required' => 'El estatus es obligatorio.',
            'status.in' => 'El estatus debe ser activo, inactivo o suspendido.',
            'isCustomer.boolean' => 'El campo cliente debe ser verdadero o falso.',
            'isSupplier.boolean' => 'El campo proveedor debe ser verdadero o falso.',
            'creditLimit.numeric' => 'El límite de crédito debe ser un número.',
            'creditLimit.min' => 'El límite de crédito no puede ser negativo.',
            'currentCredit.numeric' => 'El crédito actual debe ser un número.',
            'currentCredit.min' => 'El crédito actual no puede ser negativo.',
            'classification.in' => 'La clasificación debe ser premium, standard o basic.',
            'paymentTerms.integer' => 'Los días de pago deben ser un número entero.',
            'paymentTerms.min' => 'Los días de pago no pueden ser negativos.',
            'paymentTerms.max' => 'Los días de pago no pueden ser mayores a 365.',
            'notes.string' => 'Las notas deben ser texto.',
            'metadata.array' => 'Los metadatos deben ser un arreglo.',
            'defaultSalespersonId.exists' => 'El vendedor asignado no existe.',
            'collectionsAgentId.exists' => 'El gestor de cobranza no existe.',
            'commissionPctOverride.min' => 'El porcentaje de comision no puede ser negativo.',
            'commissionPctOverride.max' => 'El porcentaje de comision no puede ser mayor a 100.',
            'regimenFiscal.in' => 'El regimen fiscal no es un codigo SAT valido.',
            'usoCfdi.in' => 'El uso de CFDI no es un codigo SAT valido.',
            'creditMonths.min' => 'Los meses de credito no pueden ser negativos.',
            'creditMonths.max' => 'Los meses de credito no pueden ser mayores a 120.',
            'bankAccountNumber.max' => 'La cuenta bancaria no puede tener mas de 34 caracteres.',
            'discountPct.min' => 'El porcentaje de descuento no puede ser negativo.',
            'discountPct.max' => 'El porcentaje de descuento no puede ser mayor a 100.',
        ];
    }
}
