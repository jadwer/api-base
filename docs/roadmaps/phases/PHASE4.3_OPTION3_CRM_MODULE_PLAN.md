# Phase 4.3 Option 3: CRM Module - Implementation Plan

**Status:** 📋 Planning
**Start Date:** TBD
**Estimated Duration:** 4-5 days
**Complexity:** Medium (3/5)
**Priority:** 🥈 SECOND (High Priority)
**Dependencies:** Sales Module ✅ Complete, Contacts (Party Pattern) ✅ Complete

---

## Objective

Implement a comprehensive Customer Relationship Management (CRM) module to enable sales teams with lead management, opportunity tracking, sales pipeline visualization, and campaign management. Integrate with existing Sales module for seamless lead-to-customer conversion.

**Business Value:**
- Sales pipeline visibility and forecasting
- Lead nurturing and conversion tracking
- Team collaboration on opportunities
- Marketing campaign effectiveness measurement
- Customer communication history

---

## Architecture Decision

**Module Approach:** Create dedicated `CRM` module

**Why?**
- Clean separation from Sales module (CRM focuses on PRE-sale, Sales on POST-sale)
- Reusable across different business units
- Can integrate with external CRM tools later
- Dedicated permissions and workflows

---

## Implementation Plan

### Stage 1: Lead Management (Day 1, 6-7 hours)

#### 1.1 Database Migrations

**Table: `crm_leads`**
```sql
CREATE TABLE crm_leads (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    source VARCHAR(100) NOT NULL, -- website, referral, cold-call, campaign
    status VARCHAR(50) DEFAULT 'new', -- new, contacted, qualified, converted, lost
    rating VARCHAR(20) DEFAULT 'warm', -- hot, warm, cold
    company_name VARCHAR(255),
    contact_name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50),
    contact_position VARCHAR(100),
    industry VARCHAR(100),
    estimated_revenue DECIMAL(10,2),
    expected_close_date DATE,
    assigned_to BIGINT UNSIGNED, -- User ID (sales rep)
    converted_to_contact_id BIGINT UNSIGNED, -- FK to contacts
    converted_at TIMESTAMP NULL,
    lost_reason TEXT,
    notes TEXT,
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (converted_to_contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    INDEX idx_leads_status (status),
    INDEX idx_leads_rating (rating),
    INDEX idx_leads_assigned (assigned_to),
    INDEX idx_leads_source (source)
);
```

**Table: `crm_lead_activities`**
```sql
CREATE TABLE crm_lead_activities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    lead_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    activity_type VARCHAR(50) NOT NULL, -- call, email, meeting, note
    subject VARCHAR(255),
    description TEXT,
    scheduled_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_activities_lead (lead_id),
    INDEX idx_activities_user (user_id),
    INDEX idx_activities_type (activity_type)
);
```

#### 1.2 Models

**Lead Model:**
```php
class Lead extends Model
{
    protected $table = 'crm_leads';

    protected $fillable = [
        'source', 'status', 'rating', 'company_name', 'contact_name',
        'contact_email', 'contact_phone', 'contact_position', 'industry',
        'estimated_revenue', 'expected_close_date', 'assigned_to',
        'converted_to_contact_id', 'converted_at', 'lost_reason', 'notes', 'metadata'
    ];

    protected $casts = [
        'estimated_revenue' => 'float',
        'expected_close_date' => 'date',
        'converted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function assignedTo(): BelongsTo; // User
    public function convertedToContact(): BelongsTo; // Contact
    public function activities(): HasMany; // LeadActivity
    public function opportunities(): HasMany; // Opportunity

    // Scopes
    public function scopeActive($query);
    public function scopeHot($query);
    public function scopeAssignedTo($query, int $userId);
}
```

**LeadActivity Model:**
```php
class LeadActivity extends Model
{
    protected $table = 'crm_lead_activities';

    protected $fillable = [
        'lead_id', 'user_id', 'activity_type', 'subject',
        'description', 'scheduled_at', 'completed_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function lead(): BelongsTo;
    public function user(): BelongsTo;
}
```

