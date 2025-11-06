<?php

namespace Modules\CRM\JsonApi\V1\Campaigns;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class CampaignRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        $campaignId = $this->route('campaign');
        $isCreating = $this->isCreating();

        return [
            'name' => [$isCreating ? 'required' : 'sometimes', 'string', 'max:255'],
            'type' => [$isCreating ? 'required' : 'sometimes', Rule::in(['email', 'social_media', 'event', 'webinar', 'direct_mail', 'telemarketing'])],
            'status' => ['sometimes', Rule::in(['planning', 'active', 'paused', 'completed', 'cancelled'])],
            'userId' => [$isCreating ? 'required' : 'sometimes', JsonApiRule::toOne()],
            'startDate' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actualCost' => ['nullable', 'numeric', 'min:0'],
            'expectedRevenue' => ['nullable', 'numeric', 'min:0'],
            'actualRevenue' => ['nullable', 'numeric', 'min:0'],
            'targetAudience' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la campaña es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',

            'type.required' => 'El tipo de campaña es obligatorio.',
            'type.in' => 'El tipo debe ser: email, social_media, event, webinar, direct_mail o telemarketing.',

            'status.in' => 'El estado debe ser: planning, active, paused, completed o cancelled.',

            'userId.required' => 'El propietario de la campaña es obligatorio.',

            'startDate.required' => 'La fecha de inicio es obligatoria.',
            'startDate.date' => 'La fecha de inicio debe ser una fecha válida.',

            'endDate.date' => 'La fecha de fin debe ser una fecha válida.',
            'endDate.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',

            'budget.numeric' => 'El presupuesto debe ser un número.',
            'budget.min' => 'El presupuesto no puede ser negativo.',

            'actualCost.numeric' => 'El costo real debe ser un número.',
            'actualCost.min' => 'El costo real no puede ser negativo.',

            'expectedRevenue.numeric' => 'Los ingresos esperados deben ser un número.',
            'expectedRevenue.min' => 'Los ingresos esperados no pueden ser negativos.',

            'actualRevenue.numeric' => 'Los ingresos reales deben ser un número.',
            'actualRevenue.min' => 'Los ingresos reales no pueden ser negativos.',

            'targetAudience.string' => 'El público objetivo debe ser una cadena de texto.',
            'targetAudience.max' => 'El público objetivo no puede exceder 255 caracteres.',

            'description.string' => 'La descripción debe ser una cadena de texto.',

            'metadata.array' => 'Los metadatos deben ser un objeto JSON válido.',
        ];
    }
}
