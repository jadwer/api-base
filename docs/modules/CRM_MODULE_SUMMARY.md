# CRM Module - Technical Architecture & Roadmap

## Overview

The CRM (Customer Relationship Management) module provides comprehensive customer relationship management capabilities including lead tracking, opportunity management, campaign management, and activity logging.

**Implementation Status:** 100% Phase 2 Complete
**Last Updated:** December 2025

## Module Statistics

| Metric | Count |
|--------|-------|
| Entities | 5 |
| PHP Files | 82+ |
| Test Files | 25 |
| API Endpoints | 25 |
| Permissions | 25 |
| Test Cases | 212+ |

## Entities

### 1. PipelineStage
Configurable sales pipeline stages for leads and opportunities.

**Fields:**
- `id` - Primary key
- `name` - Stage name (required, max 255)
- `stageType` - Type: 'lead' or 'opportunity' (required)
- `probability` - Win probability 0-100 (required, integer)
- `sortOrder` - Display order (required, integer >= 0)
- `isActive` - Active status (default: true)
- `isClosedWon` - Marks closed won stage (default: false)
- `isClosedLost` - Marks closed lost stage (default: false)
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `leads` - HasMany Lead
- `opportunities` - HasMany Opportunity

### 2. Lead
Prospective customer tracking and qualification.

**Fields:**
- `id` - Primary key
- `title` - Lead title (required, max 255)
- `status` - Status: new, contacted, qualified, unqualified, converted
- `rating` - Temperature: hot, warm, cold
- `source` - Lead origin (optional, max 100)
- `companyName` - Company name (optional, max 255)
- `contactPerson` - Contact person name (optional, max 255)
- `email` - Contact email (optional, valid email)
- `phone` - Contact phone (optional, max 50)
- `estimatedValue` - Estimated deal value (optional, numeric >= 0)
- `estimatedCloseDate` - Expected close date (optional, date)
- `convertedAt` - Conversion timestamp (optional)
- `notes` - Additional notes (optional, text)
- `metadata` - Custom JSON data (optional)
- `userId` - Assigned user (required)
- `contactId` - Contact reference (optional, future integration)
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `user` - BelongsTo User
- `activities` - HasMany Activity
- `opportunity` - HasOne Opportunity

**Scopes:**
- `new()`, `contacted()`, `qualified()`, `unqualified()`, `converted()`
- `hot()`, `warm()`, `cold()`
- `byStatus($status)`, `byRating($rating)`
- `assignedTo($userId)`

### 3. Campaign
Marketing campaign management with financial tracking.

**Fields:**
- `id` - Primary key
- `name` - Campaign name (required, max 255)
- `type` - Type: email, social_media, event, webinar, direct_mail, telemarketing
- `status` - Status: planning, active, paused, completed, cancelled
- `startDate` - Campaign start date (optional)
- `endDate` - Campaign end date (optional)
- `budget` - Allocated budget (optional, numeric >= 0)
- `actualCost` - Actual spent amount (optional, numeric >= 0)
- `expectedRevenue` - Expected revenue (optional, numeric >= 0)
- `actualRevenue` - Actual revenue generated (optional, numeric >= 0)
- `targetAudience` - Target audience description (optional, text)
- `description` - Campaign description (optional, text)
- `metadata` - Custom JSON data (optional)
- `userId` - Campaign owner (required)
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `user` - BelongsTo User
- `leads` - BelongsToMany Lead (via campaign_lead pivot table)
- `activities` - HasMany Activity

**Scopes:**
- `planning()`, `active()`, `paused()`, `completed()`, `cancelled()`
- `email()`, `socialMedia()`, `event()`, `webinar()`
- `byStatus($status)`, `byType($type)`
- `ownedBy($userId)`, `inProgress()`, `finished()`

### 4. Activity
Customer interaction and task tracking.

**Fields:**
- `id` - Primary key
- `activityType` - Type: call, email, meeting, note, task (required)
- `subject` - Activity subject (required, max 255)
- `description` - Detailed description (optional, text)
- `activityDate` - Scheduled/occurred date (required, datetime)
- `duration` - Duration in minutes (optional, integer >= 0)
- `outcome` - Activity outcome (optional, max 500)
- `status` - Status: scheduled, completed, pending, cancelled
- `userId` - Activity owner (required)
- `leadId` - Related lead (optional)
- `opportunityId` - Related opportunity (optional)
- `campaignId` - Related campaign (optional)
- `contactId` - Related contact (optional, future integration)
- `metadata` - Custom JSON data (optional)
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `user` - BelongsTo User
- `lead` - BelongsTo Lead
- `opportunity` - BelongsTo Opportunity
- `campaign` - BelongsTo Campaign

**Scopes:**
- `calls()`, `emails()`, `meetings()`, `notes()`, `tasks()`
- `scheduled()`, `completed()`, `pending()`, `cancelled()`
- `forLead($leadId)`, `forOpportunity($opportunityId)`, `forCampaign($campaignId)`
- `forUser($userId)`
- `upcoming()`, `past()`, `today()`
- `byType($type)`, `byStatus($status)`