#### 1.3 API Endpoints

**Sales Rep / CRM User:**
```
GET    /api/v1/crm/leads                    List leads (assigned or all)
POST   /api/v1/crm/leads                    Create lead
GET    /api/v1/crm/leads/{id}               Show lead
PATCH  /api/v1/crm/leads/{id}               Update lead
DELETE /api/v1/crm/leads/{id}               Delete lead
POST   /api/v1/crm/leads/{id}/convert       Convert to customer
POST   /api/v1/crm/leads/{id}/activities    Log activity
GET    /api/v1/crm/leads/{id}/activities    List activities
```

#### 1.4 Business Logic

**LeadService:**
- `createLead()` - Create new lead with auto-assignment logic
- `assignLead()` - Assign to sales rep (round-robin or manual)
- `qualifyLead()` - Move from 'new' to 'qualified'
- `convertLead()` - Convert to Contact + create Opportunity
- `loseLead()` - Mark as lost with reason

**Conversion Logic:**
```php
public function convertLead(Lead $lead): array
{
    // 1. Create Contact from lead
    $contact = Contact::create([
        'name' => $lead->company_name ?? $lead->contact_name,
        'email' => $lead->contact_email,
        'phone' => $lead->contact_phone,
        'is_customer' => true, // Party pattern
    ]);

    // 2. Create Opportunity
    $opportunity = Opportunity::create([
        'contact_id' => $contact->id,
        'name' => "Opportunity from {$lead->contact_name}",
        'estimated_value' => $lead->estimated_revenue,
        'expected_close_date' => $lead->expected_close_date,
        'assigned_to' => $lead->assigned_to,
    ]);

    // 3. Update lead
    $lead->update([
        'status' => 'converted',
        'converted_to_contact_id' => $contact->id,
        'converted_at' => now(),
    ]);

    return compact('contact', 'opportunity');
}
```

#### 1.5 Testing

Create 5 test files for Lead + 3 for LeadActivity

**Test Scenarios:**
- Create and assign leads
- Log activities (call, email, meeting)
- Convert lead to customer
- Mark lead as lost
- Filter by status/rating
- Sales rep sees only assigned leads
- Admin sees all leads

---

### Stage 2: Opportunity Management (Day 2, 6-7 hours)

#### 2.1 Database Migration

**Table: `crm_opportunities`**
```sql
CREATE TABLE crm_opportunities (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    contact_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    stage VARCHAR(50) DEFAULT 'prospecting', -- prospecting, qualification, proposal, negotiation, closed_won, closed_lost
    probability INTEGER DEFAULT 10, -- 0-100%
    estimated_value DECIMAL(10,2),
    expected_close_date DATE,
    actual_close_date DATE,
    assigned_to BIGINT UNSIGNED,
    lead_source VARCHAR(100),
    campaign_id BIGINT UNSIGNED,
    quote_id BIGINT UNSIGNED, -- Future: link to Quote
    sales_order_id BIGINT UNSIGNED, -- Link to Sales Order when won
    loss_reason TEXT,
    next_step VARCHAR(255),
    description TEXT,
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE SET NULL,
    INDEX idx_opportunities_contact (contact_id),
    INDEX idx_opportunities_stage (stage),
    INDEX idx_opportunities_assigned (assigned_to),
    INDEX idx_opportunities_close_date (expected_close_date)
);
```

**Table: `crm_opportunity_products`**
```sql
CREATE TABLE crm_opportunity_products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    opportunity_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1,
    unit_price DECIMAL(10,2),
    discount_percent DECIMAL(5,2) DEFAULT 0,
    total_amount DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (opportunity_id) REFERENCES crm_opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_opp_products_opportunity (opportunity_id)
);
```

#### 2.2 Models

