# Phase 4.5: CRM Module Implementation Plan

**Status:** 🟡 In Planning
**Estimated Duration:** 4-5 days
**Complexity:** Medium (3/5)
**Business Value:** High for B2B companies
**Methodology:** Following `docs/development/MODULE_IMPLEMENTATION_METHODOLOGY.md`

---

## 📋 OBJECTIVES

Implement a complete Customer Relationship Management module with:
- Lead management and qualification
- Sales opportunity pipeline
- Quote/proposal generation
- Activity tracking (calls, emails, meetings)
- Marketing campaign management
- Sales forecasting
- Integration with existing Sales module

---

## 🏗️ MODULE ARCHITECTURE

### Entities (7 core entities)

#### 1. **Lead** - Prospective customers
**Purpose:** Track potential customers before they become qualified opportunities
**Fields:**
- `title` (string) - Lead title/name
- `source` (string) - Lead source (website, referral, cold call, etc.)
- `status` (enum) - new, contacted, qualified, unqualified, converted
- `rating` (enum) - hot, warm, cold
- `contact_id` (FK nullable) - Linked contact (when converted)
- `user_id` (FK) - Assigned sales rep
- `company_name` (string)
- `contact_person` (string)
- `email` (string)
- `phone` (string)
- `estimated_value` (decimal)
- `estimated_close_date` (date nullable)
- `converted_at` (timestamp nullable)
- `notes` (text nullable)
- `metadata` (json nullable)

**Relationships:**
- `belongsTo` Contact (nullable - when converted)
- `belongsTo` User (assigned rep)
- `hasMany` Activity
- `hasOne` Opportunity (when converted)
- `belongsToMany` Campaign

#### 2. **Opportunity** - Qualified sales opportunities
**Purpose:** Manage active sales opportunities through pipeline stages
**Fields:**
- `name` (string) - Opportunity name
- `contact_id` (FK) - Associated customer contact
- `lead_id` (FK nullable) - Original lead (if converted)
- `user_id` (FK) - Account owner/sales rep
- `stage` (enum) - prospecting, qualification, proposal, negotiation, closed_won, closed_lost
- `probability` (integer) - Win probability % (0-100)
- `amount` (decimal) - Expected deal value
- `expected_close_date` (date)
- `actual_close_date` (date nullable)
- `next_step` (string nullable)
- `competitors` (string nullable)
- `loss_reason` (string nullable) - Why lost
- `notes` (text nullable)
- `metadata` (json nullable)

**Relationships:**
- `belongsTo` Contact
- `belongsTo` Lead (nullable)
- `belongsTo` User (owner)
- `hasMany` Activity
- `hasMany` Quote
- `hasOne` SalesOrder (when won)

#### 3. **Quote** - Sales quotes/proposals
**Purpose:** Generate formal quotes for opportunities
**Fields:**
- `quote_number` (string unique) - Auto-generated quote #
- `opportunity_id` (FK nullable) - Related opportunity
- `contact_id` (FK) - Customer contact
- `user_id` (FK) - Quote creator
- `issue_date` (date)
- `valid_until` (date)
- `status` (enum) - draft, sent, accepted, rejected, expired
- `subtotal` (decimal)
- `tax_amount` (decimal)
- `discount_amount` (decimal)
- `total_amount` (decimal)
- `terms_conditions` (text nullable)
- `notes` (text nullable)
- `sales_order_id` (FK nullable) - When accepted and converted
- `metadata` (json nullable)

**Relationships:**
- `belongsTo` Opportunity (nullable)
- `belongsTo` Contact
- `belongsTo` User (creator)
- `hasMany` QuoteItem
- `hasOne` SalesOrder (when accepted)

#### 4. **QuoteItem** - Line items in quotes
**Purpose:** Individual products/services in quote
**Fields:**
- `quote_id` (FK)
- `product_id` (FK nullable) - Reference to Product if catalog item
- `name` (string) - Product/service name
- `description` (text nullable)
- `quantity` (decimal)
- `unit_price` (decimal)
- `discount_percent` (decimal nullable)
- `discount_amount` (decimal nullable)
- `tax_percent` (decimal)
- `subtotal` (decimal) - Calculated
- `total` (decimal) - Calculated
- `sort_order` (integer)

**Relationships:**
- `belongsTo` Quote
- `belongsTo` Product (nullable)

#### 5. **Activity** - Interaction tracking
**Purpose:** Log all customer interactions (calls, emails, meetings, notes)
**Fields:**
- `subject` (string)
- `type` (enum) - call, email, meeting, task, note
- `status` (enum) - planned, completed, cancelled
- `activityable_type` (string) - Polymorphic (Lead, Opportunity, Contact)
- `activityable_id` (bigint) - Polymorphic ID
- `user_id` (FK) - Activity owner
- `due_date` (date nullable) - For tasks
- `completed_at` (timestamp nullable)
- `duration_minutes` (integer nullable)
- `description` (text nullable)
- `outcome` (text nullable)
- `metadata` (json nullable)

