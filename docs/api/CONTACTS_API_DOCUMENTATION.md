# Contacts Module API Documentation

**Generated:** 19 August 2025  
**Version:** 1.0  
**Module:** Contacts  
**Entities:** 4 (Contact, ContactAddress, ContactPerson, ContactDocument)

---

## 📋 **MODULE OVERVIEW**

The Contacts module provides comprehensive contact management functionality with support for customers, suppliers, and mixed entities. It includes complete address management, contact person tracking, and document attachment capabilities.

### **Key Features**
- **Unified Contact System**: Single entity for customers, suppliers, or both
- **Address Management**: Multiple addresses per contact with default designation
- **Contact People**: Multiple contact persons per organization  
- **Document Management**: File attachments with verification and expiration tracking
- **Business Logic**: Credit limits, RFC validation, status management
- **JSON:API 1.1**: Full compliance with filtering, sorting, pagination, and relationships

---

## 🏢 **ENTITY 1: CONTACTS**

### **Endpoints**
```
GET    /api/v1/contacts           # List contacts with filtering/pagination
POST   /api/v1/contacts           # Create new contact  
GET    /api/v1/contacts/{id}      # Get single contact
PATCH  /api/v1/contacts/{id}      # Update contact
DELETE /api/v1/contacts/{id}      # Delete contact
```

### **Fields**
| Field | Type | Description | Validations |
|-------|------|-------------|-------------|
| `id` | ID | Unique identifier | Auto-generated |
| `contactType` | String | Contact type (`company`, `person`) | Required |
| `name` | String | Primary contact name | Required, max 255 |
| `legalName` | String | Legal business name | Optional |
| `taxId` | String | Tax identification (RFC) | Optional, RFC format |
| `email` | String | Email address | Optional, email format |
| `phone` | String | Primary phone number | Optional |
| `website` | String | Website URL | Optional |
| `status` | String | Contact status (`active`, `inactive`, `suspended`) | Default: `active` |
| `isCustomer` | Boolean | Customer flag | Default: `false` |
| `isSupplier` | Boolean | Supplier flag | Default: `false` |
| `creditLimit` | Number | Credit limit amount | Default: `0.00` |
| `currentCredit` | Number | Current credit balance | Default: `0.00` |
| `classification` | String | Contact classification | Optional |
| `paymentTerms` | Number | Payment terms in days | Default: `30` |
| `notes` | String | Additional notes | Optional |
| `metadata` | Object | Custom metadata | Optional |
| `createdAt` | DateTime | Creation timestamp | Auto-generated |
| `updatedAt` | DateTime | Last update timestamp | Auto-generated |

### **Relationships**
| Relationship | Type | Description |
|--------------|------|-------------|
| `contactAddresses` | HasMany | Associated addresses |
| `contactPeople` | HasMany | Associated contact persons |
| `contactDocuments` | HasMany | Associated documents |

### **Business Rules**
- Must be either customer (`isCustomer: true`) or supplier (`isSupplier: true`) or both
- Credit limit only applies to customers
- Current credit cannot exceed credit limit  
- Mexican RFC validation when provided
- Legal name defaults to name for companies

### **Filters**
```
?filter[contactType]=company
?filter[name]=Acme Corp
?filter[email]=contact@acme.com
?filter[status]=active
?filter[isCustomer]=true
?filter[isSupplier]=false
?filter[classification]=premium
```

### **Sorting**
```
?sort=name                    # Name A-Z
?sort=-createdAt             # Newest first
?sort=creditLimit,-name      # Credit limit ASC, then name DESC
```

### **Including Relationships**
```
?include=contactAddresses,contactPeople,contactDocuments
```

### **Example Request: Create Contact**
```http
POST /api/v1/contacts
Content-Type: application/vnd.api+json
Authorization: Bearer {token}

{
  "data": {
    "type": "contacts",
    "attributes": {
      "contactType": "company",
      "name": "Acme Corporation",
      "legalName": "Acme Corporation S.A. de C.V.",
      "taxId": "ACM850101ABC",
      "email": "contact@acme.com",
      "phone": "+52-55-1234-5678",
      "website": "https://acme.com",
      "isCustomer": true,
      "isSupplier": false,
      "creditLimit": 50000.00,
      "paymentTerms": 30,
      "classification": "premium"
    }
  }
}
```