### 5. Opportunity
Sales opportunity/deal management with forecasting.

**Fields:**
- `id` - Primary key
- `name` - Opportunity name (required, max 255)
- `description` - Description (optional, text)
- `amount` - Deal amount (required, numeric >= 0)
- `probability` - Win probability 0-100 (required, integer)
- `expectedRevenue` - Auto-calculated: amount * probability / 100
- `actualRevenue` - Actual revenue when won (optional, numeric >= 0)
- `closeDate` - Expected close date (required, date)
- `status` - Status: open, won, lost, abandoned
- `stage` - Sales stage (optional, max 100)
- `forecastCategory` - Forecast: pipeline, best_case, commit, closed (optional)
- `source` - Opportunity source (optional, max 100)
- `nextStep` - Next action required (optional, max 500)
- `lossReason` - Reason for loss (optional, max 500)
- `wonAt` - Auto-set when status changes to 'won'
- `lostAt` - Auto-set when status changes to 'lost'
- `leadId` - Source lead (optional)
- `userId` - Opportunity owner (required)
- `pipelineStageId` - Pipeline stage (optional)
- `contactId` - Primary contact (optional, future integration)
- `metadata` - Custom JSON data (optional)
- `createdAt`, `updatedAt` - Timestamps

**Relationships:**
- `user` - BelongsTo User
- `lead` - BelongsTo Lead
- `pipelineStage` - BelongsTo PipelineStage
- `activities` - HasMany Activity

**Auto-Calculated Fields:**
- `expectedRevenue` - Automatically calculated from amount * probability / 100
- `wonAt` - Automatically set when status changes to 'won'
- `lostAt` - Automatically set when status changes to 'lost'
- `forecastCategory` - Automatically set to 'closed' when won

**Scopes:**
- `open()`, `won()`, `lost()`, `abandoned()`
- `byStatus($status)`, `byStage($stage)`, `byForecastCategory($category)`
- `assignedTo($userId)`
- `closingBefore($date)`, `closingAfter($date)`
- `highValue($minAmount)`, `highProbability($minProbability)`

## API Endpoints

All endpoints follow JSON:API 1.1 specification.

### Pipeline Stages
```
GET    /api/v1/pipeline-stages           - List pipeline stages
POST   /api/v1/pipeline-stages           - Create pipeline stage
GET    /api/v1/pipeline-stages/{id}      - Show pipeline stage
PATCH  /api/v1/pipeline-stages/{id}      - Update pipeline stage
DELETE /api/v1/pipeline-stages/{id}      - Delete pipeline stage
```

### Leads
```
GET    /api/v1/leads                     - List leads
POST   /api/v1/leads                     - Create lead
GET    /api/v1/leads/{id}                - Show lead
PATCH  /api/v1/leads/{id}                - Update lead
DELETE /api/v1/leads/{id}                - Delete lead
```

### Campaigns
```
GET    /api/v1/campaigns                 - List campaigns
POST   /api/v1/campaigns                 - Create campaign
GET    /api/v1/campaigns/{id}            - Show campaign
PATCH  /api/v1/campaigns/{id}            - Update campaign
DELETE /api/v1/campaigns/{id}            - Delete campaign
```

### Activities
```
GET    /api/v1/activities                - List activities
POST   /api/v1/activities                - Create activity
GET    /api/v1/activities/{id}           - Show activity
PATCH  /api/v1/activities/{id}           - Update activity
DELETE /api/v1/activities/{id}           - Delete activity
```

### Opportunities
```
GET    /api/v1/opportunities             - List opportunities
POST   /api/v1/opportunities             - Create opportunity
GET    /api/v1/opportunities/{id}        - Show opportunity
PATCH  /api/v1/opportunities/{id}        - Update opportunity
DELETE /api/v1/opportunities/{id}        - Delete opportunity
```

## Permissions

Each entity has 5 standard permissions following the pattern `crm.{entities}.{action}`:

| Permission | Description |
|------------|-------------|
| `crm.pipeline-stages.index` | List pipeline stages |
| `crm.pipeline-stages.show` | View single pipeline stage |
| `crm.pipeline-stages.store` | Create pipeline stages |
| `crm.pipeline-stages.update` | Update pipeline stages |
| `crm.pipeline-stages.destroy` | Delete pipeline stages |
| `crm.leads.index` | List leads |
| `crm.leads.show` | View single lead |
| `crm.leads.store` | Create leads |
| `crm.leads.update` | Update leads |
| `crm.leads.destroy` | Delete leads |
| `crm.campaigns.index` | List campaigns |
| `crm.campaigns.show` | View single campaign |
| `crm.campaigns.store` | Create campaigns |
| `crm.campaigns.update` | Update campaigns |
| `crm.campaigns.destroy` | Delete campaigns |
| `crm.activities.index` | List activities |
| `crm.activities.show` | View single activity |
| `crm.activities.store` | Create activities |
| `crm.activities.update` | Update activities |
| `crm.activities.destroy` | Delete activities |
| `crm.opportunities.index` | List opportunities |
| `crm.opportunities.show` | View single opportunity |
| `crm.opportunities.store` | Create opportunities |
| `crm.opportunities.update` | Update opportunities |
| `crm.opportunities.destroy` | Delete opportunities |

