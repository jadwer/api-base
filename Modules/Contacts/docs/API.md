# 📋 API Documentation - Contacts

Auto-generated API documentation.

**Generated:** 2025-08-19 17:59:33

## 📄 ContactAddress

**Resource Type:** `contact-addresses`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/contact-addresses` | List all ContactAddresses |
| POST | `/api/v1/contact-addresses` | Create new ContactAddress |
| GET | `/api/v1/contact-addresses/{id}` | Show specific ContactAddress |
| PATCH | `/api/v1/contact-addresses/{id}` | Update ContactAddress |
| DELETE | `/api/v1/contact-addresses/{id}` | Delete ContactAddress |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `addressType` | string | Auto-detected field |
| `addressLine1` | string | Auto-detected field |
| `addressLine2` | string | Auto-detected field |
| `city` | string | Auto-detected field |
| `state` | string | Auto-detected field |
| `country` | string | Auto-detected field |
| `postalCode` | string | Auto-detected field |
| `isDefault` | boolean | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `contact` | relationship | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/contact-addresses?filter[field]=value
```

#### Sorting
```
GET /api/v1/contact-addresses?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/contact-addresses?page[number]=1&page[size]=20
```

## 📄 ContactDocument

**Resource Type:** `contact-documents`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/contact-documents` | List all ContactDocuments |
| POST | `/api/v1/contact-documents` | Create new ContactDocument |
| GET | `/api/v1/contact-documents/{id}` | Show specific ContactDocument |
| PATCH | `/api/v1/contact-documents/{id}` | Update ContactDocument |
| DELETE | `/api/v1/contact-documents/{id}` | Delete ContactDocument |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `documentType` | string | Auto-detected field |
| `filePath` | string | Auto-detected field |
| `originalFilename` | string | Auto-detected field |
| `mimeType` | string | Auto-detected field |
| `fileSize` | number | Auto-detected field |
| `uploadedBy` | number | Auto-detected field |
| `verifiedAt` | datetime | Auto-detected field |
| `verifiedBy` | number | Auto-detected field |
| `expiresAt` | datetime | Auto-detected field |
| `notes` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `contact` | relationship | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/contact-documents?filter[field]=value
```

#### Sorting
```
GET /api/v1/contact-documents?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/contact-documents?page[number]=1&page[size]=20
```

## 📄 ContactPerson

**Resource Type:** `contact-people`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/contact-people` | List all ContactPeople |
| POST | `/api/v1/contact-people` | Create new ContactPerson |
| GET | `/api/v1/contact-people/{id}` | Show specific ContactPerson |
| PATCH | `/api/v1/contact-people/{id}` | Update ContactPerson |
| DELETE | `/api/v1/contact-people/{id}` | Delete ContactPerson |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `name` | string | Auto-detected field |
| `position` | string | Auto-detected field |
| `department` | string | Auto-detected field |
| `email` | string | Auto-detected field |
| `phone` | string | Auto-detected field |
| `mobile` | string | Auto-detected field |
| `isPrimary` | boolean | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `contact` | relationship | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/contact-people?filter[field]=value
```

#### Sorting
```
GET /api/v1/contact-people?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/contact-people?page[number]=1&page[size]=20
```

## 📄 Contact

**Resource Type:** `contacts`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/contacts` | List all Contacts |
| POST | `/api/v1/contacts` | Create new Contact |
| GET | `/api/v1/contacts/{id}` | Show specific Contact |
| PATCH | `/api/v1/contacts/{id}` | Update Contact |
| DELETE | `/api/v1/contacts/{id}` | Delete Contact |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactType` | string | Auto-detected field |
| `name` | string | Auto-detected field |
| `legalName` | string | Auto-detected field |
| `taxId` | string | Auto-detected field |
| `email` | string | Auto-detected field |
| `phone` | string | Auto-detected field |
| `website` | string | Auto-detected field |
| `status` | string | Auto-detected field |
| `isCustomer` | boolean | Auto-detected field |
| `isSupplier` | boolean | Auto-detected field |
| `creditLimit` | number | Auto-detected field |
| `currentCredit` | number | Auto-detected field |
| `classification` | string | Auto-detected field |
| `paymentTerms` | number | Auto-detected field |
| `notes` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |
| `contactDocuments` | relationship[] | Auto-detected field |
| `contactAddresses` | relationship[] | Auto-detected field |
| `contactPeople` | relationship[] | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/contacts?filter[field]=value
```

#### Sorting
```
GET /api/v1/contacts?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/contacts?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