### **Example Response: Contact Created**
```json
{
  "data": {
    "type": "contacts",
    "id": "1",
    "attributes": {
      "contactType": "company",
      "name": "Acme Corporation",
      "legalName": "Acme Corporation S.A. de C.V.",
      "taxId": "ACM850101ABC",
      "email": "contact@acme.com",
      "phone": "+52-55-1234-5678",
      "website": "https://acme.com",
      "status": "active",
      "isCustomer": true,
      "isSupplier": false,
      "creditLimit": 50000.00,
      "currentCredit": 0.00,
      "classification": "premium",
      "paymentTerms": 30,
      "notes": null,
      "metadata": {},
      "createdAt": "2025-08-19T10:30:00Z",
      "updatedAt": "2025-08-19T10:30:00Z"
    },
    "relationships": {
      "contactAddresses": {
        "data": []
      },
      "contactPeople": {
        "data": []
      },
      "contactDocuments": {
        "data": []
      }
    }
  }
}
```

---

## 📍 **ENTITY 2: CONTACT ADDRESSES**

### **Endpoints**
```
GET    /api/v1/contact-addresses           # List addresses
POST   /api/v1/contact-addresses           # Create address
GET    /api/v1/contact-addresses/{id}      # Get single address
PATCH  /api/v1/contact-addresses/{id}      # Update address
DELETE /api/v1/contact-addresses/{id}      # Delete address
```

### **Fields**
| Field | Type | Description | Validations |
|-------|------|-------------|-------------|
| `id` | ID | Unique identifier | Auto-generated |
| `contactId` | Number | Parent contact ID | Required, foreign key |
| `addressType` | String | Address type (`billing`, `shipping`, `office`) | Required |
| `addressLine1` | String | Primary address line | Required |
| `addressLine2` | String | Secondary address line | Optional |
| `city` | String | City name | Required |
| `state` | String | State/province | Required |
| `country` | String | Country code/name | Required |
| `postalCode` | String | Postal/ZIP code | Required |
| `isDefault` | Boolean | Default address flag | Default: `false` |
| `metadata` | Object | Custom metadata | Optional |
| `createdAt` | DateTime | Creation timestamp | Auto-generated |
| `updatedAt` | DateTime | Last update timestamp | Auto-generated |

### **Relationships**
| Relationship | Type | Description |
|--------------|------|-------------|
| `contact` | BelongsTo | Parent contact |

### **Business Rules**
- Only one default address per contact per type
- All address fields are required except `addressLine2`
- Setting `isDefault: true` will unset other default addresses of same type

### **Example Request: Create Address**
```http
POST /api/v1/contact-addresses
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "contact-addresses",
    "attributes": {
      "contactId": 1,
      "addressType": "billing",
      "addressLine1": "123 Business Ave",
      "addressLine2": "Suite 400", 
      "city": "Mexico City",
      "state": "CDMX",
      "country": "Mexico",
      "postalCode": "01000",
      "isDefault": true
    }
  }
}
```

---

## 👥 **ENTITY 3: CONTACT PEOPLE**

### **Endpoints**
```
GET    /api/v1/contact-people           # List contact people
POST   /api/v1/contact-people           # Create contact person
GET    /api/v1/contact-people/{id}      # Get single person
PATCH  /api/v1/contact-people/{id}      # Update person
DELETE /api/v1/contact-people/{id}      # Delete person
```

### **Fields**
| Field | Type | Description | Validations |
|-------|------|-------------|-------------|
| `id` | ID | Unique identifier | Auto-generated |
| `contactId` | Number | Parent contact ID | Required, foreign key |
| `name` | String | Person's full name | Required |
| `position` | String | Job position/title | Optional |
| `department` | String | Department name | Optional |
| `email` | String | Email address | Optional, email format |
| `phone` | String | Office phone | Optional |
| `mobile` | String | Mobile phone | Optional |
| `isPrimary` | Boolean | Primary contact flag | Default: `false` |
| `metadata` | Object | Custom metadata | Optional |
| `createdAt` | DateTime | Creation timestamp | Auto-generated |
| `updatedAt` | DateTime | Last update timestamp | Auto-generated |