### Permission Matrix

| Role | pipeline-stages | leads | campaigns | activities | opportunities |
|------|-----------------|-------|-----------|------------|---------------|
| god | Full | Full | Full | Full | Full |
| admin | Full | Full | Full | Full | Full |
| tech | Read | Read | Read | Read | Read |
| customer | None | None | None | None | None |

## Database Schema

### Tables

1. `pipeline_stages` - Pipeline stage definitions
2. `leads` - Lead/prospect records
3. `campaigns` - Marketing campaign records
4. `campaign_lead` - Many-to-many pivot table (campaigns <-> leads)
5. `activities` - Activity/interaction records
6. `opportunities` - Sales opportunity records

### Key Indexes
- All foreign keys indexed
- Status fields indexed for filtering
- Date fields indexed for range queries
- User assignment indexed for ownership queries

## Testing

Test files located in `Modules/CRM/tests/Feature/`:

### Test Structure
- 5 test files per entity (Index, Show, Store, Update, Destroy)
- Total: 25 test files, 212+ test cases

### Test Coverage
- CRUD operations
- Permission checks (admin, tech, customer, guest)
- Validation rules
- Relationship inclusion
- Filtering and sorting
- Edge cases

### Running Tests
```bash
# Run all CRM tests
php artisan test Modules/CRM/tests/Feature/

# Run specific entity tests
php artisan test Modules/CRM/tests/Feature/Leads/

# Run with filter
php artisan test --filter="Lead"
```

## Integration Points

### User Module
- All entities have `user` relationship for ownership/assignment
- Activities track user who performed them
- Campaigns track campaign owner

### Contacts Module (Future)
- Lead.contactId prepared for Contact integration
- Activity.contactId prepared for Contact integration
- Opportunity.contactId prepared for Contact integration

### Sales Module (Future)
- Opportunities can convert to Sales Orders
- Quotes can generate from Opportunities

## Business Rules

### Lead Rules
1. Status transitions: new -> contacted -> qualified/unqualified -> converted
2. Rating indicates lead temperature (hot/warm/cold)
3. convertedAt is set when status changes to 'converted'
4. Can only have one active opportunity per lead

### Campaign Rules
1. Status flow: planning -> active -> paused/completed/cancelled
2. Budget tracking with actual cost and revenue
3. Leads can be associated with multiple campaigns
4. Campaign metrics aggregated from associated leads

### Activity Rules
1. Must be associated with at least one entity (lead, opportunity, or campaign)
2. Duration tracked in minutes
3. Status indicates completion state
4. Upcoming activities filtered by date and status

### Opportunity Rules
1. expectedRevenue auto-calculated from amount and probability
2. Status 'won' auto-sets wonAt timestamp
3. Status 'lost' auto-sets lostAt timestamp
4. Forecast category used for pipeline reporting
5. Can link to source lead for conversion tracking

## Roadmap

### Completed (Phase 2)
- [x] PipelineStage entity with CRUD
- [x] Lead entity with CRUD and lifecycle
- [x] Campaign entity with CRUD and lead association
- [x] Activity entity with CRUD and relationships
- [x] Opportunity entity with CRUD and forecasting
- [x] Full test coverage (212+ tests)
- [x] Permission system integration
- [x] Frontend integration guide

### Future Enhancements (Phase 3)
- [ ] Quote entity for sales proposals
- [ ] QuoteItem entity for quote line items
- [ ] Lead conversion action endpoint
- [ ] Opportunity close actions (won/lost)
- [ ] Pipeline drag-and-drop stage updates
- [ ] Campaign lead bulk operations
- [ ] Activity reminders and notifications
- [ ] Dashboard analytics and KPIs
- [ ] Email integration for activity tracking
- [ ] Contact module integration

## Frontend Integration

For complete frontend integration documentation including:
- API request/response examples
- Field validations
- Filter and sort options
- Relationship includes
- Error handling
- TypeScript interfaces

See: `docs/modules/CRM_FRONTEND_GUIDE.md`

## Changelog

### Phase 2 (December 2025)
- Added Opportunity entity with auto-calculated fields
- Complete CRUD operations for all 5 entities
- 212+ test cases with full coverage
- Frontend guide documentation (900+ lines)

### Phase 1 (November 2025)
- Initial implementation of 4 entities
- PipelineStage, Lead, Campaign, Activity
- Basic CRUD operations
- Permission system integration
