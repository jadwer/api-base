# CRM Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Lead | `/api/v1/leads` | Sales leads |
| Opportunity | `/api/v1/opportunities` | Sales opportunities |
| Campaign | `/api/v1/campaigns` | Marketing campaigns |
| Activity | `/api/v1/activities` | Follow-up activities |

## Lead

```typescript
type LeadStatus = 'new' | 'contacted' | 'qualified' | 'unqualified' | 'converted';
type LeadSource = 'website' | 'referral' | 'trade show' | 'cold call' | 'email campaign' | 'social media' | 'other';
type LeadRating = 'hot' | 'warm' | 'cold';

interface Lead {
  id: string;
  title: string;             // Lead title/deal name
  source: LeadSource;
  status: LeadStatus;
  rating: LeadRating;

  // Contact info
  companyName: string;
  contactPerson: string;
  email: string;
  phone: string | null;

  // Assignment
  userId: number | null;     // Assigned sales rep
  contactId: number | null;  // Linked contact (after qualification)

  // Value
  estimatedValue: number | null;
  estimatedCloseDate: string | null;
  convertedAt: string | null;

  notes: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
}

// List leads
GET /api/v1/leads?filter[status]=new&filter[rating]=hot&include=user&sort=-createdAt

// Create lead
POST /api/v1/leads
{
  "data": {
    "type": "leads",
    "attributes": {
      "title": "ERP Implementation for Acme Corp",
      "source": "website",
      "status": "new",
      "rating": "hot",
      "companyName": "Acme Corporation",
      "contactPerson": "John Smith",
      "email": "john@acme.com",
      "phone": "+1 555 1234",
      "estimatedValue": 50000.00,
      "estimatedCloseDate": "2026-03-01",
      "userId": 5
    }
  }
}

// Update lead status
PATCH /api/v1/leads/{id}
{
  "data": {
    "type": "leads",
    "id": "1",
    "attributes": {
      "status": "qualified",
      "rating": "hot"
    }
  }
}

// Convert lead to opportunity
POST /api/v1/leads/{id}/convert
{
  "create_contact": true,
  "create_opportunity": true
}

// Response
{
  "lead": { "id": 1, "status": "converted" },
  "contact": { "id": 15, "name": "Acme Corporation" },
  "opportunity": { "id": 8, "title": "ERP Implementation for Acme Corp" }
}
```

### Filters

| Filter | Example |
|--------|---------|
| `filter[status]` | `?filter[status]=new` |
| `filter[rating]` | `?filter[rating]=hot` |
| `filter[source]` | `?filter[source]=website` |
| `filter[user_id]` | `?filter[user_id]=5` |
| `filter[estimated_value][gte]` | `?filter[estimated_value][gte]=10000` |

## Opportunity

```typescript
type OpportunityStage = 'prospecting' | 'qualification' | 'proposal' | 'negotiation' | 'closed_won' | 'closed_lost';

interface Opportunity {
  id: string;
  title: string;
  contactId: number;
  userId: number;            // Sales rep
  leadId: number | null;     // Source lead

  // Pipeline
  stage: OpportunityStage;
  probability: number;       // 0-100%

  // Value
  amount: number;
  expectedCloseDate: string;

  // Result
  closedAt: string | null;
  lostReason: string | null;

  notes: string | null;
  createdAt: string;
}

// List opportunities
GET /api/v1/opportunities?filter[stage]=proposal&include=contact,user

// Pipeline view (grouped by stage)
GET /api/v1/opportunities/pipeline

// Response
{
  "pipeline": [
    { "stage": "prospecting", "count": 10, "value": 150000.00 },
    { "stage": "qualification", "count": 8, "value": 200000.00 },
    { "stage": "proposal", "count": 5, "value": 300000.00 },
    { "stage": "negotiation", "count": 3, "value": 250000.00 }
  ],
  "total": 26,
  "totalValue": 900000.00,
  "weightedValue": 450000.00  // Value * probability
}

// Create opportunity
POST /api/v1/opportunities
{
  "data": {
    "type": "opportunities",
    "attributes": {
      "title": "Annual Support Contract",
      "contactId": 10,
      "userId": 5,
      "stage": "qualification",
      "probability": 50,
      "amount": 25000.00,
      "expectedCloseDate": "2026-02-15"
    }
  }
}

// Move to next stage
PATCH /api/v1/opportunities/{id}
{
  "data": {
    "type": "opportunities",
    "id": "1",
    "attributes": {
      "stage": "proposal",
      "probability": 70
    }
  }
}

// Close won
POST /api/v1/opportunities/{id}/close-won
{
  "notes": "Signed 2-year contract"
}

// Close lost
POST /api/v1/opportunities/{id}/close-lost
{
  "reason": "Budget constraints",
  "notes": "Follow up in Q3"
}
```