**Relationships:**
- `morphTo` Activityable (Lead, Opportunity, Contact)
- `belongsTo` User (owner)

#### 6. **Campaign** - Marketing campaigns
**Purpose:** Organize and track marketing campaigns
**Fields:**
- `name` (string)
- `type` (enum) - email, webinar, conference, advertisement, social_media, other
- `status` (enum) - planning, active, completed, cancelled
- `start_date` (date)
- `end_date` (date nullable)
- `budget` (decimal nullable)
- `actual_cost` (decimal nullable)
- `expected_revenue` (decimal nullable)
- `expected_response_rate` (decimal nullable) - Percentage
- `user_id` (FK) - Campaign owner
- `description` (text nullable)
- `notes` (text nullable)
- `metadata` (json nullable)

**Relationships:**
- `belongsTo` User (owner)
- `belongsToMany` Lead (campaign_lead pivot)
- `hasMany` Opportunity (through converted leads)

#### 7. **PipelineStage** - Pipeline configuration
**Purpose:** Define custom pipeline stages
**Fields:**
- `name` (string)
- `type` (enum) - lead, opportunity
- `probability` (integer) - Default win probability for this stage
- `sort_order` (integer)
- `is_active` (boolean)
- `is_closed_won` (boolean)
- `is_closed_lost` (boolean)

**Relationships:**
- None (configuration table)

---

## 🔄 INTEGRATION POINTS

### With Existing Modules

**1. Sales Module:**
- `Quote` → `SalesOrder` (when quote accepted)
- `Opportunity.sales_order_id` FK to sales_orders

**2. Contacts Module:**
- `Lead.contact_id` FK to contacts
- `Opportunity.contact_id` FK to contacts
- `Quote.contact_id` FK to contacts

**3. Product Module:**
- `QuoteItem.product_id` FK to products

**4. User Module:**
- `Lead.user_id`, `Opportunity.user_id`, etc. FK to users

---

## 📊 IMPLEMENTATION PHASES

### **Phase 0: Setup & Permissions** (2-3 hours)

**Deliverables:**
- [x] Module structure created
- [x] PermissionsSeeder with 35 permissions (7 entities × 5 actions)
- [x] Server.php updated
- [x] DatabaseSeeder.php updated
- [x] TestCase.php updated
- [x] RouteServiceProvider configured

**Permissions:**
```
crm.leads.index, show, store, update, destroy
crm.opportunities.index, show, store, update, destroy
crm.quotes.index, show, store, update, destroy
crm.quote-items.index, show, store, update, destroy
crm.activities.index, show, store, update, destroy
crm.campaigns.index, show, store, update, destroy
crm.pipeline-stages.index, show, store, update, destroy
```

**Role Assignments:**
- `god`: All 35 permissions
- `admin`: All 35 permissions
- `tech`: Read-only (index, show) - 14 permissions
- `customer`: None (internal tool)

---

### **Phase 1: Base Entities** (8-10 hours)

**Order:** PipelineStage → Lead → Campaign → Activity

1. **PipelineStage** (2 hours)
   - Migration + Model + Factory
   - Schema + Authorizer + Request + Resource
   - Controller + Routes
   - Server.php registration

2. **Lead** (2.5 hours)
   - Migration + Model + Factory
   - Schema + Authorizer + Request + Resource
   - Controller + Routes
   - Server.php registration

3. **Campaign** (2 hours)
   - Migration + Model + Factory
   - Schema + Authorizer + Request + Resource
   - Controller + Routes
   - campaign_lead pivot migration

4. **Activity** (2.5 hours)
   - Migration (polymorphic) + Model + Factory
   - Schema + Authorizer + Request + Resource
   - Controller + Routes

---

### **Phase 2: Opportunity & Quote Entities** (8-10 hours)

5. **Opportunity** (3 hours)
   - Migration + Model + Factory
   - Schema + Authorizer + Request + Resource
   - Controller + Routes
   - Lead conversion logic

6. **Quote** (2.5 hours)
   - Migration + Model + Factory
   - Schema + Authorizer + Request + Resource
   - Controller + Routes
   - Quote number generation logic

7. **QuoteItem** (2.5 hours)
   - Migration + Model + Factory
   - Schema + Authorizer + Request + Resource
   - Controller + Routes

---

### **Phase 3: Business Logic & Services** (4-6 hours)

**Services to implement:**

1. **LeadConversionService** (2 hours)
   - Convert lead to opportunity
   - Create contact if doesn't exist
   - Transfer activities
   - Update lead status

2. **QuoteGenerationService** (1.5 hours)
   - Generate quote number (format: QUO-{YEAR}-{SEQ})
   - Calculate totals (subtotal, tax, discount)
   - PDF generation (optional for later)

