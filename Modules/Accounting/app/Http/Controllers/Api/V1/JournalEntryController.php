<?php

namespace Modules\Accounting\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Core\Responses\DataResponse;
use Modules\Accounting\Services\JournalEntryService;
use Modules\Accounting\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class JournalEntryController extends Controller
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    protected JournalEntryService $journalEntryService;

    public function __construct(JournalEntryService $journalEntryService)
    {
        $this->journalEntryService = $journalEntryService;
    }

    /**
     * POST /api/v1/journal-entries/{journal_entry}/post
     * Post a draft journal entry
     */
    public function post(Request $request, JournalEntry $journalEntry)
    {
        try {
            $this->journalEntryService->post($journalEntry);
            
            return DataResponse::make($journalEntry->refresh())
                ->withServer($request->server('json-api'))
                ->withHeader('Content-Location', $journalEntry->getRouteKey());
                
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => [
                    [
                        'title' => 'Validation Error',
                        'detail' => $e->getMessage(),
                        'source' => ['pointer' => '/data/attributes'],
                        'status' => '422'
                    ]
                ]
            ], 422);
        }
    }

    /**
     * GET /api/v1/journal-entries/{journal_entry}/totals  
     * Get entry totals for validation
     */
    public function totals(Request $request, JournalEntry $journalEntry)
    {
        $totals = $this->journalEntryService->getTotals($journalEntry);
        
        return response()->json([
            'data' => [
                'type' => 'journal-entry-totals',
                'id' => $journalEntry->getRouteKey(),
                'attributes' => $totals
            ]
        ]);
    }
}