### **Relationships**
| Relationship | Type | Description |
|--------------|------|-------------|
| `contact` | BelongsTo | Parent contact |

### **Business Rules**
- Only one primary contact person per contact
- Either email or phone should be provided
- Setting `isPrimary: true` will unset other primary contacts

### **Example Request: Create Contact Person**
```http
POST /api/v1/contact-people
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "contact-people",
    "attributes": {
      "contactId": 1,
      "name": "Juan Rodriguez",
      "position": "CFO",
      "department": "Finance",
      "email": "juan.rodriguez@acme.com",
      "phone": "+52-55-1234-5679",
      "mobile": "+52-55-9876-5432",
      "isPrimary": true
    }
  }
}
```

---

## 📄 **ENTITY 4: CONTACT DOCUMENTS**

### **Endpoints**
```
GET    /api/v1/contact-documents           # List documents
POST   /api/v1/contact-documents           # Upload document
GET    /api/v1/contact-documents/{id}      # Get single document
PATCH  /api/v1/contact-documents/{id}      # Update document metadata
DELETE /api/v1/contact-documents/{id}      # Delete document
```

### **Fields**
| Field | Type | Description | Validations |
|-------|------|-------------|-------------|
| `id` | ID | Unique identifier | Auto-generated |
| `contactId` | Number | Parent contact ID | Required, foreign key |
| `documentType` | String | Document type (`contract`, `identity`, `tax`, `other`) | Required |
| `filePath` | String | File storage path | Required |
| `originalFilename` | String | Original file name | Required |
| `mimeType` | String | MIME type | Required |
| `fileSize` | Number | File size in bytes | Required |
| `uploadedBy` | Number | User who uploaded | Required |
| `verifiedAt` | DateTime | Verification timestamp | Optional |
| `verifiedBy` | Number | User who verified | Optional |
| `expiresAt` | DateTime | Expiration date | Optional |
| `notes` | String | Document notes | Optional |
| `metadata` | Object | Custom metadata | Optional |
| `createdAt` | DateTime | Creation timestamp | Auto-generated |
| `updatedAt` | DateTime | Last update timestamp | Auto-generated |

### **Relationships**
| Relationship | Type | Description |
|--------------|------|-------------|
| `contact` | BelongsTo | Parent contact |

### **Business Rules**
- File size limit: 10MB per document
- Allowed file types: PDF, DOC, DOCX, JPG, PNG
- Documents can have expiration dates for compliance tracking
- Verification workflow optional but recommended for sensitive documents

### **Example Request: Upload Document**
```http
POST /api/v1/contact-documents
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "contact-documents",
    "attributes": {
      "contactId": 1,
      "documentType": "contract",
      "filePath": "/storage/contacts/1/contract_2025.pdf",
      "originalFilename": "service_agreement_2025.pdf",
      "mimeType": "application/pdf",
      "fileSize": 2048576,
      "uploadedBy": 1,
      "expiresAt": "2026-12-31T23:59:59Z",
      "notes": "Annual service agreement renewal"
    }
  }
}
```

---

## 🔗 **RELATIONSHIP QUERIES**

### **Include Related Data**
```http
GET /api/v1/contacts/1?include=contactAddresses,contactPeople,contactDocuments
```

### **Filter by Related Data**
```http
GET /api/v1/contact-addresses?filter[contact_id]=1
GET /api/v1/contact-people?filter[contact_id]=1&filter[is_primary]=true
GET /api/v1/contact-documents?filter[contact_id]=1&filter[document_type]=contract
```

### **Nested Relationship Queries**
```http
GET /api/v1/contacts?include=contactAddresses&filter[contactAddresses.address_type]=billing
```

---

## 📊 **PAGINATION & SORTING**

### **Pagination**
```http
GET /api/v1/contacts?page[number]=2&page[size]=25
```