**Opportunity Model:**
```php
class Opportunity extends Model
{
    protected $table = 'crm_opportunities';

    protected $fillable = [
        'contact_id', 'name', 'stage', 'probability', 'estimated_value',
        'expected_close_date', 'actual_close_date', 'assigned_to',
        'lead_source', 'campaign_id', 'sales_order_id', 'loss_reason',
        'next_step', 'description', 'metadata'
    ];

    protected $casts = [
        'probability' => 'integer',
        'estimated_value' => 'float',
        'expected_close_date' => 'date',
        'actual_close_date' => 'date',
        'metadata' => 'array',
    ];

    public function contact(): BelongsTo;
    public function assignedTo(): BelongsTo; // User
    public function products(): HasMany; // OpportunityProduct
    public function salesOrder(): BelongsTo; // When won
    public function campaign(): BelongsTo;

    // Weighted value for forecasting
    public function getWeightedValueAttribute(): float
    {
        return $this->estimated_value * ($this->probability / 100);
    }
}
```

**OpportunityProduct Model:**
```php
class OpportunityProduct extends Model
{
    protected $table = 'crm_opportunity_products';

    protected $fillable = [
        'opportunity_id', 'product_id', 'quantity', 'unit_price',
        'discount_percent', 'total_amount', 'notes'
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'discount_percent' => 'float',
        'total_amount' => 'float',
    ];

    public function opportunity(): BelongsTo;
    public function product(): BelongsTo;
}
```

#### 2.3 API Endpoints

```
GET    /api/v1/crm/opportunities                   List opportunities
POST   /api/v1/crm/opportunities                   Create opportunity
GET    /api/v1/crm/opportunities/{id}              Show opportunity
PATCH  /api/v1/crm/opportunities/{id}              Update opportunity
DELETE /api/v1/crm/opportunities/{id}              Delete opportunity
POST   /api/v1/crm/opportunities/{id}/products     Add product
PATCH  /api/v1/crm/opportunities/{id}/stage        Move to next stage
POST   /api/v1/crm/opportunities/{id}/win          Mark as won (create Sales Order)
POST   /api/v1/crm/opportunities/{id}/lose         Mark as lost
GET    /api/v1/crm/opportunities/pipeline          Pipeline view (by stage)
GET    /api/v1/crm/opportunities/forecast          Sales forecast
```

#### 2.4 Business Logic

**OpportunityService:**
- `createOpportunity()` - Create with products
- `moveToStage()` - Update stage + probability
- `markAsWon()` - Create SalesOrder, update stage
- `markAsLost()` - Update stage, record reason
- `calculatePipelineValue()` - Sum by stage
- `generateForecast()` - Weighted pipeline value

**Win Logic:**
```php
public function markAsWon(Opportunity $opportunity): SalesOrder
{
    // 1. Create Sales Order from opportunity
    $salesOrder = SalesOrder::create([
        'contact_id' => $opportunity->contact_id,
        'status' => 'draft',
        // ... other fields
    ]);

    // 2. Copy products
    foreach ($opportunity->products as $oppProduct) {
        SalesOrderItem::create([
            'sales_order_id' => $salesOrder->id,
            'product_id' => $oppProduct->product_id,
            'quantity' => $oppProduct->quantity,
            'unit_price' => $oppProduct->unit_price,
            // ...
        ]);
    }

    // 3. Update opportunity
    $opportunity->update([
        'stage' => 'closed_won',
        'actual_close_date' => now(),
        'probability' => 100,
        'sales_order_id' => $salesOrder->id,
    ]);

    return $salesOrder;
}
```

#### 2.5 Testing

Create 7 test files (5 for Opportunity + 2 for OpportunityProduct)

**Test Scenarios:**
- Create opportunity with products
- Move through pipeline stages
- Win opportunity → creates Sales Order
- Lose opportunity with reason
- Pipeline view by stage
- Sales forecast calculation
- Permission: sales rep sees assigned only

---

### Stage 3: Marketing Campaigns (Day 3, 4-5 hours)

#### 3.1 Database Migration

