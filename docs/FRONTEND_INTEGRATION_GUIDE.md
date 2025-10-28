# Frontend Integration Guide

Complete guide for integrating with the Laravel Modular ERP JSON:API endpoints.

## Table of Contents
- [Authentication](#authentication)
- [Common Patterns](#common-patterns)
- [Module Integration](#module-integration)
- [File Handling](#file-handling)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)

---

## Authentication

All API requests require Bearer token authentication via Laravel Sanctum.

### Getting a Token

```javascript
const response = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password'
  })
});

const { token } = await response.json();
// Store token securely (localStorage, sessionStorage, or state management)
```

### Using the Token

All subsequent requests must include the token:

```javascript
const headers = {
  'Authorization': `Bearer ${token}`,
  'Accept': 'application/vnd.api+json',
  'Content-Type': 'application/vnd.api+json'
};
```

---

## Common Patterns

### JSON:API Request Format

All POST/PATCH requests follow JSON:API 1.1 specification:

```javascript
const data = {
  data: {
    type: "resource-type",  // plural, kebab-case
    attributes: {
      fieldName: "value",   // camelCase
      // ...
    }
  }
};
```

### Fetching Resources with Relationships

```javascript
// Include related resources
const response = await fetch(
  '/api/v1/contacts/27?include=contactPeople,contactAddresses,contactDocuments',
  { headers }
);

const { data, included } = await response.json();
// data = main resource
// included = array of related resources
```

### Filtering

```javascript
// Filter by field
const customers = await fetch(
  '/api/v1/contacts?filter[isCustomer]=true',
  { headers }
);

// Filter by relationship
const addresses = await fetch(
  '/api/v1/contact-addresses?filter[contactId]=27',
  { headers }
);

// Search by name (partial match)
const search = await fetch(
  '/api/v1/contacts?filter[name]=Empresa',
  { headers }
);
```

### Sorting

```javascript
// Sort ascending
const sorted = await fetch('/api/v1/products?sort=name', { headers });

// Sort descending
const sorted = await fetch('/api/v1/products?sort=-price', { headers });

// Multiple sorts
const sorted = await fetch('/api/v1/products?sort=category,-price', { headers });
```

### Pagination

```javascript
// Default pagination (25 per page)
const page1 = await fetch('/api/v1/products', { headers });

// Custom page size
const page1 = await fetch('/api/v1/products?page[size]=50', { headers });

// Specific page
const page2 = await fetch('/api/v1/products?page[number]=2', { headers });

// Response includes pagination metadata
const response = await page1.json();
// response.meta.page.currentPage
// response.meta.page.total
// response.links.first, .prev, .next, .last
```

---

## Module Integration

### Contacts Module

Complete contact management with addresses, people, and documents.

#### Create Contact

```javascript
async function createContact(contactData) {
  const payload = {
    data: {
      type: "contacts",
      attributes: {
        contactType: "company",        // "company" or "person"
        name: "Company ABC",
        legalName: "Company ABC S.A.",
        taxId: "TAX123456",
        email: "info@company.com",
        phone: "+1-555-1234",
        website: "https://company.com",
        status: "active",              // "active" or "inactive"
        isCustomer: true,
        isSupplier: false,
        creditLimit: 50000.00,
        classification: "A",           // A, B, C
        paymentTerms: 30,              // days
        notes: "Important client"
      }
    }
  };

  const response = await fetch('/api/v1/contacts', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}
```

#### Add Contact Address

```javascript
async function addAddress(contactId, addressData) {
  const payload = {
    data: {
      type: "contact-addresses",
      attributes: {
        contactId: parseInt(contactId),
        addressType: "billing",       // "billing", "shipping", "office"
        addressLine1: "123 Main St",
        addressLine2: "Suite 500",
        city: "New York",
        state: "NY",
        country: "USA",
        postalCode: "10001",
        isDefault: true
      }
    }
  };

  const response = await fetch('/api/v1/contact-addresses', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}
```

#### Add Contact Person

```javascript
async function addContactPerson(contactId, personData) {
  const payload = {
    data: {
      type: "contact-people",
      attributes: {
        contactId: parseInt(contactId),
        name: "John Doe",
        position: "Purchasing Manager",
        department: "Procurement",
        email: "john.doe@company.com",
        phone: "+1-555-1235",
        mobile: "+1-555-1236",
        isPrimary: true
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

### Sales Module

See [BUSINESS_FLOWS.md](architecture/BUSINESS_FLOWS.md) for complete Order-to-Cash flow.

#### Create Sales Order

```javascript
async function createSalesOrder(orderData) {
  const payload = {
    data: {
      type: "sales-orders",
      attributes: {
        contactId: 27,
        orderDate: "2025-01-15",
        status: "pending",            // pending, approved, completed
        totalAmount: 1500.00,
        notes: "Rush order"
      }
    }
  };

  const response = await fetch('/api/v1/sales-orders', {
    method: 'POST',
    headers,
    body: JSON.stringify(payload)
  });

  return await response.json();
}
```

### Purchase Module

See [BUSINESS_FLOWS.md](architecture/BUSINESS_FLOWS.md) for complete Procure-to-Pay flow.

### Finance Module

Accounts Receivable and Accounts Payable management.

#### Get AR Invoice with Balance

```javascript
// AR Invoices include calculated fields:
// - paidAmount: sum of all payment applications
// - remainingBalance: totalAmount - paidAmount

const response = await fetch('/api/v1/ar-invoices/123', { headers });
const invoice = await response.json();

console.log(invoice.data.attributes);
// {
//   invoiceNumber: "AR-2025-001",
//   totalAmount: 1500.00,
//   paidAmount: 500.00,        // Calculated
//   remainingBalance: 1000.00,  // Calculated
//   status: "partially_paid"
// }
```

### Accounting Module

General Ledger, Journal Entries, and Fiscal Periods.

See [ERD_DOCUMENTATION.md](architecture/ERD_DOCUMENTATION.md) for complete schema reference.

---

## File Handling

### Upload Document

Documents use `multipart/form-data` instead of JSON:API format.

```javascript
async function uploadDocument(contactId, file, documentType, notes = '') {
  const formData = new FormData();
  formData.append('contact_id', contactId);
  formData.append('document_type', documentType);  // See types below
  formData.append('file', file);
  formData.append('notes', notes);
  formData.append('expires_at', '2025-12-31');     // Optional

  const response = await fetch('/api/v1/contact-documents/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
      // Do NOT set Content-Type - FormData handles it automatically
    },
    body: formData
  });

  return await response.json();
}
```

**Supported Document Types:**
- `rfc` - Tax ID
- `cedula_fiscal` - Tax Certificate
- `ine` - Government ID
- `constancia_sat` - Tax Status Certificate
- `opinion_sat` - Tax Opinion
- `certificado_sello` - Digital Stamp Certificate
- `comprobante_domicilio` - Proof of Address
- `cotizacion` - Quote
- `orden_compra` - Purchase Order
- `factura` - Invoice
- `contrato` - Contract
- `otros` - Other

**Supported File Types:**
- PDF: `.pdf`
- Images: `.jpg`, `.jpeg`, `.png`, `.gif`
- Word: `.doc`, `.docx`
- Excel: `.xls`, `.xlsx`
- **Max size:** 10MB

### View/Preview Document

```javascript
async function showDocument(documentId, elementId) {
  const response = await fetch(
    `/api/v1/contact-documents/${documentId}/view`,
    { headers }
  );

  if (response.ok) {
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);

    // For images
    document.getElementById(elementId).src = url;

    // For PDFs in iframe
    document.getElementById('pdf-viewer').src = url;

    // Remember to revoke URL when done
    // setTimeout(() => URL.revokeObjectURL(url), 100);
  }
}
```

### Download Document

```javascript
async function downloadDocument(documentId, filename) {
  const response = await fetch(
    `/api/v1/contact-documents/${documentId}/download`,
    { headers }
  );

  if (response.ok) {
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  }
}
```

### Verify/Unverify Document

```javascript
// Mark document as verified
async function verifyDocument(documentId) {
  const response = await fetch(
    `/api/v1/contact-documents/${documentId}/verify`,
    {
      method: 'PATCH',
      headers
    }
  );

  return await response.json();
}

// Remove verification
async function unverifyDocument(documentId) {
  const response = await fetch(
    `/api/v1/contact-documents/${documentId}/unverify`,
    {
      method: 'PATCH',
      headers
    }
  );

  return await response.json();
}
```

---

## Best Practices

### 1. Use TypeScript Interfaces

```typescript
interface JsonApiResource<T> {
  data: {
    type: string;
    id?: string;
    attributes: T;
  };
}

interface Contact {
  contactType: 'company' | 'person';
  name: string;
  email: string;
  // ...
}
```

### 2. Create Reusable API Client

```javascript
class ApiClient {
  constructor(baseUrl, token) {
    this.baseUrl = baseUrl;
    this.token = token;
  }

  async get(endpoint, params = {}) {
    const url = new URL(endpoint, this.baseUrl);
    Object.keys(params).forEach(key =>
      url.searchParams.append(key, params[key])
    );

    const response = await fetch(url, {
      headers: this.getHeaders()
    });

    return this.handleResponse(response);
  }

  async post(endpoint, data) {
    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      method: 'POST',
      headers: this.getHeaders(),
      body: JSON.stringify(data)
    });

    return this.handleResponse(response);
  }

  getHeaders() {
    return {
      'Authorization': `Bearer ${this.token}`,
      'Accept': 'application/vnd.api+json',
      'Content-Type': 'application/vnd.api+json'
    };
  }

  async handleResponse(response) {
    if (!response.ok) {
      const error = await response.json();
      throw new ApiError(error, response.status);
    }
    return await response.json();
  }
}
```

### 3. Handle Included Resources

```javascript
function normalizeJsonApiResponse(response) {
  const { data, included = [] } = response;

  // Create lookup map for included resources
  const includedMap = {};
  included.forEach(item => {
    if (!includedMap[item.type]) {
      includedMap[item.type] = {};
    }
    includedMap[item.type][item.id] = item.attributes;
  });

  // Merge relationships into main data
  if (data.relationships) {
    Object.keys(data.relationships).forEach(key => {
      const relationship = data.relationships[key].data;
      if (Array.isArray(relationship)) {
        data.attributes[key] = relationship.map(r =>
          includedMap[r.type]?.[r.id]
        );
      } else if (relationship) {
        data.attributes[key] = includedMap[relationship.type]?.[relationship.id];
      }
    });
  }

  return data.attributes;
}
```

### 4. Always Use parseInt() for IDs

```javascript
// JSON:API returns IDs as strings, but backend expects integers
const payload = {
  data: {
    type: "contact-addresses",
    attributes: {
      contactId: parseInt(contactId),  // ✓ Correct
      // contactId: contactId,         // ✗ May fail validation
    }
  }
};
```

### 5. Handle Validation Errors

```javascript
async function handleSubmit(formData) {
  try {
    const response = await api.post('/api/v1/contacts', formData);
    // Success
  } catch (error) {
    if (error.status === 422) {
      // Validation errors
      const errors = error.errors;
      Object.keys(errors).forEach(field => {
        showFieldError(field, errors[field][0]);
      });
    } else {
      // Other errors
      showGeneralError(error.message);
    }
  }
}
```

---

## Error Handling

### Common HTTP Status Codes

| Code | Meaning | Action |
|------|---------|--------|
| 401 | Unauthenticated | Token expired/invalid - redirect to login |
| 403 | Forbidden | User lacks permission - show error |
| 404 | Not Found | Resource doesn't exist - show not found |
| 422 | Validation Error | Show field-specific errors |
| 500 | Server Error | Show generic error, log for support |

### Validation Errors (422)

```javascript
// Response format
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email must be a valid email address."]
  }
}
```

### Authentication Errors (401)

```javascript
{
  "message": "Unauthenticated."
}
```

### Permission Errors (403)

```javascript
{
  "message": "This action is unauthorized."
}
```

---

## Complete Example: Create Contact with Everything

```javascript
async function createCompleteContact() {
  try {
    // 1. Create contact
    const contact = await createContact({
      contactType: "company",
      name: "My Company",
      email: "info@mycompany.com",
      phone: "+1-555-1234",
      isCustomer: true,
      status: "active"
    });

    const contactId = contact.data.id;
    console.log('✓ Contact created:', contactId);

    // 2. Add address
    await addAddress(contactId, {
      addressType: "billing",
      addressLine1: "123 Main St",
      city: "New York",
      state: "NY",
      country: "USA",
      postalCode: "10001",
      isDefault: true
    });
    console.log('✓ Address added');

    // 3. Add contact person
    await addContactPerson(contactId, {
      name: "Jane Smith",
      position: "General Manager",
      email: "jane@mycompany.com",
      phone: "+1-555-1235",
      isPrimary: true
    });
    console.log('✓ Contact person added');

    // 4. Upload document (if file available)
    const fileInput = document.getElementById('file-input');
    if (fileInput.files.length > 0) {
      await uploadDocument(
        contactId,
        fileInput.files[0],
        'rfc',
        'Company tax ID'
      );
      console.log('✓ Document uploaded');
    }

    // 5. Fetch complete contact
    const fullContact = await fetch(
      `/api/v1/contacts/${contactId}?include=contactPeople,contactDocuments,contactAddresses`,
      { headers }
    );

    const complete = await fullContact.json();
    console.log('🎉 Complete contact:', complete);

  } catch (error) {
    console.error('Error:', error);
  }
}
```

---

## Reference Documentation

For more detailed information, see:

- **System Architecture**: [docs/architecture/README.md](architecture/README.md)
- **Database Schema**: [docs/architecture/ERD_DOCUMENTATION.md](architecture/ERD_DOCUMENTATION.md)
- **Business Flows**: [docs/architecture/BUSINESS_FLOWS.md](architecture/BUSINESS_FLOWS.md)
- **Business Rules**: [docs/architecture/BUSINESS_RULES_COMPLETE.md](architecture/BUSINESS_RULES_COMPLETE.md)
- **API Module Specs**: [docs/api-documentation/backend-specs/modules/](api-documentation/backend-specs/modules/)

---

## Quick Reference URLs

### Authentication
- Login: `POST /api/auth/login`

### Contacts
- Contacts: `/api/v1/contacts`
- Addresses: `/api/v1/contact-addresses`
- People: `/api/v1/contact-people`
- Documents (List/CRUD): `/api/v1/contact-documents`
- Upload Document: `POST /api/v1/contact-documents/upload`
- Download: `GET /api/v1/contact-documents/{id}/download`
- View/Preview: `GET /api/v1/contact-documents/{id}/view`
- Verify: `PATCH /api/v1/contact-documents/{id}/verify`
- Unverify: `PATCH /api/v1/contact-documents/{id}/unverify`

### Products & Inventory
- Products: `/api/v1/products`
- Categories: `/api/v1/categories`
- Brands: `/api/v1/brands`
- Units: `/api/v1/units`
- Warehouses: `/api/v1/warehouses`
- Stock: `/api/v1/stock`
- Inventory Movements: `/api/v1/inventory-movements`

### Sales & Purchase
- Sales Orders: `/api/v1/sales-orders`
- Sales Order Items: `/api/v1/sales-order-items`
- Purchase Orders: `/api/v1/purchase-orders`
- Purchase Order Items: `/api/v1/purchase-order-items`

### Finance & Accounting
- AR Invoices: `/api/v1/ar-invoices`
- AP Invoices: `/api/v1/ap-invoices`
- Payments: `/api/v1/payments`
- Bank Accounts: `/api/v1/bank-accounts`
- GL Accounts: `/api/v1/accounts`
- Journal Entries: `/api/v1/journal-entries`
- Fiscal Periods: `/api/v1/fiscal-periods`

---

**Last Updated**: 2025-10-28
**API Version**: v1
**JSON:API Spec**: 1.1
