# 📋 API Documentation - Accounting

Auto-generated API documentation.

**Generated:** 2025-08-20 11:02:18

## 📄 Account

**Resource Type:** `accounts`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/accounts` | List all Accounts |
| POST | `/api/v1/accounts` | Create new Account |
| GET | `/api/v1/accounts/{id}` | Show specific Account |
| PATCH | `/api/v1/accounts/{id}` | Update Account |
| DELETE | `/api/v1/accounts/{id}` | Delete Account |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `code` | string | Auto-detected field |
| `name` | string | Auto-detected field |
| `accountType` | string | Auto-detected field |
| `level` | number | Auto-detected field |
| `parentId` | number | Auto-detected field |
| `currency` | string | Auto-detected field |
| `isPostable` | boolean | Auto-detected field |
| `status` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/accounts?filter[field]=value
```

#### Sorting
```
GET /api/v1/accounts?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/accounts?page[number]=1&page[size]=20
```

## 📄 ExchangeRate

**Resource Type:** `exchange-rates`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/exchange-rates` | List all ExchangeRates |
| POST | `/api/v1/exchange-rates` | Create new ExchangeRate |
| GET | `/api/v1/exchange-rates/{id}` | Show specific ExchangeRate |
| PATCH | `/api/v1/exchange-rates/{id}` | Update ExchangeRate |
| DELETE | `/api/v1/exchange-rates/{id}` | Delete ExchangeRate |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `baseCurrency` | string | Auto-detected field |
| `quoteCurrency` | string | Auto-detected field |
| `rateDate` | datetime | Auto-detected field |
| `rate` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/exchange-rates?filter[field]=value
```

#### Sorting
```
GET /api/v1/exchange-rates?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/exchange-rates?page[number]=1&page[size]=20
```

## 📄 FiscalPeriod

**Resource Type:** `fiscal-periods`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/fiscal-periods` | List all FiscalPeriods |
| POST | `/api/v1/fiscal-periods` | Create new FiscalPeriod |
| GET | `/api/v1/fiscal-periods/{id}` | Show specific FiscalPeriod |
| PATCH | `/api/v1/fiscal-periods/{id}` | Update FiscalPeriod |
| DELETE | `/api/v1/fiscal-periods/{id}` | Delete FiscalPeriod |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `name` | string | Auto-detected field |
| `startDate` | datetime | Auto-detected field |
| `endDate` | datetime | Auto-detected field |
| `status` | string | Auto-detected field |
| `allowBackpost` | boolean | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/fiscal-periods?filter[field]=value
```

#### Sorting
```
GET /api/v1/fiscal-periods?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/fiscal-periods?page[number]=1&page[size]=20
```

## 📄 JournalEntry

**Resource Type:** `journal-entries`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/journal-entries` | List all JournalEntries |
| POST | `/api/v1/journal-entries` | Create new JournalEntry |
| GET | `/api/v1/journal-entries/{id}` | Show specific JournalEntry |
| PATCH | `/api/v1/journal-entries/{id}` | Update JournalEntry |
| DELETE | `/api/v1/journal-entries/{id}` | Delete JournalEntry |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `journalId` | number | Auto-detected field |
| `periodId` | number | Auto-detected field |
| `number` | string | Auto-detected field |
| `date` | datetime | Auto-detected field |
| `currency` | string | Auto-detected field |
| `exchangeRate` | number | Auto-detected field |
| `reference` | string | Auto-detected field |
| `description` | string | Auto-detected field |
| `status` | string | Auto-detected field |
| `approvedById` | number | Auto-detected field |
| `postedById` | number | Auto-detected field |
| `postedAt` | datetime | Auto-detected field |
| `reversalOfId` | number | Auto-detected field |
| `sourceType` | string | Auto-detected field |
| `sourceId` | number | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/journal-entries?filter[field]=value
```

#### Sorting
```
GET /api/v1/journal-entries?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/journal-entries?page[number]=1&page[size]=20
```

## 📄 JournalLine

**Resource Type:** `journal-lines`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/journal-lines` | List all JournalLines |
| POST | `/api/v1/journal-lines` | Create new JournalLine |
| GET | `/api/v1/journal-lines/{id}` | Show specific JournalLine |
| PATCH | `/api/v1/journal-lines/{id}` | Update JournalLine |
| DELETE | `/api/v1/journal-lines/{id}` | Delete JournalLine |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `journalEntryId` | number | Auto-detected field |
| `accountId` | number | Auto-detected field |
| `debit` | number | Auto-detected field |
| `credit` | number | Auto-detected field |
| `baseAmount` | number | Auto-detected field |
| `costCenterId` | number | Auto-detected field |
| `partnerId` | number | Auto-detected field |
| `memo` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/journal-lines?filter[field]=value
```

#### Sorting
```
GET /api/v1/journal-lines?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/journal-lines?page[number]=1&page[size]=20
```

## 📄 Journal

**Resource Type:** `journals`

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/journals` | List all Journals |
| POST | `/api/v1/journals` | Create new Journal |
| GET | `/api/v1/journals/{id}` | Show specific Journal |
| PATCH | `/api/v1/journals/{id}` | Update Journal |
| DELETE | `/api/v1/journals/{id}` | Delete Journal |

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | id | Auto-detected field |
| `code` | string | Auto-detected field |
| `name` | string | Auto-detected field |
| `autoNumbering` | boolean | Auto-detected field |
| `sequencePrefix` | string | Auto-detected field |
| `sequenceNext` | number | Auto-detected field |
| `defaultCurrency` | string | Auto-detected field |
| `postPolicy` | string | Auto-detected field |
| `metadata` | object | Auto-detected field |
| `createdAt` | datetime | Auto-detected field |
| `updatedAt` | datetime | Auto-detected field |

### Query Parameters

#### Filtering
```
GET /api/v1/journals?filter[field]=value
```

#### Sorting
```
GET /api/v1/journals?sort=field,-other_field
```

#### Pagination
```
GET /api/v1/journals?page[number]=1&page[size]=20
```


## 🔐 Authentication

All endpoints require authentication using Sanctum tokens.

```bash
Authorization: Bearer {your-token}
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json
```

