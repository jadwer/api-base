# 📋 API Documentation - Finance

Auto-generated API documentation.

**Generated:** 2025-08-20 11:02:24

## 📄 APInvoiceLine

**Resource Type:** `apinvoice-lines`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/apinvoice-lines` | List all APInvoiceLines |
| POST | `/api/v1/apinvoice-lines` | Create new APInvoiceLine |
| GET | `/api/v1/apinvoice-lines/{id}` | Show specific APInvoiceLine |
| PATCH | `/api/v1/apinvoice-lines/{id}` | Update APInvoiceLine |
| DELETE | `/api/v1/apinvoice-lines/{id}` | Delete APInvoiceLine |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `apInvoiceId` | number | Auto-detected field |
| `description` | string | Auto-detected field |
| `quantity` | number | Auto-detected field |
| `unitPrice` | number | Auto-detected field |
| `discount` | number | Auto-detected field |
| `lineTotal` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/apinvoice-lines?filter[field]=value
```

#### Sorting
```
GET /api/v1/apinvoice-lines?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/apinvoice-lines?page[number]=1&page[size]=20
```

## 📄 APInvoicePayment

**Resource Type:** `apinvoice-payments`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/apinvoice-payments` | List all APInvoicePayments |
| POST | `/api/v1/apinvoice-payments` | Create new APInvoicePayment |
| GET | `/api/v1/apinvoice-payments/{id}` | Show specific APInvoicePayment |
| PATCH | `/api/v1/apinvoice-payments/{id}` | Update APInvoicePayment |
| DELETE | `/api/v1/apinvoice-payments/{id}` | Delete APInvoicePayment |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `apInvoiceId` | number | Auto-detected field |
| `apPaymentId` | number | Auto-detected field |
| `amountApplied` | number | Auto-detected field |
| `appliedAt` | datetime | Auto-detected field |
| `exchangeRateAtApply` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/apinvoice-payments?filter[field]=value
```

#### Sorting
```
GET /api/v1/apinvoice-payments?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/apinvoice-payments?page[number]=1&page[size]=20
```

## 📄 APInvoice

**Resource Type:** `apinvoices`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/apinvoices` | List all APInvoices |
| POST | `/api/v1/apinvoices` | Create new APInvoice |
| GET | `/api/v1/apinvoices/{id}` | Show specific APInvoice |
| PATCH | `/api/v1/apinvoices/{id}` | Update APInvoice |
| DELETE | `/api/v1/apinvoices/{id}` | Delete APInvoice |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `invoiceNumber` | string | Auto-detected field |
| `invoiceDate` | datetime | Auto-detected field |
| `dueDate` | datetime | Auto-detected field |
| `currency` | string | Auto-detected field |
| `exchangeRate` | number | Auto-detected field |
| `subtotal` | number | Auto-detected field |
| `taxTotal` | number | Auto-detected field |
| `total` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/apinvoices?filter[field]=value
```

#### Sorting
```
GET /api/v1/apinvoices?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/apinvoices?page[number]=1&page[size]=20
```

## 📄 APPayment

**Resource Type:** `appayments`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/appayments` | List all APPayments |
| POST | `/api/v1/appayments` | Create new APPayment |
| GET | `/api/v1/appayments/{id}` | Show specific APPayment |
| PATCH | `/api/v1/appayments/{id}` | Update APPayment |
| DELETE | `/api/v1/appayments/{id}` | Delete APPayment |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `paymentDate` | datetime | Auto-detected field |
| `paymentMethod` | string | Auto-detected field |
| `currency` | string | Auto-detected field |
| `amount` | number | Auto-detected field |
| `bankAccountId` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/appayments?filter[field]=value
```

#### Sorting
```
GET /api/v1/appayments?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/appayments?page[number]=1&page[size]=20
```

## 📄 ARInvoiceLine

**Resource Type:** `arinvoice-lines`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/arinvoice-lines` | List all ARInvoiceLines |
| POST | `/api/v1/arinvoice-lines` | Create new ARInvoiceLine |
| GET | `/api/v1/arinvoice-lines/{id}` | Show specific ARInvoiceLine |
| PATCH | `/api/v1/arinvoice-lines/{id}` | Update ARInvoiceLine |
| DELETE | `/api/v1/arinvoice-lines/{id}` | Delete ARInvoiceLine |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `arInvoiceId` | number | Auto-detected field |
| `description` | string | Auto-detected field |
| `quantity` | number | Auto-detected field |
| `unitPrice` | number | Auto-detected field |
| `discount` | number | Auto-detected field |
| `lineTotal` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/arinvoice-lines?filter[field]=value
```

#### Sorting
```
GET /api/v1/arinvoice-lines?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/arinvoice-lines?page[number]=1&page[size]=20
```

## 📄 ARInvoiceReceipt

