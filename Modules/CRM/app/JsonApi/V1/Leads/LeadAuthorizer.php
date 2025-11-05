<?php

namespace Modules\CRM\JsonApi\V1\Leads;

use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use Modules\CRM\Models\Lead;

class LeadAuthorizer implements AuthorizerContract
{
    public function index(Request $request): Response
    {
        return $request->user() && $request->user()->can('crm.leads.index')
            ? Response::allow()
            : Response::deny('You do not have permission to view leads.');
    }

    public function show(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.show')
            ? Response::allow()
            : Response::deny('You do not have permission to view this lead.');
    }

    public function store(Request $request): Response
    {
        return $request->user() && $request->user()->can('crm.leads.store')
            ? Response::allow()
            : Response::deny('You do not have permission to create leads.');
    }

    public function update(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update this lead.');
    }

    public function destroy(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.destroy')
            ? Response::allow()
            : Response::deny('You do not have permission to delete this lead.');
    }

    public function showRelated(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.show')
            ? Response::allow()
            : Response::deny('You do not have permission to view this lead relationship.');
    }

    public function showRelationship(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.show')
            ? Response::allow()
            : Response::deny('You do not have permission to view this lead relationship.');
    }

    public function updateRelationship(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update this lead relationship.');
    }

    public function attachRelationship(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update this lead relationship.');
    }

    public function detachRelationship(Request $request, Lead $model): Response
    {
        return $request->user() && $request->user()->can('crm.leads.update')
            ? Response::allow()
            : Response::deny('You do not have permission to update this lead relationship.');
    }
}
