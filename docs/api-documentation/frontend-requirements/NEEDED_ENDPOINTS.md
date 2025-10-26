# NEEDED ENDPOINTS - Frontend Requirements

**Purpose:** Frontend team writes here what endpoints they need
**Last Updated:** 2025-10-25

---

## HOW TO USE THIS FILE

1. **Frontend:** Add your endpoint requests below
2. **Backend:** Review requests and implement with priority
3. **Both:** Update status when complete

---

## TEMPLATE FOR NEW REQUESTS

```markdown
### [Request ID] - [Brief Description]

**Requested by:** [Frontend Dev Name]
**Date:** YYYY-MM-DD
**Priority:** High / Medium / Low
**Module:** [Module name]

**Endpoint:**
```http
[METHOD] /api/v1/[resource-name]
```

**Use Case:**
[Describe why you need this endpoint]

**Expected Request:**
```json
{
  "data": {
    "type": "resource-name",
    "attributes": {
      "field1": "value1"
    }
  }
}
```

**Expected Response:**
```json
{
  "data": {
    "type": "resource-name",
    "id": "1",
    "attributes": {
      "field1": "value1"
    }
  }
}
```

**Status:** ⏳ Pending / 🔨 In Progress / ✅ Completed
**Backend Response:** [Backend team writes here]
```

---

## CURRENT REQUESTS

### REQ-001 - Payment Application Dashboard

**Requested by:** Frontend Team
**Date:** 2025-10-25
**Priority:** Medium
**Module:** Finance

**Endpoint:**
```http
GET /api/v1/payments/unapplied
```

**Use Case:**
Show all payments that haven't been fully applied to invoices yet. Needed for "Apply Payment" screen.

**Expected Response:**
```json
{
  "data": [
    {
      "type": "payments",
      "id": "123",
      "attributes": {
        "paymentNumber": "PAY-001",
        "amount": 1500.00,
        "appliedAmount": 500.00,
        "unappliedAmount": 1000.00,
        "status": "partial"
      }
    }
  ]
}
```

**Status:** ⏳ Pending
**Backend Response:** Can use existing `/api/v1/payments?filter[status]=unapplied` or `/api/v1/payments?filter[status]=partial`

---

### REQ-002 - Invoice Aging Report Data

**Requested by:** Frontend Team
**Date:** 2025-10-25
**Priority:** Low
**Module:** Finance

**Endpoint:**
```http
GET /api/v1/reports/ar-aging
```

**Use Case:**
Display aging report showing invoices grouped by days overdue (0-30, 31-60, 61-90, 90+)

**Expected Response:**
```json
{
  "data": {
    "type": "ar-aging-report",
    "attributes": {
      "current": 5000.00,
      "days_1_30": 3000.00,
      "days_31_60": 1500.00,
      "days_61_90": 500.00,
      "days_90_plus": 200.00,
      "total": 10200.00
    }
  }
}
```

**Status:** ⏳ Pending
**Backend Response:** [Backend will implement in Phase 3]

---

## EXAMPLES (Delete after reading)

### Example Request - Bulk Product Update

**Requested by:** John Doe
**Date:** 2025-01-15
**Priority:** High
**Module:** Products

**Endpoint:**
```http
PATCH /api/v1/products/bulk-update
```

**Use Case:**
Update prices for multiple products at once instead of one by one.

**Expected Request:**
```json
{
  "data": [
    { "id": "1", "attributes": { "price": 99.99 } },
    { "id": "2", "attributes": { "price": 149.99 } }
  ]
}
```

**Status:** ✅ Completed
**Backend Response:** Implemented - use standard PATCH with array of resources.

---

**Instructions:**
- Use REQ-XXX numbering
- Be specific about use case
- Provide example payloads
- Backend will respond with ETA or alternative solution