**Resource Type:** `arinvoice-receipts`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/arinvoice-receipts` | List all ARInvoiceReceipts |
| POST | `/api/v1/arinvoice-receipts` | Create new ARInvoiceReceipt |
| GET | `/api/v1/arinvoice-receipts/{id}` | Show specific ARInvoiceReceipt |
| PATCH | `/api/v1/arinvoice-receipts/{id}` | Update ARInvoiceReceipt |
| DELETE | `/api/v1/arinvoice-receipts/{id}` | Delete ARInvoiceReceipt |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `arInvoiceId` | number | Auto-detected field |
| `arReceiptId` | number | Auto-detected field |
| `amountApplied` | number | Auto-detected field |
| `appliedAt` | datetime | Auto-detected field |
| `exchangeRateAtApply` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/arinvoice-receipts?filter[field]=value
```

#### Sorting
```
GET /api/v1/arinvoice-receipts?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/arinvoice-receipts?page[number]=1&page[size]=20
```

## 📄 ARInvoice

**Resource Type:** `arinvoices`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/arinvoices` | List all ARInvoices |
| POST | `/api/v1/arinvoices` | Create new ARInvoice |
| GET | `/api/v1/arinvoices/{id}` | Show specific ARInvoice |
| PATCH | `/api/v1/arinvoices/{id}` | Update ARInvoice |
| DELETE | `/api/v1/arinvoices/{id}` | Delete ARInvoice |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `invoiceNumber` | string | Auto-detected field |
| `invoiceDate` | datetime | Auto-detected field |
| `dueDate` | datetime | Auto-detected field |
| `currency` | string | Auto-detected field |
| `exchangeRate` | number | Auto-detected field |
| `subtotal` | number | Auto-detected field |
| `taxTotal` | number | Auto-detected field |
| `total` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/arinvoices?filter[field]=value
```

#### Sorting
```
GET /api/v1/arinvoices?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/arinvoices?page[number]=1&page[size]=20
```

## 📄 ARReceipt

**Resource Type:** `arreceipts`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/arreceipts` | List all ARReceipts |
| POST | `/api/v1/arreceipts` | Create new ARReceipt |
| GET | `/api/v1/arreceipts/{id}` | Show specific ARReceipt |
| PATCH | `/api/v1/arreceipts/{id}` | Update ARReceipt |
| DELETE | `/api/v1/arreceipts/{id}` | Delete ARReceipt |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `contactId` | number | Auto-detected field |
| `receiptDate` | datetime | Auto-detected field |
| `paymentMethod` | string | Auto-detected field |
| `currency` | string | Auto-detected field |
| `amount` | number | Auto-detected field |
| `bankAccountId` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/arreceipts?filter[field]=value
```

#### Sorting
```
GET /api/v1/arreceipts?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/arreceipts?page[number]=1&page[size]=20
```

## 📄 BankAccount

**Resource Type:** `bank-accounts`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/bank-accounts` | List all BankAccounts |
| POST | `/api/v1/bank-accounts` | Create new BankAccount |
| GET | `/api/v1/bank-accounts/{id}` | Show specific BankAccount |
| PATCH | `/api/v1/bank-accounts/{id}` | Update BankAccount |
| DELETE | `/api/v1/bank-accounts/{id}` | Delete BankAccount |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `bankName` | string | Auto-detected field |
| `accountNumber` | string | Auto-detected field |
| `clabe` | string | Auto-detected field |
| `currency` | string | Auto-detected field |
| `accountType` | string | Auto-detected field |
| `openingBalance` | number | Auto-detected field |
| `status` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/bank-accounts?filter[field]=value
```

#### Sorting
```
GET /api/v1/bank-accounts?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/bank-accounts?page[number]=1&page[size]=20
```

## 📄 BankStatementLine

**Resource Type:** `bank-statement-lines`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/bank-statement-lines` | List all BankStatementLines |
| POST | `/api/v1/bank-statement-lines` | Create new BankStatementLine |
| GET | `/api/v1/bank-statement-lines/{id}` | Show specific BankStatementLine |
| PATCH | `/api/v1/bank-statement-lines/{id}` | Update BankStatementLine |
| DELETE | `/api/v1/bank-statement-lines/{id}` | Delete BankStatementLine |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `bankStatementId` | number | Auto-detected field |
| `txnDate` | datetime | Auto-detected field |
| `amount` | number | Auto-detected field |
| `counterparty` | string | Auto-detected field |
| `reference` | string | Auto-detected field |
| `fitid` | string | Auto-detected field |
| `status` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/bank-statement-lines?filter[field]=value
```

#### Sorting
```
GET /api/v1/bank-statement-lines?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/bank-statement-lines?page[number]=1&page[size]=20
```

## 📄 BankStatement

**Resource Type:** `bank-statements`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/bank-statements` | List all BankStatements |
| POST | `/api/v1/bank-statements` | Create new BankStatement |
| GET | `/api/v1/bank-statements/{id}` | Show specific BankStatement |
| PATCH | `/api/v1/bank-statements/{id}` | Update BankStatement |
| DELETE | `/api/v1/bank-statements/{id}` | Delete BankStatement |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `bankAccountId` | number | Auto-detected field |
| `statementDate` | datetime | Auto-detected field |
| `importSource` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/bank-statements?filter[field]=value
```

#### Sorting
```
GET /api/v1/bank-statements?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/bank-statements?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