3. **OpportunityPipelineService** (1.5 hours)
   - Move through stages
   - Update probability automatically
   - Sales forecasting calculations
   - Win/Loss tracking

4. **SalesOrderConversionService** (1 hour)
   - Convert accepted quote to sales order
   - Copy quote items to order items
   - Link opportunity to order

---

### **Phase 4: Custom Endpoints** (3-4 hours)

**Additional endpoints beyond CRUD:**

1. **Lead Operations:**
   - `POST /leads/{id}/convert` - Convert to opportunity
   - `POST /leads/{id}/qualify` - Mark as qualified
   - `POST /leads/{id}/disqualify` - Mark as unqualified

2. **Opportunity Operations:**
   - `POST /opportunities/{id}/advance-stage` - Move to next stage
   - `POST /opportunities/{id}/close-won` - Mark won
   - `POST /opportunities/{id}/close-lost` - Mark lost with reason
   - `GET /opportunities/forecast` - Sales forecast by period

3. **Quote Operations:**
   - `POST /quotes/{id}/send` - Send to customer (mark as sent)
   - `POST /quotes/{id}/accept` - Accept and create sales order
   - `POST /quotes/{id}/reject` - Reject quote
   - `GET /quotes/{id}/pdf` - Generate PDF (future)

4. **Activity Operations:**
   - `GET /activities/calendar` - Calendar view
   - `GET /activities/upcoming` - Upcoming tasks

5. **Campaign Operations:**
   - `POST /campaigns/{id}/add-leads` - Bulk add leads
   - `GET /campaigns/{id}/stats` - Campaign statistics

---

### **Phase 5: Comprehensive Testing** (6-8 hours)

**Test Coverage:**
- 5 test files per entity (Index, Show, Store, Update, Destroy)
- Business logic tests (conversions, calculations)
- Integration tests (Quote → SalesOrder, Lead → Opportunity)
- Permission tests (all roles)

**Total Test Files:** 40+ test files

---

## 📈 SUCCESS METRICS

**Code Statistics Target:**
- **Entities:** 7 (Lead, Opportunity, Quote, QuoteItem, Activity, Campaign, PipelineStage)
- **Migrations:** 8 (7 tables + 1 pivot)
- **API Endpoints:** 40+ (35 CRUD + 10+ custom)
- **Permissions:** 35 (7 entities × 5 actions)
- **Tests:** 45+ test files
- **Services:** 4 business logic services
- **Production Code:** ~5,500 lines
- **Test Code:** ~3,500 lines

**Business Value:**
- Complete lead-to-cash workflow
- Sales pipeline visibility
- Quote generation and tracking
- Activity tracking for customer engagement
- Campaign ROI measurement

---

## 🎯 VALIDATION CHECKLIST

### Architecture
- [ ] All entities use Party Pattern (`contact_id`)
- [ ] All foreign keys properly indexed
- [ ] All polymorphic relationships configured
- [ ] All migrations MySQL + SQLite compatible

### JSON:API Compliance
- [ ] All schemas have `fields()`, `filters()`, `pagination()`
- [ ] All controllers use Actions traits (7 minimum)
- [ ] All authorizers have 10 methods (5 CRUD + 5 relationship)
- [ ] All resources properly map attributes

### Testing
- [ ] 5 test files per entity
- [ ] All permission scenarios tested
- [ ] Business logic services tested
- [ ] Integration flows tested

### Documentation
- [ ] All endpoints documented
- [ ] Business logic flows documented
- [ ] Integration points documented
- [ ] Frontend integration guide created

---

## 🚀 EXECUTION TIMELINE

**Day 1:**
- Phase 0: Setup & Permissions (3 hours)
- Phase 1: PipelineStage + Lead (4-5 hours)

**Day 2:**
- Phase 1: Campaign + Activity (4-5 hours)
- Phase 2: Opportunity (3 hours)

**Day 3:**
- Phase 2: Quote + QuoteItem (5 hours)
- Phase 3: LeadConversionService + QuoteGenerationService (3 hours)

**Day 4:**
- Phase 3: OpportunityPipelineService + SalesOrderConversionService (2.5 hours)
- Phase 4: Custom Endpoints (4 hours)

**Day 5:**
- Phase 5: Comprehensive Testing (8 hours)

**Total:** 4-5 days (~32-40 hours)

---

## 📝 NEXT STEPS

1. **Review and Approve Plan** - Get user confirmation
2. **Start Phase 0** - Create module structure and permissions
3. **Execute Phase 1-4** - Implement entities and business logic
4. **Execute Phase 5** - Comprehensive testing
5. **Documentation** - Create frontend integration guide
6. **Deployment** - Update production environment

---

**Document Status:** Ready for execution
**Methodology:** Validated with HR Module (0 errors)
**Risk Level:** Medium (well-defined scope, proven methodology)