## Campaign

```typescript
type CampaignStatus = 'draft' | 'scheduled' | 'active' | 'paused' | 'completed';
type CampaignType = 'email' | 'social' | 'event' | 'webinar' | 'advertising' | 'other';

interface Campaign {
  id: string;
  name: string;
  type: CampaignType;
  status: CampaignStatus;
  startDate: string;
  endDate: string | null;

  // Budget
  budget: number | null;
  actualCost: number | null;

  // Metrics
  targetAudience: number | null;
  leads: number;
  opportunities: number;
  revenue: number;

  description: string | null;
  createdAt: string;
}

// List campaigns
GET /api/v1/campaigns?filter[status]=active

// Get campaign with metrics
GET /api/v1/campaigns/{id}

// Response includes calculated metrics
{
  "data": {
    "attributes": {
      "leads": 50,
      "opportunities": 10,
      "revenue": 150000.00,
      "roi": 300  // (revenue - actualCost) / actualCost * 100
    }
  }
}

// Create campaign
POST /api/v1/campaigns
{
  "data": {
    "type": "campaigns",
    "attributes": {
      "name": "Q1 Product Launch",
      "type": "email",
      "startDate": "2026-01-15",
      "endDate": "2026-02-28",
      "budget": 5000.00,
      "targetAudience": 10000
    }
  }
}
```

## Activity

```typescript
type ActivityType = 'call' | 'email' | 'meeting' | 'task' | 'note';
type ActivityStatus = 'scheduled' | 'completed' | 'cancelled';

interface Activity {
  id: string;
  type: ActivityType;
  subject: string;
  description: string | null;
  status: ActivityStatus;

  // Scheduling
  scheduledAt: string;
  completedAt: string | null;
  duration: number | null;    // Minutes

  // Relations
  userId: number;             // Assigned to
  contactId: number | null;
  leadId: number | null;
  opportunityId: number | null;

  outcome: string | null;     // Call result, meeting notes
  createdAt: string;
}

// List activities
GET /api/v1/activities?filter[user_id]=5&filter[status]=scheduled&sort=scheduledAt

// Get activities for contact
GET /api/v1/activities?filter[contact_id]=10&include=user

// Create activity
POST /api/v1/activities
{
  "data": {
    "type": "activities",
    "attributes": {
      "type": "call",
      "subject": "Follow-up call",
      "scheduledAt": "2026-01-08T10:00:00Z",
      "userId": 5,
      "leadId": 10
    }
  }
}

// Complete activity
PATCH /api/v1/activities/{id}
{
  "data": {
    "type": "activities",
    "id": "1",
    "attributes": {
      "status": "completed",
      "completedAt": "2026-01-08T10:30:00Z",
      "duration": 30,
      "outcome": "Customer interested, sending proposal"
    }
  }
}

// Log activity quickly
POST /api/v1/activities/log
{
  "type": "note",
  "subject": "Customer called",
  "description": "Asked about pricing",
  "contact_id": 10
}
```

## Pipeline Management

```typescript
// Get pipeline summary
GET /api/v1/opportunities/pipeline

// Get forecast
GET /api/v1/opportunities/forecast

// Response
{
  "thisMonth": {
    "expected": 150000.00,
    "weighted": 75000.00,
    "closed": 50000.00
  },
  "nextMonth": {
    "expected": 200000.00,
    "weighted": 100000.00
  },
  "quarter": {
    "target": 500000.00,
    "achieved": 50000.00,
    "pipeline": 350000.00
  }
}
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Lead Conversion | Creates contact + opportunity | Handle multi-step |
| Stage Progression | Forward only (mostly) | Validate transitions |
| Activity Assignment | Must have user assigned | Require user |
| Campaign Tracking | Links leads to campaigns | Track attribution |
| Probability | Auto-suggest by stage | Pre-fill probability |

## Stage-Probability Mapping

| Stage | Default Probability |
|-------|---------------------|
| prospecting | 10% |
| qualification | 25% |
| proposal | 50% |
| negotiation | 75% |
| closed_won | 100% |
| closed_lost | 0% |

## Dashboard Metrics

```typescript
// Get CRM dashboard
GET /api/v1/crm/dashboard

// Response
{
  "leads": {
    "new": 15,
    "contacted": 10,
    "qualified": 5
  },
  "opportunities": {
    "active": 20,
    "closingThisMonth": 5,
    "value": 250000.00
  },
  "activities": {
    "overdue": 3,
    "today": 8,
    "thisWeek": 25
  },
  "performance": {
    "conversionRate": 25,  // Leads to opportunities
    "winRate": 40,         // Opportunities won
    "avgDealSize": 35000.00
  }
}
```
