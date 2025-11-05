# Contacts Module - Frontend Integration Guide

**Module:** Contacts
**Entities:** 4 (Contact, ContactAddress, ContactPerson, ContactDocument)
**Endpoints:** 20
**Base Path:** `/api/v1`

## Overview

The Contacts module manages customers, suppliers, and general business contacts with support for multiple addresses, contact persons, and document attachments. This module is used throughout the system by Sales, Purchase, Finance, and Billing modules.

## Core Entities

### 1. Contact

**Endpoint:** `/contacts`
**Resource Type:** `contacts`

#### TypeScript Interface

```typescript
type ContactType = 'individual' | 'company' | 'government';
type ContactStatus = 'active' | 'inactive' | 'suspended' | 'archived';

interface Contact {
  id: string;
  contactType: ContactType;
  name: string;
  legalName: string | null;
  taxId: string | null;        // RFC for Mexico, Tax ID for other countries
  email: string | null;
  phone: string | null;
  website: string | null;
  status: ContactStatus;

  // Business relationships
  isCustomer: boolean;
  isSupplier: boolean;

  // Credit management
  creditLimit: number;
  currentCredit: number;

  // Classification
  classification: string | null;  // VIP, Regular, etc.
  paymentTerms: number | null;    // Days

  notes: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Field Mappings

| JSON:API Field | Database Column | Type | Required | Sortable | Filterable |
|---------------|-----------------|------|----------|----------|------------|
| `contactType` | `type` | string | Yes | Yes | Yes |
| `name` | `name` | string | Yes | Yes | Yes |
| `legalName` | `legal_name` | string | No | Yes | Yes |
| `taxId` | `tax_id` | string | No | Yes | Yes |
| `email` | `email` | string | No | Yes | Yes |
| `phone` | `phone` | string | No | Yes | Yes |
| `status` | `status` | string | Yes | Yes | Yes |
| `isCustomer` | `is_customer` | boolean | No | Yes | Yes |
| `isSupplier` | `is_supplier` | boolean | No | Yes | Yes |
| `creditLimit` | `credit_limit` | number | No | Yes | No |
| `classification` | `classification` | string | No | Yes | Yes |
| `paymentTerms` | `payment_terms` | number | No | Yes | Yes |

#### Relationships

- `contactAddresses` → ContactAddress[] (hasMany)
- `contactPeople` → ContactPerson[] (hasMany)
- `contactDocuments` → ContactDocument[] (hasMany)

---

### 2. ContactAddress

**Endpoint:** `/contact-addresses`
**Resource Type:** `contact-addresses`

#### TypeScript Interface

```typescript
type AddressType = 'billing' | 'shipping' | 'fiscal' | 'other';

interface ContactAddress {
  id: string;
  contactId: number;
  addressType: AddressType;
  addressLine1: string;
  addressLine2: string | null;
  city: string;
  state: string;
  country: string;
  postalCode: string;
  isDefault: boolean;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `contact` → Contact (belongsTo)

---

### 3. ContactPerson

**Endpoint:** `/contact-people`
**Resource Type:** `contact-people`

#### TypeScript Interface

```typescript
interface ContactPerson {
  id: string;
  contactId: number;
  name: string;
  position: string | null;
  department: string | null;
  email: string | null;
  phone: string | null;
  mobile: string | null;
  isPrimary: boolean;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `contact` → Contact (belongsTo)

---

### 4. ContactDocument

**Endpoint:** `/contact-documents`
**Resource Type:** `contact-documents`

#### TypeScript Interface

```typescript
type DocumentType = 'id_card' | 'tax_certificate' | 'contract' | 'license' | 'other';

interface ContactDocument {
  id: string;
  contactId: number;
  documentType: DocumentType;
  filePath: string;
  originalFilename: string;
  mimeType: string;
  fileSize: number;
  uploadedBy: number;
  verifiedAt: string | null;
  verifiedBy: number | null;
  expiresAt: string | null;
  notes: string | null;
  metadata: Record<string, any> | null;
  createdAt: string;
  updatedAt: string;
}
```

#### Relationships

- `contact` → Contact (belongsTo)

---

## Common Use Cases

### 1. Create Complete Contact with Address

```javascript
async function createContactWithAddress(contactData) {
  // 1. Create contact
  const contactPayload = {
    data: {
      type: "contacts",
      attributes: {
        contactType: "company",
        name: contactData.name,
        legalName: contactData.legalName,
        taxId: contactData.taxId,
        email: contactData.email,
        phone: contactData.phone,
        status: "active",
        isCustomer: true,
        isSupplier: false,
        creditLimit: 10000,
        currentCredit: 0,
        paymentTerms: 30
      }
    }
  };

  const contactResponse = await fetch('/api/v1/contacts', {
    method: 'POST',
    headers,
    body: JSON.stringify(contactPayload)
  });

  const contact = await contactResponse.json();
  const contactId = contact.data.id;

  // 2. Create billing address
  const addressPayload = {
    data: {
      type: "contact-addresses",
      attributes: {
        contactId: parseInt(contactId),
        addressType: "billing",
        addressLine1: contactData.address.street,
        addressLine2: contactData.address.suite,
        city: contactData.address.city,
        state: contactData.address.state,
        country: contactData.address.country,
        postalCode: contactData.address.postalCode,
        isDefault: true
      }
    }
  };

  await fetch('/api/v1/contact-addresses', {
    method: 'POST',
    headers,
    body: JSON.stringify(addressPayload)
  });

  return contact;
}
```

### 2. Add Contact Person

```javascript
async function addContactPerson(contactId, personData) {
  const payload = {
    data: {
      type: "contact-people",
      attributes: {
        contactId: contactId,
        name: personData.name,
        position: personData.position,
        department: personData.department,
        email: personData.email,
        phone: personData.phone,
        mobile: personData.mobile,
        isPrimary: personData.isPrimary || false
      }
    }
  };

  const response = await fetch('/api/v1/contact-people', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}
```

### 3. Upload Contact Document

```javascript
async function uploadContactDocument(contactId, file, documentType) {
  // Use multipart/form-data for file upload
  const formData = new FormData();
  formData.append('file', file);
  formData.append('contact_id', contactId);
  formData.append('document_type', documentType);
  formData.append('notes', 'Tax registration certificate');

  const response = await fetch('/api/v1/contact-documents/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      // Note: Don't set Content-Type header, browser will set it with boundary
    },
    body: formData
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.errors[0].detail);
  }

