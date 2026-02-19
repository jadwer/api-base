<?php

namespace Modules\Accounting\JsonApi\V1\Accounts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;
use Modules\Accounting\Services\AccountHierarchyService;

class AccountRequest extends ResourceRequest
{
    public function rules(): array
    {
        $account = $this->model();
        $isUpdate = $account && $account->exists;

        
        return [            'code' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255', Rule::unique('accounts')->ignore($account?->id)],
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'accountType' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'nature' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'level' => [$isUpdate ? 'sometimes' : 'required', 'integer'],
            'parentId' => ['nullable', 'integer'],
            'currency' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'isPostable' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'isCashFlow' => [$isUpdate ? 'sometimes' : 'required', 'boolean'],
            'status' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [            'code.required' => 'El campo Code es obligatorio.',
            'code.string' => 'El campo Code debe ser texto.',
            'code.max' => 'El campo Code no puede tener más de 255 caracteres.',
            'code.unique' => 'Este Code ya está en uso.',
            'name.required' => 'El campo Name es obligatorio.',
            'name.string' => 'El campo Name debe ser texto.',
            'name.max' => 'El campo Name no puede tener más de 255 caracteres.',
            'accountType.required' => 'El campo Account type es obligatorio.',
            'accountType.string' => 'El campo Account type debe ser texto.',
            'accountType.max' => 'El campo Account type no puede tener más de 255 caracteres.',
            'nature.required' => 'El campo Nature es obligatorio.',
            'nature.string' => 'El campo Nature debe ser texto.',
            'nature.max' => 'El campo Nature no puede tener más de 255 caracteres.',
            'level.required' => 'El campo Level es obligatorio.',
            'level.integer' => 'El campo Level debe ser un número entero.',
            'currency.required' => 'El campo Currency es obligatorio.',
            'currency.string' => 'El campo Currency debe ser texto.',
            'currency.max' => 'El campo Currency no puede tener más de 255 caracteres.',
            'isPostable.required' => 'El campo Is postable es obligatorio.',
            'isPostable.boolean' => 'El campo Is postable debe ser verdadero o falso.',
            'isCashFlow.required' => 'El campo Is cash flow es obligatorio.',
            'isCashFlow.boolean' => 'El campo Is cash flow debe ser verdadero o falso.',
            'status.required' => 'El campo Status es obligatorio.',
            'status.string' => 'El campo Status debe ser texto.',
            'status.max' => 'El campo Status no puede tener más de 255 caracteres.',
            'metadata.array' => 'El campo Metadata debe ser un arreglo.',
        ];
    }

    /**
     * AC-007: Validate account hierarchy to prevent circular references
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->validated();

            // Only validate if parentId is being set
            if (!isset($data['parentId']) || $data['parentId'] === null) {
                return;
            }

            $account = $this->model();
            $accountId = $account?->id;

            $hierarchyService = app(AccountHierarchyService::class);

            try {
                // Validate parent account exists
                $parentAccount = \Modules\Accounting\Models\Account::find($data['parentId']);
                if (!$parentAccount) {
                    $validator->errors()->add('parentId', 'La cuenta padre no existe.');
                    return;
                }

                // Validate max depth from parent (new account adds 1 level)
                $parentDepth = $hierarchyService->calculateDepth($data['parentId']);
                if ($parentDepth >= 4) { // Max 5 levels, parent at depth 4 = child at depth 5
                    $validator->errors()->add('parentId', 'Se excede la profundidad máxima de jerarquía (5 niveles).');
                    return;
                }

                // For updates, also validate circular reference
                if ($accountId) {
                    $hierarchyService->validateNoCircularReference($accountId, $data['parentId']);
                    $hierarchyService->validateMaxDepth($accountId, 5);
                }
            } catch (\Exception $e) {
                $validator->errors()->add('parentId', $e->getMessage());
            }
        });
    }
}
