<?php

namespace Modules\CRM\JsonApi\V1\Opportunities;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class OpportunityRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'actualRevenue' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'closeDate' => ['required', 'date'],
            'status' => ['required', Rule::in(['open', 'won', 'lost', 'abandoned'])],
            'stage' => ['required', 'string', 'max:255'],
            'forecastCategory' => ['required', Rule::in(['pipeline', 'best_case', 'commit', 'closed'])],
            'source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nextStep' => ['sometimes', 'nullable', 'string'],
            'lossReason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'user' => 'required',
            'lead' => 'nullable',
            'pipelineStage' => 'nullable',
            'contact' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la oportunidad es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',

            'description.string' => 'La descripción debe ser una cadena de texto.',

            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto no puede ser negativo.',

            'probability.required' => 'La probabilidad es obligatoria.',
            'probability.integer' => 'La probabilidad debe ser un número entero.',
            'probability.min' => 'La probabilidad no puede ser menor a 0.',
            'probability.max' => 'La probabilidad no puede ser mayor a 100.',

            'actualRevenue.numeric' => 'El ingreso real debe ser un número.',
            'actualRevenue.min' => 'El ingreso real no puede ser negativo.',

            'closeDate.required' => 'La fecha de cierre es obligatoria.',
            'closeDate.date' => 'La fecha de cierre debe ser una fecha válida.',

            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser: open, won, lost, o abandoned.',

            'stage.required' => 'La etapa es obligatoria.',
            'stage.string' => 'La etapa debe ser una cadena de texto.',
            'stage.max' => 'La etapa no debe exceder 255 caracteres.',

            'forecastCategory.required' => 'La categoría de pronóstico es obligatoria.',
            'forecastCategory.in' => 'La categoría debe ser: pipeline, best_case, commit, o closed.',

            'source.string' => 'La fuente debe ser una cadena de texto.',
            'source.max' => 'La fuente no debe exceder 255 caracteres.',

            'nextStep.string' => 'El próximo paso debe ser una cadena de texto.',

            'lossReason.string' => 'La razón de pérdida debe ser una cadena de texto.',
            'lossReason.max' => 'La razón de pérdida no debe exceder 255 caracteres.',

            'metadata.array' => 'Los metadatos deben ser un arreglo.',
        ];
    }
}