  return await response.json();
}
```

### 4. Get Contact with All Relations

```javascript
async function getContactComplete(contactId) {
  const response = await fetch(
    `/api/v1/contacts/${contactId}?include=contactAddresses,contactPeople,contactDocuments`,
    { headers }
  );

  const data = await response.json();

  return {
    contact: data.data,
    addresses: data.included.filter(inc => inc.type === 'contact-addresses'),
    people: data.included.filter(inc => inc.type === 'contact-people'),
    documents: data.included.filter(inc => inc.type === 'contact-documents')
  };
}
```

### 5. Search Contacts

```javascript
async function searchContacts(searchTerm, filters = {}) {
  const params = new URLSearchParams({
    'filter[name]': searchTerm,
    'sort': 'name',
    ...filters
  });

  const response = await fetch(`/api/v1/contacts?${params}`, { headers });
  return await response.json();
}

// Get all customers
const customers = await searchContacts('', { 'filter[isCustomer]': 'true' });

// Get all suppliers
const suppliers = await searchContacts('', { 'filter[isSupplier]': 'true' });

// Get active contacts
const active = await searchContacts('', { 'filter[status]': 'active' });
```

### 6. Update Credit Status

```javascript
async function updateCreditStatus(contactId, creditUsed) {
  // Get current contact
  const response = await fetch(`/api/v1/contacts/${contactId}`, { headers });
  const contact = await response.json();

  const currentCredit = contact.data.attributes.currentCredit;
  const creditLimit = contact.data.attributes.creditLimit;

  // Update current credit
  const updatePayload = {
    data: {
      type: "contacts",
      id: contactId,
      attributes: {
        currentCredit: currentCredit + creditUsed
      }
    }
  };

  const updateResponse = await fetch(`/api/v1/contacts/${contactId}`, {
    method: 'PATCH',
    headers,
    body: JSON.stringify(updatePayload)
  });

  const updated = await updateResponse.json();

  return {
    contactId: contactId,
    currentCredit: updated.data.attributes.currentCredit,
    creditLimit: creditLimit,
    availableCredit: creditLimit - updated.data.attributes.currentCredit,
    creditExceeded: updated.data.attributes.currentCredit > creditLimit
  };
}
```

### 7. Download Contact Document

```javascript
async function downloadContactDocument(documentId) {
  const response = await fetch(
    `/api/v1/contact-documents/${documentId}/download`,
    { headers }
  );

  if (!response.ok) {
    throw new Error('Download failed');
  }

  // Get filename from headers
  const contentDisposition = response.headers.get('content-disposition');
  const filename = contentDisposition
    ? contentDisposition.split('filename=')[1].replace(/"/g, '')
    : `document-${documentId}`;

  // Download file
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  window.URL.revokeObjectURL(url);
}
```

---

## Permissions

### Role-Based Access

| Role | Contacts | Addresses | People | Documents |
|------|----------|-----------|--------|-----------|
| **God** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Admin** | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ CRUD |
| **Tech** | ✅ Read | ✅ Read | ✅ Read | ✅ Read |
| **Customer** | ✅ Read (own) | ✅ Read (own) | ✅ Read (own) | ✅ Read (own) |

---

## Quick Reference

**Available Endpoints:**
- `GET /api/v1/contacts` - List contacts
- `POST /api/v1/contacts` - Create contact
- `GET /api/v1/contacts/{id}` - Get single contact
- `PATCH /api/v1/contacts/{id}` - Update contact
- `DELETE /api/v1/contacts/{id}` - Delete contact
- Same pattern for `/contact-addresses`, `/contact-people`
- `POST /api/v1/contact-documents/upload` - Upload document (multipart/form-data)
- `GET /api/v1/contact-documents/{id}/download` - Download document

**Contact Types:**
- `individual` - Person
- `company` - Business entity
- `government` - Government agency

**Address Types:**
- `billing` - Billing address
- `shipping` - Shipping/delivery address
- `fiscal` - Tax/legal address
- `other` - Other type

**Document Types:**
- `id_card` - Identification document
- `tax_certificate` - Tax registration certificate (RFC in Mexico)
- `contract` - Business contract
- `license` - Business license
- `other` - Other document

**Related Modules:**
- [Sales Module](SALES_FRONTEND_GUIDE.md) - Customer contacts
- [Purchase Module](PURCHASE_FRONTEND_GUIDE.md) - Supplier contacts
- [Finance Module](FINANCE_FRONTEND_GUIDE.md) - Invoice contacts
- [Billing Module](BILLING_FRONTEND_GUIDE.md) - CFDI customer information
