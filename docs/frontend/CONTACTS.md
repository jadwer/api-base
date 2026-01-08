# Contacts Module

## Entities

| Entity | Endpoint | Description |
|--------|----------|-------------|
| Contact | `/api/v1/contacts` | Customers & Suppliers |
| ContactAddress | `/api/v1/contact-addresses` | Addresses |
| ContactPerson | `/api/v1/contact-people` | Contact persons |
| ContactDocument | `/api/v1/contact-documents` | Documents (RFC, contracts) |

## Contact (Party Pattern)

A single contact can be both customer AND supplier (Party Pattern).

```typescript
interface Contact {
  id: string;
  name: string;
  email: string | null;
  phone: string | null;
  taxId: string | null;       // RFC in Mexico

  // Role flags (not mutually exclusive)
  isCustomer: boolean;
  isSupplier: boolean;
  isEmployee: boolean;

  // Customer-specific
  creditLimit: number;        // Default 0
  paymentTerms: number;       // Days, default 30

  // Status
  isActive: boolean;
  notes: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
}

// List customers only
GET /api/v1/contacts?filter[is_customer]=true

// List suppliers only
GET /api/v1/contacts?filter[is_supplier]=true

// Get contact with all related data
GET /api/v1/contacts/{id}?include=addresses,people,documents

// Create contact
POST /api/v1/contacts
{
  "data": {
    "type": "contacts",
    "attributes": {
      "name": "Acme Corporation",
      "email": "contact@acme.com",
      "phone": "+52 55 1234 5678",
      "taxId": "ACM850101XXX",
      "isCustomer": true,
      "isSupplier": false,
      "creditLimit": 50000.00,
      "paymentTerms": 30,
      "isActive": true
    }
  }
}
```

### Filters

| Filter | Example |
|--------|---------|
| `filter[name]` | `?filter[name]=Acme` |
| `filter[email]` | `?filter[email]=contact@acme.com` |
| `filter[tax_id]` | `?filter[tax_id]=ACM850101XXX` |
| `filter[is_customer]` | `?filter[is_customer]=true` |
| `filter[is_supplier]` | `?filter[is_supplier]=true` |
| `filter[is_active]` | `?filter[is_active]=true` |

## Contact Address

```typescript
type AddressType = 'billing' | 'shipping' | 'both';

interface ContactAddress {
  id: string;
  contactId: number;
  addressType: AddressType;
  street: string;
  city: string;
  state: string;
  postalCode: string;
  country: string;
  isDefault: boolean;
}

// Get addresses for contact
GET /api/v1/contact-addresses?filter[contact_id]=1

// Create address
POST /api/v1/contact-addresses
{
  "data": {
    "type": "contact-addresses",
    "attributes": {
      "contactId": 1,
      "addressType": "both",
      "street": "Av. Insurgentes Sur 1234",
      "city": "Mexico City",
      "state": "CDMX",
      "postalCode": "03100",
      "country": "Mexico",
      "isDefault": true
    }
  }
}
```

## Contact Person

```typescript
interface ContactPerson {
  id: string;
  contactId: number;
  name: string;
  email: string | null;
  phone: string | null;
  position: string | null;   // "Sales Manager"
  department: string | null; // "Sales"
  isPrimary: boolean;
}

// Get contact persons
GET /api/v1/contact-people?filter[contact_id]=1

// Create contact person
POST /api/v1/contact-people
{
  "data": {
    "type": "contact-people",
    "attributes": {
      "contactId": 1,
      "name": "Juan Perez",
      "email": "juan.perez@acme.com",
      "phone": "+52 55 9876 5432",
      "position": "Purchasing Manager",
      "department": "Procurement",
      "isPrimary": true
    }
  }
}
```

## Contact Document

```typescript
type DocumentType = 'rfc' | 'id' | 'contract' | 'certificate' | 'other';
type VerificationStatus = 'pending' | 'verified' | 'rejected';

interface ContactDocument {
  id: string;
  contactId: number;
  documentType: DocumentType;
  name: string;
  fileName: string;
  filePath: string;
  expirationDate: string | null;
  verificationStatus: VerificationStatus;
  verifiedAt: string | null;
  verifiedById: number | null;
  notes: string | null;
}

// Get documents for contact
GET /api/v1/contact-documents?filter[contact_id]=1

// Upload document (multipart form)
POST /api/v1/contact-documents/upload
Content-Type: multipart/form-data
- contact_id: 1
- document_type: "contract"
- name: "Service Agreement 2026"
- file: [binary]
- expiration_date: "2027-01-08"

// Download document
GET /api/v1/contact-documents/{id}/download

// View document (inline)
GET /api/v1/contact-documents/{id}/view

// Verify document (admin)
POST /api/v1/contact-documents/{id}/verify

// Reject verification
POST /api/v1/contact-documents/{id}/unverify
```

## Duplicate Detection

```typescript
// Check for potential duplicates before creating
GET /api/v1/contacts/check-duplicates?name=Acme&email=contact@acme.com&tax_id=ACM850101XXX

// Response
{
  "duplicates": [
    {
      "id": 5,
      "name": "Acme Corp",
      "email": "contact@acme.com",
      "matchScore": 0.85,
      "matchReasons": ["email", "name_similarity"]
    }
  ],
  "hasDuplicates": true
}
```

## Business Rules

| Rule | Description | Frontend Impact |
|------|-------------|-----------------|
| Party Pattern | Contact can be customer + supplier | Show both checkboxes |
| Credit Limit | Enforced for customers | Show credit status |
| Payment Terms | Default 30 days | Pre-fill form |
| Default Address | Each contact has one default | Toggle isDefault |
| Document Expiration | Track expiration dates | Show warnings |
| Duplicate Detection | Fuzzy matching on create | Show duplicates dialog |

## Credit Information (for Sales)

```typescript
// Get customer credit status
GET /api/v1/contacts/{id}/credit-status

// Response
{
  "creditLimit": 50000.00,
  "currentBalance": 35000.00,
  "availableCredit": 15000.00,
  "overdueAmount": 0.00,
  "paymentScore": 95,
  "isOnCreditHold": false
}
```

## Common Workflows

### Create Customer with Address
```typescript
// Step 1: Create contact
const contact = await createContact({
  name: "New Customer",
  isCustomer: true,
  creditLimit: 10000
});

// Step 2: Add address
await createAddress({
  contactId: contact.id,
  addressType: "both",
  isDefault: true,
  // ... address fields
});

// Step 3: Add contact person
await createContactPerson({
  contactId: contact.id,
  name: "Primary Contact",
  isPrimary: true,
  // ... person fields
});
```

### Check Duplicates Before Create
```typescript
async function createContactSafe(data: ContactData) {
  // Check duplicates first
  const duplicateCheck = await fetch(
    `/api/v1/contacts/check-duplicates?name=${data.name}&email=${data.email}`,
    { headers }
  );
  const { hasDuplicates, duplicates } = await duplicateCheck.json();

  if (hasDuplicates) {
    // Show confirmation dialog to user
    const proceed = await showDuplicateWarning(duplicates);
    if (!proceed) return null;
  }

  // Create contact
  return createContact(data);
}
```