**Table: `crm_campaigns`**
```sql
CREATE TABLE crm_campaigns (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(100) NOT NULL, -- email, social, event, webinar, advertising
    status VARCHAR(50) DEFAULT 'planning', -- planning, active, paused, completed
    start_date DATE,
    end_date DATE,
    budget_amount DECIMAL(10,2),
    actual_cost DECIMAL(10,2) DEFAULT 0,
    target_audience TEXT,
    description TEXT,
    expected_revenue DECIMAL(10,2),
    expected_leads INTEGER DEFAULT 0,
    assigned_to BIGINT UNSIGNED,
    metadata JSON,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaigns_status (status),
    INDEX idx_campaigns_type (type),
    INDEX idx_campaigns_dates (start_date, end_date)
);
```

**Table: `crm_campaign_members`**
```sql
CREATE TABLE crm_campaign_members (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    campaign_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED, -- Existing customer
    lead_id BIGINT UNSIGNED, -- Prospective customer
    status VARCHAR(50) DEFAULT 'sent', -- sent, responded, converted
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (campaign_id) REFERENCES crm_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (lead_id) REFERENCES crm_leads(id) ON DELETE CASCADE,
    INDEX idx_campaign_members_campaign (campaign_id),
    INDEX idx_campaign_members_status (status)
);
```

#### 3.2 Models & Endpoints

**Campaign Model:**
```php
class Campaign extends Model
{
    protected $table = 'crm_campaigns';

    public function members(): HasMany; // CampaignMember
    public function leads(): HasMany; // Leads generated from campaign
    public function opportunities(): HasMany; // Opportunities from campaign

    // Calculate ROI
    public function getRoiAttribute(): float
    {
        if ($this->actual_cost == 0) return 0;
        $revenue = $this->opportunities()->sum('estimated_value');
        return (($revenue - $this->actual_cost) / $this->actual_cost) * 100;
    }
}
```

**Endpoints:**
```
GET    /api/v1/crm/campaigns                  List campaigns
POST   /api/v1/crm/campaigns                  Create campaign
GET    /api/v1/crm/campaigns/{id}             Show campaign
PATCH  /api/v1/crm/campaigns/{id}             Update campaign
DELETE /api/v1/crm/campaigns/{id}             Delete campaign
POST   /api/v1/crm/campaigns/{id}/members     Add members (contacts/leads)
GET    /api/v1/crm/campaigns/{id}/performance ROI and stats
```

#### 3.3 Testing

Create 4 test files

**Test Scenarios:**
- Create campaign
- Add members (leads + contacts)
- Track responses
- Calculate ROI
- Filter by status/type

---

### Stage 4: Sales Pipeline & Analytics (Day 4, 4-5 hours)

#### 4.1 Analytics Service

**CRMAnalyticsService:**
```php
class CRMAnalyticsService
{
    // Pipeline value by stage
    public function getPipelineByStage(): Collection;

    // Sales forecast (weighted pipeline value)
    public function getSalesForecast(Carbon $startDate, Carbon $endDate): array;

    // Conversion rates (lead → opportunity → sale)
    public function getConversionRates(): array;

    // Sales rep performance
    public function getSalesRepPerformance(int $userId, string $period = 'month'): array;

    // Lead source effectiveness
    public function getLeadSourceAnalysis(): Collection;

    // Campaign performance comparison
    public function getCampaignPerformance(): Collection;

    // Win/Loss analysis
    public function getWinLossAnalysis(string $period = 'quarter'): array;
}
```

#### 4.2 Dashboard Endpoints

```
GET /api/v1/crm/dashboard/pipeline           Pipeline by stage
GET /api/v1/crm/dashboard/forecast           Sales forecast
GET /api/v1/crm/dashboard/conversion-rates   Lead/Opportunity conversion
GET /api/v1/crm/dashboard/sales-rep/{id}     Rep performance
GET /api/v1/crm/dashboard/lead-sources       Source effectiveness
GET /api/v1/crm/dashboard/campaign-roi       Campaign ROI
```