**Response includes pagination meta:**
```json
{
  "data": [...],
  "meta": {
    "page": {
      "currentPage": 2,
      "from": 26,
      "lastPage": 10,
      "perPage": 25,
      "to": 50,
      "total": 250
    }
  },
  "links": {
    "first": "/api/v1/contacts?page[number]=1",
    "last": "/api/v1/contacts?page[number]=10",
    "prev": "/api/v1/contacts?page[number]=1",
    "next": "/api/v1/contacts?page[number]=3"
  }
}
```

### **Advanced Sorting**
```http
# Sort contacts by multiple criteria
GET /api/v1/contacts?sort=-creditLimit,name,createdAt

# Sort addresses by default flag and city
GET /api/v1/contact-addresses?sort=-isDefault,city
```

---

## 🔒 **AUTHENTICATION & PERMISSIONS**

### **Authentication**
All endpoints require Bearer token authentication:
```http
Authorization: Bearer {your-access-token}
```

### **Permissions**
| Action | Permission | Description |
|--------|------------|-------------|
| View | `contacts.index` | List contacts |
| View Single | `contacts.show` | View single contact |
| Create | `contacts.store` | Create new contact |
| Update | `contacts.update` | Update existing contact |
| Delete | `contacts.destroy` | Delete contact |

**Same pattern applies to all entities:**
- `contact-addresses.{action}`
- `contact-people.{action}`  
- `contact-documents.{action}`

---

## ⚠️ **ERROR HANDLING**

### **Validation Errors (422)**
```json
{
  "errors": [
    {
      "title": "Validation Error",
      "detail": "Contact must be either a customer, supplier, or both.",
      "source": { "pointer": "/data/attributes/isCustomer" },
      "status": "422"
    }
  ]
}
```

### **Business Rule Violations (422)**
```json
{
  "errors": [
    {
      "title": "Business Rule Violation", 
      "detail": "Current credit cannot exceed credit limit.",
      "source": { "pointer": "/data/attributes/currentCredit" },
      "status": "422"
    }
  ]
}
```

### **Not Found (404)**
```json
{
  "errors": [
    {
      "title": "Not Found",
      "detail": "Contact not found.",
      "status": "404"
    }
  ]
}
```

---

## 🧪 **TESTING EXAMPLES**

### **Complete Contact Creation Flow**
```bash
# 1. Create contact
curl -X POST /api/v1/contacts \
  -H "Content-Type: application/vnd.api+json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "data": {
      "type": "contacts",
      "attributes": {
        "contactType": "company",
        "name": "Test Company",
        "isCustomer": true,
        "creditLimit": 10000
      }
    }
  }'

# 2. Add address
curl -X POST /api/v1/contact-addresses \
  -H "Content-Type: application/vnd.api+json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "data": {
      "type": "contact-addresses", 
      "attributes": {
        "contactId": 1,
        "addressType": "billing",
        "addressLine1": "123 Test St",
        "city": "Test City",
        "state": "Test State",
        "country": "Mexico",
        "postalCode": "12345",
        "isDefault": true
      }
    }
  }'

# 3. Add contact person
curl -X POST /api/v1/contact-people \
  -H "Content-Type: application/vnd.api+json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "data": {
      "type": "contact-people",
      "attributes": {
        "contactId": 1,
        "name": "John Doe",
        "position": "Manager",
        "email": "john@test.com",
        "isPrimary": true
      }
    }
  }'

# 4. Get complete contact with relationships
curl -X GET "/api/v1/contacts/1?include=contactAddresses,contactPeople,contactDocuments" \
  -H "Authorization: Bearer {token}"
```

---

## 📈 **PERFORMANCE CONSIDERATIONS**

### **Efficient Queries**
- Use sparse fieldsets to reduce payload size
- Leverage filtering instead of client-side filtering
- Use pagination for large datasets
- Include only needed relationships

### **Example: Optimized Query**
```http
GET /api/v1/contacts?fields[contacts]=name,email,status&filter[status]=active&page[size]=50
```

---

**📚 End of Contacts API Documentation**

*This documentation covers all 4 entities with complete field definitions, examples, and usage patterns. The module provides comprehensive contact management with full JSON:API compliance.*