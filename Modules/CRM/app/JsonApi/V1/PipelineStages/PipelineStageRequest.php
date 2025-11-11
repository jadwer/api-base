<?php

namespace Modules\CRM\JsonApi\V1\PipelineStages;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class PipelineStageRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        $isCreating = $this->isMethod('POST');

        return [
            'name' => $isCreating ? ['required', 'string', 'max:255'] : ['sometimes', 'string', 'max:255'],
            'stageType' => $isCreating ? ['required', Rule::in(['lead', 'opportunity'])] : ['sometimes', Rule::in(['lead', 'opportunity'])],
            'probability' => $isCreating ? ['required', 'integer', 'min:0', 'max:100'] : ['sometimes', 'integer', 'min:0', 'max:100'],
            'sortOrder' => $isCreating ? ['required', 'integer', 'min:0'] : ['sometimes', 'integer', 'min:0'],
            'isActive' => ['sometimes', 'boolean'],
            'isClosedWon' => ['sometimes', 'boolean'],
            'isClosedLost' => ['sometimes', 'boolean'],
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
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'stageType.required' => 'El tipo es obligatorio.',
            'stageType.in' => 'El tipo debe ser "lead" u "opportunity".',
            'probability.required' => 'La probabilidad es obligatoria.',
            'probability.integer' => 'La probabilidad debe ser un número entero.',
            'probability.min' => 'La probabilidad debe ser al menos 0.',
            'probability.max' => 'La probabilidad no debe exceder 100.',
            'sortOrder.required' => 'El orden es obligatorio.',
            'sortOrder.integer' => 'El orden debe ser un número entero.',
            'sortOrder.min' => 'El orden debe ser al menos 0.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
            'isClosedWon.boolean' => 'El campo cerrado ganado debe ser verdadero o falso.',
            'isClosedLost.boolean' => 'El campo cerrado perdido debe ser verdadero o falso.',
        ];
    }
}
