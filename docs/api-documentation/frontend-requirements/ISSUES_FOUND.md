# ISSUES FOUND - Frontend Reporting

**Purpose:** Frontend reports API issues here for Backend to investigate
**Last Updated:** 2025-10-25

---

## HOW TO REPORT AN ISSUE

```markdown
### ISSUE-XXX - [Brief Description]

**Reported by:** [Your Name]
**Date:** YYYY-MM-DD
**Severity:** Critical / High / Medium / Low
**Module:** [Module name]

**Endpoint:**
```http
[METHOD] /api/v1/[endpoint]
```

**What I sent:**
```json
{
  "data": { ... }
}
```

**What I got:**
```json
{
  "errors": [ ... ]
}
```

**What I expected:**
[Describe expected behavior]

**Steps to Reproduce:**
1. Step 1
2. Step 2
3. ...

**Status:** 🔴 Open / 🔍 Investigating / 🔨 Fixing / ✅ Resolved
**Backend Response:** [Backend team writes here]
```

---

## OPEN ISSUES

### ISSUE-001 - Finance URLs Not Working

**Reported by:** Frontend Team
**Date:** 2025-10-25
**Severity:** Critical
**Module:** Finance

**Endpoint:**
```http
GET /api/v1/a-p-invoices
```

**What I got:**
```
404 Not Found
```

**What I expected:**
List of AP Invoices

**Steps to Reproduce:**
1. Call `/api/v1/a-p-invoices`
2. Get 404 error

**Status:** ✅ Resolved
**Backend Response:** URLs changed from `a-p-invoices` to `ap-invoices`. See BREAKING_CHANGES.md. Frontend needs update.

---

### ISSUE-002 - Payment Relationship Returns Null

**Reported by:** Frontend Team
**Date:** 2025-10-25
**Severity:** High
**Module:** Finance

**Endpoint:**
```http
GET /api/v1/payments/123?include=journalEntry
```

**What I got:**
```json
{
  "data": {
    "id": "123",
    "attributes": {
      "journalEntryId": 456
    },
    "relationships": {
      "journalEntry": { "data": null }
    }
  }
}
```

**What I expected:**
`journalEntry` should have data when `journalEntryId` exists

**Steps to Reproduce:**
1. Create payment
2. Request with `?include=journalEntry`
3. Relationship is null despite journalEntryId existing

**Status:** 🔍 Investigating
**Backend Response:** This is a known issue in 3 integration tests. Under investigation. Workaround: fetch journal entry separately for now.

---

## RESOLVED ISSUES

### ISSUE-003 - Validation Errors in Spanish

**Reported by:** Frontend Team
**Date:** 2025-08-15
**Severity:** Low
**Module:** All

**Issue:** Error messages are in Spanish but Frontend expects English

**Status:** ✅ Resolved
**Backend Response:** This is intentional for Spanish-speaking users. Frontend can translate or accept Spanish messages.

---

**Instructions:**
- Use ISSUE-XXX numbering
- Include actual payloads and responses
- Backend will investigate and respond
- Update status when resolved
