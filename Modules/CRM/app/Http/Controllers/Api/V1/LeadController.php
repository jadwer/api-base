<?php

namespace Modules\CRM\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Destroy;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchMany;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchOne;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchRelated;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\FetchRelationship;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Store;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\Update;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\AttachRelationship;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\DetachRelationship;
use LaravelJsonApi\Laravel\Http\Controllers\Actions\UpdateRelationship;
use Modules\Contacts\Models\Contact;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Opportunity;

class LeadController extends Controller
{
    use FetchMany;
    use FetchOne;
    use Store;
    use Update;
    use Destroy;
    use FetchRelated;
    use FetchRelationship;
    use UpdateRelationship;
    use AttachRelationship;
    use DetachRelationship;

    /**
     * Convert a lead into a contact (prospect) and/or opportunity.
     * POST /api/v1/leads/{lead}/convert
     */
    public function convert(Request $request, Lead $lead): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('crm.leads.update')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($lead->status === 'converted') {
            return response()->json([
                'error' => 'El lead ya fue convertido',
            ], 422);
        }

        $validated = $request->validate([
            'create_contact' => ['sometimes', 'boolean'],
            'create_opportunity' => ['sometimes', 'boolean'],
        ]);

        $createContact = (bool) ($validated['create_contact'] ?? true);
        $createOpportunity = (bool) ($validated['create_opportunity'] ?? true);

        [$contact, $opportunity] = DB::transaction(function () use ($lead, $user, $createContact, $createOpportunity) {
            $contact = null;
            $opportunity = null;

            if ($createContact && !$lead->contact_id) {
                $contact = Contact::create([
                    'contact_type' => 'company',
                    'name' => $lead->company_name ?: $lead->title,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    // Sigue siendo prospecto hasta su primera compra
                    'is_customer' => false,
                    'status' => 'active',
                ]);

                $lead->contact_id = $contact->id;
            }

            if ($createOpportunity) {
                $opportunity = Opportunity::create([
                    'name' => $lead->title,
                    'amount' => $lead->estimated_value ?? 0,
                    'probability' => 0,
                    'close_date' => $lead->estimated_close_date ?? now()->addDays(30),
                    'status' => 'open',
                    'lead_id' => $lead->id,
                    'user_id' => $lead->user_id ?? $user->id,
                    'contact_id' => $lead->contact_id,
                    'source' => $lead->source,
                ]);
            }

            $lead->status = 'converted';
            $lead->converted_at = now();
            $lead->save();

            return [$contact, $opportunity];
        });

        $response = [
            'message' => 'Lead convertido exitosamente',
            'lead' => [
                'id' => $lead->id,
                'status' => $lead->status,
                'converted_at' => $lead->converted_at?->toISOString(),
                'contact_id' => $lead->contact_id,
            ],
        ];

        if ($contact) {
            $response['contact'] = [
                'id' => $contact->id,
                'name' => $contact->name,
            ];
        }

        if ($opportunity) {
            $response['opportunity'] = [
                'id' => $opportunity->id,
                'title' => $opportunity->name,
            ];
        }

        return response()->json($response);
    }
}
