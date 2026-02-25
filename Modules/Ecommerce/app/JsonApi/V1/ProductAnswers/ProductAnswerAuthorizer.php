<?php

namespace Modules\Ecommerce\JsonApi\V1\ProductAnswers;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Response;
use LaravelJsonApi\Contracts\Auth\Authorizer;

class ProductAnswerAuthorizer implements Authorizer
{
    public function index(Request $request, string $modelClass): bool|Response
    {
        return $request->user()?->can('ecommerce.product-answers.index') ?? false;
    }

    public function store(Request $request, string $modelClass): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if (!$user->can('ecommerce.product-answers.store')) {
            return false;
        }
        // Verify the question exists and is approved
        $data = $request->json()->all();
        if (isset($data['data']['relationships']['question']['data']['id'])) {
            $questionId = $data['data']['relationships']['question']['data']['id'];
            $question = \Modules\Ecommerce\Models\ProductQuestion::find($questionId);
            if (!$question || $question->status !== 'approved') {
                return false;
            }
        }
        return true;
    }

    public function show(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-answers.show')) {
            return true;
        }
        // Users can view answers to approved questions
        $question = $model->question;
        return $question && $question->status === 'approved';
    }

    public function update(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-answers.update')) {
            return true;
        }
        return $model->user_id === $user->id;
    }

    public function destroy(Request $request, object $model): bool|Response
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($user->can('ecommerce.product-answers.destroy')) {
            return true;
        }
        return $model->user_id === $user->id;
    }

    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->show($request, $model);
    }

    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }

    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->update($request, $model);
    }
}