#### 4.3 Testing

Create 2 test files for analytics

**Test Scenarios:**
- Pipeline calculation by stage
- Sales forecast accuracy
- Conversion rate tracking
- Rep performance metrics

---

### Stage 5: Integration & Polish (Day 5, 4-5 hours)

#### 5.1 Integration Points

**With Sales Module:**
- Opportunity → Sales Order conversion
- Contact sharing (Party Pattern)

**With Finance Module:**
- Opportunity value → Revenue forecasting
- Won opportunities → AR Invoices

**With Ecommerce:**
- Website leads capture
- Ecommerce customer → CRM contact sync

#### 5.2 Notifications

**Email Notifications:**
- New lead assigned
- Opportunity stage changed
- Deal won/lost
- Campaign started
- Activity reminder

#### 5.3 Permissions

```php
// Lead permissions
'crm.leads.index'
'crm.leads.show'
'crm.leads.store'
'crm.leads.update'
'crm.leads.destroy'
'crm.leads.convert'

// Opportunity permissions
'crm.opportunities.*'
'crm.opportunities.win'

// Campaign permissions
'crm.campaigns.*'

// Analytics (admin/manager only)
'crm.analytics.view'
```

#### 5.4 Final Testing

Run full test suite (35+ test files, 175+ tests)

---

## Database Schema Summary

**New Tables:** 7
- `crm_leads` (19 columns, 5 indexes)
- `crm_lead_activities` (9 columns, 3 indexes)
- `crm_opportunities` (19 columns, 4 indexes)
- `crm_opportunity_products` (9 columns, 1 index)
- `crm_campaigns` (15 columns, 3 indexes)
- `crm_campaign_members` (7 columns, 2 indexes)

**Integration Tables:** 0 (uses existing contacts, products, sales_orders)

---

## API Endpoints Summary

| Entity | Endpoints |
|--------|-----------|
| Leads | 7 |
| Lead Activities | 2 |
| Opportunities | 10 |
| Opportunity Products | 1 |
| Campaigns | 6 |
| Analytics Dashboard | 6 |
| **TOTAL** | **32** |

---

## Testing Summary

| Entity | Test Files | Est. Tests |
|--------|-----------|------------|
| Leads | 5 | 25+ |
| Lead Activities | 3 | 15+ |
| Opportunities | 7 | 35+ |
| Campaigns | 4 | 20+ |
| Analytics | 2 | 10+ |
| Integration | 3 | 15+ |
| **TOTAL** | **24** | **120+** |

---

## Permissions Structure

**Roles:**
- **Sales Rep:** Own leads/opportunities, create activities
- **Sales Manager:** All leads/opportunities, team analytics
- **Marketing:** Campaigns, lead sources
- **Admin/God:** Full access

---

## Success Criteria

**Functional:**
- [ ] Sales reps can manage leads and opportunities
- [ ] Lead-to-customer conversion works
- [ ] Opportunity-to-sales-order conversion works
- [ ] Pipeline view displays correctly
- [ ] Sales forecast accurate
- [ ] Campaign ROI tracking functional
- [ ] Email notifications sending

**Technical:**
- [ ] 24+ test files, 120+ tests passing
- [ ] JSON:API 1.1 compliant
- [ ] Proper authorization (role-based)
- [ ] Integration with Sales/Finance modules
- [ ] API < 200ms (p95)

---

## Effort Breakdown

| Stage | Duration | Complexity |
|-------|----------|------------|
| Lead Management | 6-7 hours | Medium |
| Opportunities | 6-7 hours | Medium |
| Campaigns | 4-5 hours | Low-Medium |
| Analytics | 4-5 hours | Medium |
| Integration & Polish | 4-5 hours | Medium |
| **TOTAL** | **24-29 hours** | **4-5 days** |

---

**Document Status:** Planning Complete
**Last Updated:** 2025-10-29
**Next Action:** Review and approve
