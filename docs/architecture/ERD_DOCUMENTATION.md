# ERD Documentation - Complete System Overview

**Date:** 2025-10-31
**Version:** 1.1
**Status:** Production-Ready Phase 4.4 Complete (HR Module)

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [ERD Diagram Files](#erd-diagram-files)
3. [Database Statistics](#database-statistics)
4. [Module Breakdown](#module-breakdown)
5. [Table Definitions](#table-definitions)
6. [Relationship Explanations](#relationship-explanations)
7. [Index Strategies](#index-strategies)
8. [Constraint Documentation](#constraint-documentation)
9. [Business Rule Enforcement](#business-rule-enforcement)
10. [Query Optimization Tips](#query-optimization-tips)

---

## Overview

This document provides comprehensive documentation for all Entity Relationship Diagrams (ERDs) in the Laravel Modular ERP system. The system uses a modular architecture with **9 core business modules** managing **54+ database tables** with complete relationship mapping.

### Key Architectural Patterns

1. **Party Pattern**: Unified `contacts` table with `is_customer` and `is_supplier` boolean flags eliminates duplication
2. **Event-Driven Integration**: Laravel Events connect Sales/Purchase → Finance → Accounting automatically
3. **Hierarchical Data**: Chart of Accounts uses self-referencing `parent_id` for multi-level structures (90+ accounts, 4 levels deep)
4. **Audit Trail**: Complete history tracking with `previous_stock` and `new_stock` in inventory movements
5. **Financial Integration**: Orders link to invoices, invoices link to journal entries, ensuring complete financial traceability

---

## ERD Diagram Files

All ERD diagrams are created in **DrawIO XML format** and can be opened/edited at [diagrams.net](https://app.diagrams.net).

### Available Diagrams

| File | Purpose | Tables | Focus Area |
|------|---------|--------|------------|
| **ERD-complete-system.drawio** | Complete system overview | 39+ | All 7 modules with color-coded grouping |
| **ERD-finance-accounting.drawio** | Financial subsystem | 17 | Finance (6 tables) + Accounting (11 tables) |
| **ERD-sales-purchase-inventory.drawio** | Operations subsystem | 14 | Product, Inventory, Sales, Purchase modules |

### How to Use

1. **Open**: Visit [diagrams.net](https://app.diagrams.net) and open the `.drawio` file
2. **Navigate**: Use zoom controls and pan to explore different sections
3. **Legend**: Each diagram includes a color-coded legend for module identification
4. **Relationships**: Crow's Foot notation shows cardinality (one-to-many relationships)
5. **Primary Keys**: Marked with 🔑 icon
6. **Foreign Keys**: Marked with 🔗 icon

---

## Database Statistics

### Overall System Metrics

- **Total Modules:** 9
- **Total Tables:** 54+
- **Total Relationships:** 60+ foreign key constraints
- **Total Indexes:** 70+ (including composites) - see [Performance Optimization](../performance/OPTIMIZATION_SESSION_LOG.md)
- **Database Engine:** MySQL 8.0 (Production), SQLite (Testing)
- **Character Set:** utf8mb4 (full Unicode support including emojis)
- **Collation:** utf8mb4_unicode_ci

### Tables by Module

| Module | Tables | Primary Entities | Complexity |
|--------|--------|------------------|------------|
| **Product** | 4 | Products, Units, Categories, Brands | Low |
| **Inventory** | 5 | Warehouses, Locations, Stock, Batches, Movements | High |
| **Contacts** | 4 | Contacts (Party), Addresses, Documents, Persons | Medium |
| **Sales** | 2 | Sales Orders, Order Items | Medium |
| **Purchase** | 2 | Purchase Orders, Order Items | Medium |
| **Ecommerce** | 11 | Carts, Checkout, Payments, Shipping, Wishlists, Reviews, Recommendations, Currencies | Very High |
| **Finance** | 6 | AR/AP Invoices, Payments, Applications, Bank Accounts | High |
| **Accounting** | 11 | Accounts, Journal Entries/Lines, Fiscal Periods | Very High |
| **Reports** | 0 | (Service layer only - uses existing tables) | Medium |
| **HR** | 9 | Departments, Positions, Employees, Attendance, Leaves, Payroll, Reviews | Very High |

---

## Module Breakdown

### 1. Product Module (4 Tables)

**Purpose:** Product catalog management with hierarchical categorization

#### products
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    iva DECIMAL(5,2) NOT NULL DEFAULT 16.00,  -- IVA % (Mexico tax)
    unit_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    brand_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,

    INDEX idx_products_sku (sku),
    INDEX idx_products_name (name),
    INDEX idx_products_category (category_id),
    INDEX idx_products_brand (brand_id),
    INDEX idx_products_unit (unit_id)
);
```

**Business Rules:**
- SKU must be unique across all products
- Unit is required (cannot be deleted if products exist)
- Category and Brand are optional (can be NULL)
- IVA defaults to 16% (standard Mexican VAT rate)
- `is_active` flag for soft deactivation without deletion

#### units
```sql
CREATE TABLE units (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    abbreviation VARCHAR(20) NOT NULL,
    type ENUM('weight', 'length', 'volume', 'unit') NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_units_type (type)
);
```

**Business Rules:**
- Type enum restricts to 4 measurement categories
- Examples: kg (weight), m (length), L (volume), pcs (unit)

#### categories
```sql
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    parent_id BIGINT UNSIGNED NULL,  -- For hierarchical categories
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_categories_slug (slug),
    INDEX idx_categories_parent (parent_id)
);
```

**Business Rules:**
- Slug must be URL-friendly and unique (used in ecommerce)
- Hierarchical structure supports nested categories (e.g., Electronics → Computers → Laptops)

#### brands
```sql
CREATE TABLE brands (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    logo VARCHAR(255) NULL,  -- File path to logo image
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_brands_slug (slug)
);
```

---

### 2. Inventory Module (5 Tables)

**Purpose:** Warehouse and stock management with complete audit trail

#### warehouses
```sql
CREATE TABLE warehouses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    warehouse_type ENUM('general', 'cold_storage', 'hazmat', 'transit', 'consignment') NOT NULL,

    -- Address fields
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'México',

    -- Capacity and operations
    max_capacity DECIMAL(12,2) NULL,  -- In cubic meters or custom unit
    current_utilization DECIMAL(5,2) DEFAULT 0.00,  -- Percentage 0-100
    operating_hours JSON NULL,  -- {"monday": "08:00-17:00", ...}

    -- Status and metadata
    is_active BOOLEAN DEFAULT TRUE,
    metadata JSON NULL,  -- Custom fields for specific business needs

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_warehouses_code (code),
    INDEX idx_warehouses_type (warehouse_type)
);
```

**JSON Field Examples:**
```json
// operating_hours
{
  "monday": "08:00-17:00",
  "tuesday": "08:00-17:00",
  "wednesday": "08:00-17:00",
  "thursday": "08:00-17:00",
  "friday": "08:00-15:00",
  "saturday": "closed",
  "sunday": "closed"
}

// metadata
{
  "temperature_controlled": true,
  "min_temperature": -20,
  "max_temperature": 2,
  "security_level": "high",
  "dock_doors": 6
}
```

#### warehouse_locations
```sql
CREATE TABLE warehouse_locations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) NOT NULL,  -- e.g., "A-12-03-04"
    location_type ENUM('bin', 'shelf', 'zone', 'cold', 'hazmat', 'bulk') NOT NULL,

    -- Physical location hierarchy
    aisle VARCHAR(50) NULL,      -- "A"
    rack VARCHAR(50) NULL,       -- "12"
    shelf VARCHAR(50) NULL,      -- "03"
    level VARCHAR(50) NULL,      -- "04"

    -- Physical attributes
    dimensions JSON NULL,  -- {"length": 1.2, "width": 0.8, "height": 2.0, "unit": "m"}
    max_weight DECIMAL(10,2) NULL,

    -- Operations
    is_pickable BOOLEAN DEFAULT TRUE,
    is_receivable BOOLEAN DEFAULT TRUE,
    priority INTEGER DEFAULT 0,  -- For picking optimization

    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_location_code (warehouse_id, code),
    INDEX idx_locations_warehouse (warehouse_id),
    INDEX idx_locations_type (location_type)
);
```

**Business Rules:**
- Location codes must be unique within a warehouse (composite UNIQUE constraint)
- Hierarchical addressing: Aisle → Rack → Shelf → Level
- Picking/receiving flags control operational workflows

#### stock
```sql
CREATE TABLE stock (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_location_id BIGINT UNSIGNED NULL,

    -- Quantity tracking
    quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    reserved_quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    available_quantity DECIMAL(12,3) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,

    -- Reorder logic
    minimum_stock DECIMAL(12,3) DEFAULT 0.000,
    maximum_stock DECIMAL(12,3) NULL,
    reorder_point DECIMAL(12,3) DEFAULT 0.000,

    -- Costing
    unit_cost DECIMAL(10,2) DEFAULT 0.00,
    total_value DECIMAL(15,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED,

    -- Status and batch info
    status ENUM('available', 'reserved', 'quarantine', 'damaged', 'expired') DEFAULT 'available',
    batch_info JSON NULL,  -- {"batch_id": 123, "expiration": "2025-12-31", ...}

    last_movement_date TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,

    UNIQUE KEY unique_stock (warehouse_id, product_id, warehouse_location_id),
    INDEX idx_stock_warehouse (warehouse_id),
    INDEX idx_stock_product (product_id),
    INDEX idx_stock_status (status)
);
```

**Key Features:**
- **Generated Columns**: `available_quantity` and `total_value` are automatically calculated
- **Reservation System**: `reserved_quantity` tracks allocated stock for orders
- **Reorder Logic**: `reorder_point` triggers purchase suggestions
- **Multiple Stock Records**: Same product can have stock in multiple locations within a warehouse

#### product_batches
```sql
CREATE TABLE product_batches (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    batch_number VARCHAR(100) NOT NULL UNIQUE,

    -- Date tracking
    manufacturing_date DATE NULL,
    expiration_date DATE NULL,
    best_before_date DATE NULL,
    received_date DATE NOT NULL,

    -- Quantity and costing
    original_quantity DECIMAL(12,3) NOT NULL,
    current_quantity DECIMAL(12,3) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,

    -- Quality control
    quality_status ENUM('pending', 'passed', 'failed', 'quarantine') DEFAULT 'pending',
    quality_notes TEXT NULL,
    test_results JSON NULL,
    certifications JSON NULL,

    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    INDEX idx_batches_product (product_id),
    INDEX idx_batches_expiration (expiration_date),
    INDEX idx_batches_status (quality_status)
);
```

**Business Rules:**
- **FEFO Strategy**: First Expired, First Out - system picks batches with earliest expiration
- Quality control workflow: pending → passed/failed/quarantine
- Batch tracking enables product recalls and traceability

#### inventory_movements
```sql
CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_location_id BIGINT UNSIGNED NULL,
    product_batch_id BIGINT UNSIGNED NULL,

    -- Movement details
    movement_type ENUM('entry', 'exit', 'transfer', 'adjustment') NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    unit_cost DECIMAL(10,2) NULL,

    -- Audit trail
    previous_stock DECIMAL(12,3) NOT NULL,  -- Stock before movement
    new_stock DECIMAL(12,3) NOT NULL,       -- Stock after movement

    -- Transfer movements (when movement_type = 'transfer')
    destination_warehouse_id BIGINT UNSIGNED NULL,
    destination_location_id BIGINT UNSIGNED NULL,

    -- Reference linking
    reference_type VARCHAR(100) NULL,  -- 'SalesOrder', 'PurchaseOrder', etc.
    reference_id BIGINT UNSIGNED NULL,

    -- GL Integration (Phase 3)
    gl_journal_entry_id BIGINT UNSIGNED NULL,
    gl_posting_status ENUM('pending', 'posted', 'failed', 'not_required') DEFAULT 'not_required',

    -- Metadata
    movement_date DATETIME NOT NULL,
    reason TEXT NULL,
    notes TEXT NULL,
    performed_by BIGINT UNSIGNED NOT NULL,  -- User ID

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_location_id) REFERENCES warehouse_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (product_batch_id) REFERENCES product_batches(id) ON DELETE SET NULL,
    FOREIGN KEY (destination_warehouse_id) REFERENCES warehouses(id) ON DELETE RESTRICT,
    FOREIGN KEY (gl_journal_entry_id) REFERENCES journal_entries(id) ON DELETE SET NULL,

    INDEX idx_movements_warehouse (warehouse_id),
    INDEX idx_movements_product (product_id),
    INDEX idx_movements_type (movement_type),
    INDEX idx_movements_date (movement_date),
    INDEX idx_movements_reference (reference_type, reference_id)
);
```

**Movement Types:**

1. **Entry** (receiving):
   - Increases stock
   - Typically from purchase orders
   - GL: DR Inventory Asset, CR AP/Cash

2. **Exit** (shipping):
   - Decreases stock
   - Typically from sales orders
   - GL: DR COGS, CR Inventory Asset

3. **Transfer** (between warehouses):
   - Decreases stock in source warehouse
   - Increases stock in destination warehouse
   - Requires `destination_warehouse_id`

4. **Adjustment** (manual correction):
   - Can increase or decrease stock
   - Used for cycle counts, damage, theft
   - GL: DR/CR Inventory Adjustment Expense

---

### 3. Contacts Module (4 Tables)

**Purpose:** Party Pattern implementation - unified contact management

#### contacts (Party Pattern)
```sql
CREATE TABLE contacts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    tax_id VARCHAR(100) NULL,  -- RFC in Mexico

    -- Party Pattern flags
    is_customer BOOLEAN DEFAULT FALSE,
    is_supplier BOOLEAN DEFAULT FALSE,
    is_employee BOOLEAN DEFAULT FALSE,
    is_other BOOLEAN DEFAULT FALSE,

    -- Customer-specific fields
    credit_limit DECIMAL(12,2) DEFAULT 0.00,
    payment_terms INTEGER DEFAULT 30,  -- Days
    payment_method_id BIGINT UNSIGNED NULL,

    -- Status and categorization
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    contact_type ENUM('individual', 'company') NOT NULL,
    industry VARCHAR(100) NULL,

    -- Metadata
    notes TEXT NULL,
    metadata JSON NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_contacts_customer (is_customer),
    INDEX idx_contacts_supplier (is_supplier),
    INDEX idx_contacts_email (email),
    INDEX idx_contacts_tax_id (tax_id),
    INDEX idx_contacts_status (status)
);
```

**Business Rules:**
- **Party Pattern**: Single table for all contact types (eliminates customer/supplier duplication)
- A contact can be both customer AND supplier simultaneously
- Tax ID (RFC) is critical for SAT compliance in Mexico
- Credit limit applies only to customers
- `is_employee` flag enables HR module future expansion

#### contact_addresses
```sql
CREATE TABLE contact_addresses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    contact_id BIGINT UNSIGNED NOT NULL,
    address_type ENUM('billing', 'shipping', 'both', 'office', 'warehouse') NOT NULL,

    -- Address fields
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'México',

    -- Flags
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,

    -- Contact info specific to this address
    contact_name VARCHAR(255) NULL,
    contact_phone VARCHAR(50) NULL,
    delivery_instructions TEXT NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    INDEX idx_addresses_contact (contact_id),
    INDEX idx_addresses_type (address_type)
);
```

**Business Rules:**
- Multiple addresses per contact
- Only one default address per contact (enforced in application layer)
- Cascade delete: when contact is deleted, addresses are removed

#### contact_documents
```sql
CREATE TABLE contact_documents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    contact_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM('rfc', 'id', 'contract', 'certification', 'other') NOT NULL,
    document_number VARCHAR(100) NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INTEGER NULL,  -- In bytes
    mime_type VARCHAR(100) NULL,

    expiration_date DATE NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by BIGINT UNSIGNED NULL,
    verified_at TIMESTAMP NULL,

    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    INDEX idx_documents_contact (contact_id),
    INDEX idx_documents_type (document_type),
    INDEX idx_documents_expiration (expiration_date)
);
```

**Business Rules:**
- Store file path (not binary data in database)
- Track expiration for certifications and IDs
- Verification workflow for compliance

#### contact_persons
```sql
CREATE TABLE contact_persons (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    contact_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(100) NULL,
    department VARCHAR(100) NULL,

    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    mobile VARCHAR(50) NULL,

    is_primary BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,

    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    INDEX idx_persons_contact (contact_id)
);
```

**Business Rules:**
- Multiple contact persons per company
- One primary contact per company
- Used in communications and approvals

---

### 4. Sales Module (2 Tables)

**Purpose:** Sales order management with invoice integration

#### sales_orders
```sql
CREATE TABLE sales_orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    contact_id BIGINT UNSIGNED NOT NULL,  -- is_customer = TRUE

    -- Dates
    order_date DATE NOT NULL,
    delivery_date DATE NULL,
    expected_delivery_date DATE NULL,

    -- Amounts
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    -- Status tracking
    status ENUM('pending', 'approved', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',

    -- Finance integration (Phase 2)
    ar_invoice_id BIGINT UNSIGNED NULL,
    invoicing_status ENUM('not_invoiced', 'partially_invoiced', 'fully_invoiced') DEFAULT 'not_invoiced',
    financial_status ENUM('not_posted', 'posted', 'paid', 'cancelled') DEFAULT 'not_posted',

    -- Shipping
    shipping_address_id BIGINT UNSIGNED NULL,
    shipping_method VARCHAR(100) NULL,
    tracking_number VARCHAR(100) NULL,

    -- Additional info
    payment_terms INTEGER DEFAULT 30,
    notes TEXT NULL,
    internal_notes TEXT NULL,

    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE RESTRICT,
    FOREIGN KEY (ar_invoice_id) REFERENCES ar_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (shipping_address_id) REFERENCES contact_addresses(id) ON DELETE SET NULL,

    INDEX idx_sales_orders_number (order_number),
    INDEX idx_sales_orders_contact (contact_id),
    INDEX idx_sales_orders_status (status),
    INDEX idx_sales_orders_date (order_date),
    INDEX idx_sales_orders_financial_status (financial_status)
);
```

**Event-Driven Integration:**
- When `status` changes to 'completed' → `SalesOrderCompleted` event fires
- Listener automatically creates AR invoice and GL entries
- Updates `ar_invoice_id`, `invoicing_status`, `financial_status`

#### sales_order_items
```sql
CREATE TABLE sales_order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    sales_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    -- Quantities
    quantity DECIMAL(12,3) NOT NULL,
    shipped_quantity DECIMAL(12,3) DEFAULT 0.000,
    invoiced_quantity DECIMAL(12,3) DEFAULT 0.000,

    -- Pricing
    unit_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 16.00,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,

    -- Line totals
    line_subtotal DECIMAL(12,2) NOT NULL,
    line_tax DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,

    -- Finance integration
    ar_invoice_line_id BIGINT UNSIGNED NULL,
    invoiced_amount DECIMAL(12,2) DEFAULT 0.00,

    -- Inventory tracking
    warehouse_id BIGINT UNSIGNED NULL,
    reserved BOOLEAN DEFAULT FALSE,

    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,

    INDEX idx_order_items_order (sales_order_id),
    INDEX idx_order_items_product (product_id)
);
```

**Business Rules:**
- Line totals calculated: `line_subtotal = quantity × unit_price × (1 - discount_percentage)`
- Tax calculated: `line_tax = line_subtotal × tax_rate`
- Total: `line_total = line_subtotal + line_tax`
- `reserved` flag triggers inventory reservation

---

### 5. Purchase Module (2 Tables)

**Purpose:** Purchase order management with invoice integration

#### purchase_orders
```sql
CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    contact_id BIGINT UNSIGNED NOT NULL,  -- is_supplier = TRUE

    -- Dates
    order_date DATE NOT NULL,
    expected_delivery_date DATE NULL,
    received_date DATE NULL,

    -- Amounts
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    -- Status tracking
    status ENUM('pending', 'approved', 'ordered', 'received', 'completed', 'cancelled') DEFAULT 'pending',

    -- Finance integration (Phase 2)
    ap_invoice_id BIGINT UNSIGNED NULL,
    invoicing_status ENUM('not_invoiced', 'partially_invoiced', 'fully_invoiced') DEFAULT 'not_invoiced',
    financial_status ENUM('not_posted', 'posted', 'paid', 'cancelled') DEFAULT 'not_posted',

    -- Receiving warehouse
    warehouse_id BIGINT UNSIGNED NULL,

    -- Additional info
    payment_terms INTEGER DEFAULT 30,
    notes TEXT NULL,
    internal_notes TEXT NULL,

    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE RESTRICT,
    FOREIGN KEY (ap_invoice_id) REFERENCES ap_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL,

    INDEX idx_purchase_orders_number (order_number),
    INDEX idx_purchase_orders_contact (contact_id),
    INDEX idx_purchase_orders_status (status),
    INDEX idx_purchase_orders_date (order_date),
    INDEX idx_purchase_orders_financial_status (financial_status)
);
```

**Event-Driven Integration:**
- When `status` changes to 'received' → `PurchaseOrderReceived` event fires
- Listener automatically creates AP invoice and GL entries
- Updates `ap_invoice_id`, `invoicing_status`, `financial_status`

#### purchase_order_items
```sql
CREATE TABLE purchase_order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    -- Quantities
    quantity DECIMAL(12,3) NOT NULL,
    received_quantity DECIMAL(12,3) DEFAULT 0.000,
    invoiced_quantity DECIMAL(12,3) DEFAULT 0.000,

    -- Pricing
    unit_price DECIMAL(10,2) NOT NULL,
    tax_rate DECIMAL(5,2) DEFAULT 16.00,

    -- Line totals
    line_subtotal DECIMAL(12,2) NOT NULL,
    line_tax DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,

    -- Finance integration
    ap_invoice_line_id BIGINT UNSIGNED NULL,
    invoiced_amount DECIMAL(12,2) DEFAULT 0.00,

    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,

    INDEX idx_order_items_order (purchase_order_id),
    INDEX idx_order_items_product (product_id)
);
```

---

### 6. Finance Module (6 Tables)

**Purpose:** Accounts Receivable, Accounts Payable, and Payment processing

#### ar_invoices (Accounts Receivable)
```sql
CREATE TABLE ar_invoices (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    contact_id BIGINT UNSIGNED NOT NULL,  -- Customer
    sales_order_id BIGINT UNSIGNED NULL,

    -- Dates
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE NULL,

    -- Amounts
    subtotal DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    remaining_balance DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,

    -- Status
    status ENUM('draft', 'pending_approval', 'approved', 'posted', 'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',

    -- GL Integration (Phase 2)
    journal_entry_id BIGINT UNSIGNED NULL,
    gl_posting_status ENUM('pending', 'posted', 'failed') DEFAULT 'pending',
    gl_posted_at TIMESTAMP NULL,

    -- Payment tracking
    payment_terms INTEGER DEFAULT 30,
    payment_method_id BIGINT UNSIGNED NULL,

    -- SAT Mexico (CFDI)
    cfdi_uuid VARCHAR(100) NULL UNIQUE,
    cfdi_xml_path VARCHAR(500) NULL,
    cfdi_status ENUM('pending', 'stamped', 'cancelled') DEFAULT 'pending',

    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE RESTRICT,
    FOREIGN KEY (sales_order_id) REFERENCES sales_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE SET NULL,

    INDEX idx_ar_invoices_number (invoice_number),
    INDEX idx_ar_invoices_contact (contact_id),
    INDEX idx_ar_invoices_status (status),
    INDEX idx_ar_invoices_date (invoice_date),
    INDEX idx_ar_invoices_due_date (due_date),
    INDEX idx_ar_invoices_contact_status (contact_id, status),  -- Composite for aging
    INDEX idx_ar_invoices_status_due (status, due_date)  -- Composite for overdue detection
);
```

**GL Posting (Automatic):**
```
DR  Accounts Receivable (Customer)   $total_amount
    CR  Revenue (Sales)                  $subtotal
    CR  VAT Payable                      $tax_amount
```

**Status Flow:**
```
draft → pending_approval → approved → posted → partially_paid → paid
                                   ↘ overdue (if past due_date)
```

#### ap_invoices (Accounts Payable)
```sql
CREATE TABLE ap_invoices (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_invoice_number VARCHAR(100) NULL,  -- Supplier's invoice #
    contact_id BIGINT UNSIGNED NOT NULL,  -- Supplier
    purchase_order_id BIGINT UNSIGNED NULL,

    -- Dates
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    paid_date DATE NULL,
    received_date DATE NULL,

    -- Amounts
    subtotal DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(12,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0.00,
    remaining_balance DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,

    -- Status
    status ENUM('draft', 'pending_approval', 'approved', 'posted', 'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',

    -- GL Integration (Phase 2)
    journal_entry_id BIGINT UNSIGNED NULL,
    gl_posting_status ENUM('pending', 'posted', 'failed') DEFAULT 'pending',
    gl_posted_at TIMESTAMP NULL,

    -- Payment tracking
    payment_terms INTEGER DEFAULT 30,
    payment_method_id BIGINT UNSIGNED NULL,

    -- SAT Mexico (CFDI received)
    cfdi_uuid VARCHAR(100) NULL,
    cfdi_xml_path VARCHAR(500) NULL,

    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE RESTRICT,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE SET NULL,

    INDEX idx_ap_invoices_number (invoice_number),
    INDEX idx_ap_invoices_contact (contact_id),
    INDEX idx_ap_invoices_status (status),
    INDEX idx_ap_invoices_date (invoice_date),
    INDEX idx_ap_invoices_due_date (due_date),
    INDEX idx_ap_invoices_contact_status (contact_id, status),
    INDEX idx_ap_invoices_status_due (status, due_date)
);
```

**GL Posting (Automatic):**
```
DR  Expense (Purchases/COGS)     $subtotal
DR  VAT Recoverable               $tax_amount
    CR  Accounts Payable (Supplier)  $total_amount
```

#### payments
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payment_number VARCHAR(50) NOT NULL UNIQUE,
    contact_id BIGINT UNSIGNED NOT NULL,
    payment_type ENUM('received', 'sent') NOT NULL,  -- AR or AP payment

    -- Payment details
    payment_date DATE NOT NULL,
    payment_amount DECIMAL(12,2) NOT NULL,
    applied_amount DECIMAL(12,2) DEFAULT 0.00,
    unapplied_amount DECIMAL(12,2) GENERATED ALWAYS AS (payment_amount - applied_amount) STORED,

    -- Banking
    bank_account_id BIGINT UNSIGNED NOT NULL,
    payment_method_id BIGINT UNSIGNED NOT NULL,
    reference_number VARCHAR(100) NULL,  -- Check number, transfer reference, etc.

    -- GL Integration
    journal_entry_id BIGINT UNSIGNED NULL,
    gl_posting_status ENUM('pending', 'posted', 'failed') DEFAULT 'pending',

    -- Status
    status ENUM('pending', 'cleared', 'reconciled', 'cancelled') DEFAULT 'pending',

    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE RESTRICT,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE RESTRICT,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE SET NULL,

    INDEX idx_payments_number (payment_number),
    INDEX idx_payments_contact (contact_id),
    INDEX idx_payments_date (payment_date),
    INDEX idx_payments_type (payment_type),
    INDEX idx_payments_status (status),
    INDEX idx_payments_bank_account (bank_account_id)
);
```

**GL Posting (Payment Received - AR):**
```
DR  Bank Account              $payment_amount
    CR  Accounts Receivable       $payment_amount
```

**GL Posting (Payment Sent - AP):**
```
DR  Accounts Payable         $payment_amount
    CR  Bank Account              $payment_amount
```

#### payment_applications
```sql
CREATE TABLE payment_applications (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    payment_id BIGINT UNSIGNED NOT NULL,

    -- Polymorphic relationship (can apply to AR or AP invoice)
    invoice_type ENUM('ar_invoice', 'ap_invoice') NOT NULL,
    invoice_id BIGINT UNSIGNED NOT NULL,

    applied_amount DECIMAL(12,2) NOT NULL,
    application_date DATE NOT NULL,

    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_applications_payment (payment_id),
    INDEX idx_applications_invoice (invoice_type, invoice_id)
);
```

**Business Rules:**
- One payment can be applied to multiple invoices (partial payments)
- Sum of applications cannot exceed payment amount
- When application created → updates invoice `paid_amount` and payment `applied_amount`

#### bank_accounts
```sql
CREATE TABLE bank_accounts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    account_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    account_type ENUM('checking', 'savings', 'credit_card', 'money_market') NOT NULL,
    currency VARCHAR(3) DEFAULT 'MXN',

    -- GL Integration
    gl_account_id BIGINT UNSIGNED NULL,  -- Links to Chart of Accounts

    -- Balance tracking
    current_balance DECIMAL(15,2) DEFAULT 0.00,
    available_balance DECIMAL(15,2) DEFAULT 0.00,
    last_reconciliation_date DATE NULL,

    is_active BOOLEAN DEFAULT TRUE,
    is_default BOOLEAN DEFAULT FALSE,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (gl_account_id) REFERENCES accounts(id) ON DELETE SET NULL,
    INDEX idx_bank_accounts_account_number (account_number),
    INDEX idx_bank_accounts_gl_account (gl_account_id)
);
```

#### payment_methods
```sql
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    type ENUM('cash', 'check', 'transfer', 'credit_card', 'debit_card', 'other') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Standard Payment Methods:**
- 01: Cash (Efectivo)
- 02: Check (Cheque)
- 03: Electronic Transfer (Transferencia)
- 04: Credit Card (Tarjeta de Crédito)
- 99: Other (Otro)

---

### 7. Accounting Module (11 Tables)

**Purpose:** General Ledger, Journal Entries, Fiscal Periods, Audit Trail

#### accounts (Chart of Accounts)
```sql
CREATE TABLE accounts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,

    -- Hierarchical structure
    parent_id BIGINT UNSIGNED NULL,
    level INTEGER DEFAULT 1,

    -- Balance attributes
    normal_balance ENUM('debit', 'credit') NOT NULL,
    current_balance DECIMAL(15,2) DEFAULT 0.00,

    -- Operational flags
    is_active BOOLEAN DEFAULT TRUE,
    allow_manual_entries BOOLEAN DEFAULT TRUE,
    require_dimensions BOOLEAN DEFAULT FALSE,

    -- SAT Mexico compliance
    sat_code VARCHAR(20) NULL,  -- Código agrupador SAT

    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (parent_id) REFERENCES accounts(id) ON DELETE RESTRICT,
    INDEX idx_accounts_code (code),
    INDEX idx_accounts_type (account_type),
    INDEX idx_accounts_parent (parent_id)
);
```

**Account Hierarchy Example:**
```
1000 - Assets (Level 1, parent_id = NULL)
  1100 - Current Assets (Level 2, parent_id = 1000)
    1110 - Cash & Equivalents (Level 3, parent_id = 1100)
      1111 - Bank BBVA (Level 4, parent_id = 1110)
      1112 - Bank Santander (Level 4, parent_id = 1110)
```

**System has 90+ accounts in 4 levels**

#### journal_entries
```sql
CREATE TABLE journal_entries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    entry_number VARCHAR(50) NOT NULL UNIQUE,
    entry_date DATE NOT NULL,
    fiscal_period_id BIGINT UNSIGNED NOT NULL,
    journal_id BIGINT UNSIGNED NOT NULL,

    -- Entry details
    description TEXT NOT NULL,
    reference VARCHAR(100) NULL,
    source_type VARCHAR(100) NULL,  -- 'ARInvoice', 'Payment', 'Manual', etc.
    source_id BIGINT UNSIGNED NULL,

    -- Calculated totals (updated by MySQL triggers)
    total_debit DECIMAL(15,2) DEFAULT 0.00,
    total_credit DECIMAL(15,2) DEFAULT 0.00,

    -- Status
    status ENUM('draft', 'pending_approval', 'approved', 'posted', 'reversed', 'cancelled') DEFAULT 'draft',

    -- Approval workflow
    posted_by BIGINT UNSIGNED NULL,
    posted_at TIMESTAMP NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,

    -- Reversal tracking
    reversed_by_entry_id BIGINT UNSIGNED NULL,
    reversal_of_entry_id BIGINT UNSIGNED NULL,

    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (fiscal_period_id) REFERENCES fiscal_periods(id) ON DELETE RESTRICT,
    FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE RESTRICT,

    INDEX idx_journal_entries_number (entry_number),
    INDEX idx_journal_entries_date (entry_date),
    INDEX idx_journal_entries_period (fiscal_period_id),
    INDEX idx_journal_entries_status (status),
    INDEX idx_journal_entries_source (source_type, source_id),
    INDEX idx_journal_entries_period_status (fiscal_period_id, status)  -- Composite
);
```

**MySQL Triggers (Automatic Balance Calculation):**
```sql
-- After INSERT on journal_lines
CREATE TRIGGER journal_lines_after_insert
AFTER INSERT ON journal_lines
FOR EACH ROW
UPDATE journal_entries
SET total_debit = (SELECT SUM(debit_amount) FROM journal_lines WHERE journal_entry_id = NEW.journal_entry_id),
    total_credit = (SELECT SUM(credit_amount) FROM journal_lines WHERE journal_entry_id = NEW.journal_entry_id)
WHERE id = NEW.journal_entry_id;
```

**Status Flow:**
```
draft → pending_approval → approved → posted
                                   ↗ reversed (creates reversal entry)
```

#### journal_lines
```sql
CREATE TABLE journal_lines (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    journal_entry_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    line_number INTEGER NOT NULL,

    -- Amounts (XOR constraint: either debit OR credit, never both)
    debit_amount DECIMAL(15,2) DEFAULT 0.00,
    credit_amount DECIMAL(15,2) DEFAULT 0.00,

    description TEXT NULL,
    reference VARCHAR(100) NULL,

    -- Dimensions (cost center, project, etc.)
    dimension1_value VARCHAR(100) NULL,
    dimension2_value VARCHAR(100) NULL,
    dimension3_value VARCHAR(100) NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE RESTRICT,

    INDEX idx_journal_lines_entry (journal_entry_id),
    INDEX idx_journal_lines_account (account_id),

    -- CHECK constraint: Either debit OR credit must be > 0, but not both
    CONSTRAINT check_debit_credit_xor CHECK (
        (debit_amount > 0 AND credit_amount = 0) OR
        (credit_amount > 0 AND debit_amount = 0)
    )
);
```

**XOR Constraint Explanation:**
- Every line must have EITHER a debit OR a credit
- Never both debit and credit on the same line
- Never zero on both sides
- Enforced at database level for data integrity

#### fiscal_periods
```sql
CREATE TABLE fiscal_periods (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    fiscal_year INTEGER NOT NULL,
    period_number INTEGER NOT NULL,

    start_date DATE NOT NULL,
    end_date DATE NOT NULL,

    status ENUM('open', 'locked', 'closed') DEFAULT 'open',

    locked_by BIGINT UNSIGNED NULL,
    locked_at TIMESTAMP NULL,
    closed_by BIGINT UNSIGNED NULL,
    closed_at TIMESTAMP NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_period (fiscal_year, period_number),
    INDEX idx_fiscal_periods_year (fiscal_year),
    INDEX idx_fiscal_periods_status (status),
    INDEX idx_fiscal_periods_dates (start_date, end_date)
);
```

**Period Control Rules:**
- **Open**: Anyone can post entries
- **Locked**: Only users with `override-period-lock` permission can post
- **Closed**: NO posting allowed (enforced in PeriodControlService)

**Standard Setup (Monthly):**
```
2025-01: Period 1 (Jan 1 - Jan 31)
2025-02: Period 2 (Feb 1 - Feb 28)
...
2025-12: Period 12 (Dec 1 - Dec 31)
```

#### journals
```sql
CREATE TABLE journals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    journal_type ENUM('general', 'sales', 'purchases', 'cash_receipts', 'cash_disbursements', 'adjustments') NOT NULL,
    description TEXT NULL,

    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Standard Journals:**
- GJ: General Journal (manual entries)
- SJ: Sales Journal (AR invoices)
- PJ: Purchase Journal (AP invoices)
- CRJ: Cash Receipts Journal (payments received)
- CDJ: Cash Disbursements Journal (payments sent)
- AJ: Adjusting Journal (period-end adjustments)

#### journal_sequences
```sql
CREATE TABLE journal_sequences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    journal_id BIGINT UNSIGNED NOT NULL,
    fiscal_year INTEGER NOT NULL,
    last_number INTEGER DEFAULT 0,
    prefix VARCHAR(20) NULL,

    UNIQUE KEY unique_sequence (journal_id, fiscal_year),
    FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
);
```

**Automatic Numbering:**
- Each journal has independent sequence per fiscal year
- Example: SJ-2025-0001, SJ-2025-0002, ..., SJ-2025-9999
- SequenceService uses `SELECT ... FOR UPDATE` to prevent race conditions

#### exchange_rates
```sql
CREATE TABLE exchange_rates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    from_currency VARCHAR(3) NOT NULL,
    to_currency VARCHAR(3) NOT NULL,
    rate DECIMAL(12,6) NOT NULL,
    effective_date DATE NOT NULL,
    source VARCHAR(100) NULL,  -- 'Banxico', 'Manual', 'BCE'

    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_rate (from_currency, to_currency, effective_date),
    INDEX idx_exchange_rates_currencies (from_currency, to_currency),
    INDEX idx_exchange_rates_date (effective_date)
);
```

**Usage:**
- Multi-currency transactions
- Foreign exchange gains/losses
- Revaluation of foreign currency balances

#### account_balances
```sql
CREATE TABLE account_balances (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    account_id BIGINT UNSIGNED NOT NULL,
    fiscal_period_id BIGINT UNSIGNED NOT NULL,

    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    period_debit DECIMAL(15,2) DEFAULT 0.00,
    period_credit DECIMAL(15,2) DEFAULT 0.00,
    closing_balance DECIMAL(15,2) GENERATED ALWAYS AS (
        opening_balance + period_debit - period_credit
    ) STORED,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_account_period (account_id, fiscal_period_id),
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (fiscal_period_id) REFERENCES fiscal_periods(id) ON DELETE CASCADE,

    INDEX idx_balances_account (account_id),
    INDEX idx_balances_period (fiscal_period_id)
);
```

**Generated Column:**
- `closing_balance` automatically calculated
- For Asset/Expense (DR normal): closing = opening + debits - credits
- Adjusted in application layer for CR normal accounts

#### idempotency_keys (Phase 3)
```sql
CREATE TABLE idempotency_keys (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    idempotency_key VARCHAR(255) NOT NULL UNIQUE,
    request_type VARCHAR(100) NOT NULL,
    request_data JSON NOT NULL,
    response_data JSON NULL,

    created_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,

    INDEX idx_idempotency_expires (expires_at)
);
```

**Purpose:**
- Prevent duplicate AR/AP invoice creation from events
- Event-driven architecture safety
- Key format: `sales_order_completed_{order_id}` or `purchase_order_received_{order_id}`

#### critical_action_logs (Phase 3 - SAT Compliance)
```sql
CREATE TABLE critical_action_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    log_type ENUM('journal_entry', 'fiscal_period', 'user_permission', 'data_export', 'data_deletion') NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,

    -- Audit details
    user_id BIGINT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,

    -- Data snapshot
    before_data JSON NULL,
    after_data JSON NULL,
    changes_summary TEXT NULL,

    -- Integrity
    hash VARCHAR(255) NOT NULL,  -- SHA256 hash for integrity verification

    -- Compliance
    reason TEXT NULL,
    approved_by BIGINT UNSIGNED NULL,

    created_at TIMESTAMP NULL,

    INDEX idx_critical_logs_type (log_type),
    INDEX idx_critical_logs_entity (entity_type, entity_id),
    INDEX idx_critical_logs_user (user_id),
    INDEX idx_critical_logs_created (created_at)
);
```

**SAT Compliance (Mexico):**
- **Retention Period**: 7-15 years (configurable)
- **Integrity Verification**: SHA256 hash of log entry prevents tampering
- **Immutable**: No updates/deletes allowed (only INSERT)
- **Critical Actions Logged**:
  - Journal entry posting
  - Fiscal period lock/close
  - User permission changes
  - Financial data exports
  - Record deletions

---

## Relationship Explanations

### Foreign Key Strategy

All foreign keys follow consistent naming and behavior:

**Naming Convention:**
- `{referenced_table_singular}_id`
- Examples: `contact_id`, `product_id`, `warehouse_id`

**Deletion Behavior:**

1. **RESTRICT** (Protect Data Integrity):
   - Used when child records MUST NOT be orphaned
   - Examples:
     - `products.unit_id → units.id` (can't delete unit if products use it)
     - `stock.warehouse_id → warehouses.id` (can't delete warehouse with stock)
     - `ar_invoices.contact_id → contacts.id` (can't delete customer with invoices)

2. **CASCADE** (Automatic Cleanup):
   - Used for dependent/child records that are meaningless without parent
   - Examples:
     - `contact_addresses.contact_id → contacts.id` (delete addresses when contact deleted)
     - `journal_lines.journal_entry_id → journal_entries.id` (delete lines when entry deleted)
     - `sales_order_items.sales_order_id → sales_orders.id`

3. **SET NULL** (Optional Relationships):
   - Used for reference data that can be broken without issue
   - Examples:
     - `products.category_id → categories.id` (product can exist without category)
     - `ar_invoices.journal_entry_id → journal_entries.id` (invoice survives if GL entry reversed)

### Key Relationships

#### 1. Party Pattern (Contacts ← Finance/Sales/Purchase)
```
contacts (Party Pattern)
├─→ ar_invoices (WHERE is_customer = TRUE)
├─→ ap_invoices (WHERE is_supplier = TRUE)
├─→ sales_orders (WHERE is_customer = TRUE)
├─→ purchase_orders (WHERE is_supplier = TRUE)
└─→ payments (both customer and supplier payments)
```

**Eliminates Duplication:**
- Before: Separate `customers` and `suppliers` tables (duplicate contact info)
- After: Single `contacts` table with boolean flags
- Benefit: Contact can be both customer AND supplier without duplication

#### 2. Order-to-Cash Flow (Sales → Finance → Accounting)
```
sales_orders
├─→ sales_order_items (cascade)
├─→ ar_invoices (event-driven creation)
    ├─→ journal_entries (GL posting)
    │   └─→ journal_lines (DR Customers, CR Revenue)
    └─→ payments (via payment_applications)
        └─→ journal_entries (GL posting)
            └─→ journal_lines (DR Bank, CR Customers)
```

**Event Flow:**
1. Sales Order created with status='pending'
2. User changes status to 'completed'
3. **SalesOrderCompleted** event fires
4. Listener creates AR Invoice automatically
5. AR Invoice posting creates GL Journal Entry
6. Sales Order updated with `ar_invoice_id` and `financial_status='posted'`

#### 3. Procure-to-Pay Flow (Purchase → Finance → Accounting)
```
purchase_orders
├─→ purchase_order_items (cascade)
├─→ ap_invoices (event-driven creation)
    ├─→ journal_entries (GL posting)
    │   └─→ journal_lines (DR Expenses, CR Payables)
    └─→ payments (via payment_applications)
        └─→ journal_entries (GL posting)
            └─→ journal_lines (DR Payables, CR Bank)
```

#### 4. Inventory Integration (Purchase → Inventory → Accounting)
```
purchase_orders (received)
└─→ inventory_movements (entry)
    ├─→ stock (quantity increase)
    └─→ journal_entries (GL posting)
        └─→ journal_lines (DR Inventory Asset, CR Payables)
```

#### 5. Hierarchical Chart of Accounts
```
accounts (self-referencing)
├─→ parent_id → accounts.id
└─→ Example:
    1000 Assets (parent_id=NULL)
      1100 Current Assets (parent_id=1000)
        1110 Cash (parent_id=1100)
          1111 Bank BBVA (parent_id=1110)
```

**Query Pattern (Recursive CTE):**
```sql
WITH RECURSIVE account_hierarchy AS (
    SELECT id, code, name, parent_id, 1 as level
    FROM accounts
    WHERE parent_id IS NULL

    UNION ALL

    SELECT a.id, a.code, a.name, a.parent_id, ah.level + 1
    FROM accounts a
    JOIN account_hierarchy ah ON a.parent_id = ah.id
)
SELECT * FROM account_hierarchy
ORDER BY level, code;
```

---

## Index Strategies

### Index Categories

#### 1. Primary Keys (Automatic)
- **Type**: B-Tree, UNIQUE, NOT NULL
- **Performance**: O(log n) lookups
- **All tables** have `id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT`

#### 2. Foreign Key Indexes (Critical for Joins)
```sql
-- Example: ar_invoices
INDEX idx_ar_invoices_contact (contact_id),      -- JOIN contacts
INDEX idx_ar_invoices_sales_order (sales_order_id),  -- JOIN sales_orders
INDEX idx_ar_invoices_journal (journal_entry_id)     -- JOIN journal_entries
```

**Impact**: 50-80% faster JOIN operations

#### 3. Filter Indexes (Common WHERE Clauses)
```sql
-- Status columns (very common filters)
INDEX idx_products_active (is_active),
INDEX idx_ar_invoices_status (status),
INDEX idx_journal_entries_status (status)

-- Date columns (range queries, aging reports)
INDEX idx_ar_invoices_date (invoice_date),
INDEX idx_ar_invoices_due_date (due_date),
INDEX idx_journal_entries_date (entry_date)
```

**Impact**: 40-60% faster filtered queries

#### 4. Unique Indexes (Business Constraints)
```sql
-- Prevent duplicates
UNIQUE INDEX unique_sku (sku),                    -- products
UNIQUE INDEX unique_order_number (order_number),  -- sales_orders
UNIQUE INDEX unique_invoice_number (invoice_number) -- ar_invoices
```

**Dual Purpose**:
- Data integrity (unique constraint)
- Fast lookup (index)

#### 5. Composite Indexes (Multi-Column Queries)
```sql
-- AR aging analysis (contact + status)
INDEX idx_ar_invoices_contact_status (contact_id, status)
-- Query: WHERE contact_id = 123 AND status = 'posted'

-- Overdue detection (status + due_date)
INDEX idx_ar_invoices_status_due (status, due_date)
-- Query: WHERE status IN ('posted', 'partially_paid') AND due_date < CURRENT_DATE

-- Period queries (period + status)
INDEX idx_journal_entries_period_status (fiscal_period_id, status)
-- Query: WHERE fiscal_period_id = 12 AND status = 'posted'
```

**Impact**: 70-90% faster on multi-condition queries

**Column Order Matters:**
- Most selective column first
- `(contact_id, status)` better than `(status, contact_id)` because contact_id has higher cardinality

#### 6. Search/Sort Indexes
```sql
-- Full-text search candidates
INDEX idx_products_name (name),
INDEX idx_contacts_name (name),
INDEX idx_contacts_email (email)

-- Number fields often used in search
INDEX idx_products_sku (sku),
INDEX idx_accounts_code (code)
```

### Index Performance Analysis

See [Performance Optimization Log](../performance/OPTIMIZATION_SESSION_LOG.md) for detailed measurements:

- **Total Indexes**: 70+ across all tables
- **Performance Gain**: 30-50% average query speed improvement
- **Baseline Before Indexes**: 75.45ms average, 95.28ms p95
- **Projected After Indexes**: ~55ms average (-27%), ~70ms p95 (-26%)

---

## Constraint Documentation

### CHECK Constraints

#### 1. XOR Constraint (Journal Lines)
```sql
ALTER TABLE journal_lines
ADD CONSTRAINT check_debit_credit_xor CHECK (
    (debit_amount > 0 AND credit_amount = 0) OR
    (credit_amount > 0 AND debit_amount = 0)
);
```

**Purpose**: Ensure every line has EITHER debit OR credit, never both, never neither

**Enforcement**: Database level (MySQL 8.0+)

#### 2. Status Enum Constraints
```sql
-- Example: ar_invoices
status ENUM('draft', 'pending_approval', 'approved', 'posted',
            'partially_paid', 'paid', 'overdue', 'cancelled') DEFAULT 'draft'
```

**Purpose**:
- Restrict to valid status values
- Type safety
- Index optimization (small value set)

**All Status Enums:**
- `ar_invoices.status`: 8 values
- `ap_invoices.status`: 8 values
- `payments.status`: 4 values
- `journal_entries.status`: 6 values
- `fiscal_periods.status`: 3 values (open/locked/closed)
- `sales_orders.status`: 5 values
- `purchase_orders.status`: 6 values
- `inventory_movements.movement_type`: 4 values (entry/exit/transfer/adjustment)

#### 3. UNIQUE Constraints
```sql
-- Single column
UNIQUE (sku)              -- products
UNIQUE (email)            -- users
UNIQUE (invoice_number)   -- ar_invoices

-- Composite (multi-column)
UNIQUE (warehouse_id, code)           -- warehouse_locations
UNIQUE (account_id, fiscal_period_id) -- account_balances
UNIQUE (fiscal_year, period_number)   -- fiscal_periods
```

**Purpose**: Prevent duplicate business keys

### Foreign Key Constraints

**Total**: 40+ foreign key constraints across all tables

**Enforcement**:
- Referential integrity at database level
- Prevents orphaned records
- Cascading behavior (RESTRICT/CASCADE/SET NULL)

**Benefits**:
- Data consistency guaranteed
- No application bugs can create orphaned records
- Database-level validation

---

## Business Rule Enforcement

### Database-Level Enforcement

#### 1. Generated Columns (Automatic Calculation)
```sql
-- ar_invoices
remaining_balance DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED

-- stock
available_quantity DECIMAL(12,3) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED
total_value DECIMAL(15,2) GENERATED ALWAYS AS (quantity * unit_cost) STORED

-- payments
unapplied_amount DECIMAL(12,2) GENERATED ALWAYS AS (payment_amount - applied_amount) STORED

-- account_balances
closing_balance DECIMAL(15,2) GENERATED ALWAYS AS (opening_balance + period_debit - period_credit) STORED
```

**Benefits:**
- Always accurate (cannot be out of sync)
- Zero maintenance cost
- Query-optimized (stored, not virtual)

#### 2. MySQL Triggers (Automatic Aggregation)
```sql
-- Calculate journal entry totals from lines
CREATE TRIGGER journal_lines_after_insert AFTER INSERT ON journal_lines
FOR EACH ROW
UPDATE journal_entries
SET total_debit = (SELECT SUM(debit_amount) FROM journal_lines WHERE journal_entry_id = NEW.journal_entry_id),
    total_credit = (SELECT SUM(credit_amount) FROM journal_lines WHERE journal_entry_id = NEW.journal_entry_id)
WHERE id = NEW.journal_entry_id;
```

**4 Triggers Implemented:**
- `journal_lines_after_insert`
- `journal_lines_after_update`
- `journal_lines_after_delete`
- Purpose: Keep `journal_entries.total_debit` and `total_credit` synchronized

### Application-Level Enforcement

#### 1. Credit Management (Phase 3)
```php
// CreditManagementService.php
public function validateCustomerCredit(int $contactId, float $orderAmount): array
{
    $creditLimit = $contact->credit_limit;
    $currentARBalance = $this->getCurrentARBalance($contactId);
    $overdueAmount = $this->getOverdueAmount($contactId);
    $paymentScore = $this->calculatePaymentScore($contactId);

    // Rules:
    // 1. Credit limit check
    // 2. Overdue detection
    // 3. Payment score threshold (60%)

    if ($currentARBalance + $orderAmount > $creditLimit) {
        return ['approved' => false, 'reason' => 'Credit limit exceeded'];
    }

    if ($overdueAmount > 0) {
        return ['approved' => false, 'reason' => 'Overdue invoices exist'];
    }

    if ($paymentScore < 60) {
        return ['approved' => false, 'reason' => 'Low payment score'];
    }

    return ['approved' => true];
}
```

**Payment Score Formula:**
```
payment_score = (on_time_payments / total_paid_invoices) × 100
```

#### 2. Approval Workflows (Phase 3)
```php
// ApprovalWorkflowService.php
public function requiresARApproval(float $amount, int $contactId): array
{
    $contact = Contact::findOrFail($contactId);

    // Tier 1: Over $100,000
    if ($amount > 100000) {
        return ['required' => true, 'tier' => 1, 'approvers' => ['CFO']];
    }

    // Tier 2: Over $50,000
    if ($amount > 50000) {
        return ['required' => true, 'tier' => 2, 'approvers' => ['Finance Manager']];
    }

    // Tier 3: First-time customer OR over $10,000
    if (!$contact->has_prior_orders || $amount > 10000) {
        return ['required' => true, 'tier' => 3, 'approvers' => ['Sales Manager']];
    }

    return ['required' => false];
}
```

#### 3. Period Control (Phase 3)
```php
// PeriodControlService.php
public function validatePeriodAccess(int $periodId, int $userId): bool
{
    $period = FiscalPeriod::findOrFail($periodId);

    if ($period->status === 'open') {
        return true;  // Anyone can post
    }

    if ($period->status === 'locked') {
        return User::find($userId)->hasPermissionTo('override-period-lock');
    }

    if ($period->status === 'closed') {
        return false;  // NO ONE can post
    }
}
```

#### 4. FEFO Strategy (Inventory)
```php
// When picking for sales order, select batch with earliest expiration
$batch = ProductBatch::where('product_id', $productId)
    ->where('warehouse_id', $warehouseId)
    ->where('current_quantity', '>=', $requiredQuantity)
    ->where('quality_status', 'passed')
    ->orderBy('expiration_date', 'ASC')  // First Expired
    ->first();
```

---

## Query Optimization Tips

### 1. Use Indexes Effectively

**DO:**
```sql
-- Use indexed columns in WHERE
SELECT * FROM ar_invoices WHERE contact_id = 123;  -- Uses idx_ar_invoices_contact

-- Use composite index in correct order
SELECT * FROM ar_invoices
WHERE contact_id = 123 AND status = 'posted';  -- Uses idx_ar_invoices_contact_status
```

**DON'T:**
```sql
-- Avoid functions on indexed columns
SELECT * FROM products WHERE LOWER(name) = 'laptop';  -- Index not used

-- Avoid leading wildcards
SELECT * FROM products WHERE name LIKE '%laptop';  -- Index not used
```

### 2. Eager Loading (N+1 Query Prevention)

**Problem (N+1 Query):**
```php
// 1 query for orders + N queries for contacts
$orders = SalesOrder::all();
foreach ($orders as $order) {
    echo $order->contact->name;  // N queries!
}
```

**Solution (Eager Loading):**
```php
// 1 query for orders + 1 query for contacts
$orders = SalesOrder::with('contact')->get();
foreach ($orders as $order) {
    echo $order->contact->name;  // No additional queries
}
```

### 3. Select Only Needed Columns

**DON'T:**
```php
$invoices = ARInvoice::all();  // Selects ALL columns
```

**DO:**
```php
$invoices = ARInvoice::select('id', 'invoice_number', 'total_amount', 'status')->get();
```

### 4. Use Database Aggregations

**DON'T:**
```php
$totalAR = ARInvoice::where('status', 'posted')->get()->sum('remaining_balance');
// Fetches all records to PHP, then sums
```

**DO:**
```php
$totalAR = ARInvoice::where('status', 'posted')->sum('remaining_balance');
// Aggregates in database using SUM()
```

### 5. Batch Processing for Large Datasets

**DON'T:**
```php
$invoices = ARInvoice::all();  // Load 100,000 invoices into memory
```

**DO:**
```php
ARInvoice::chunk(1000, function ($invoices) {
    foreach ($invoices as $invoice) {
        // Process in batches of 1000
    }
});
```

### 6. Query Caching

**Implement Response Caching:**
```php
// Cache catalog endpoints for 1 hour
Cache::remember('products-list', 3600, function () {
    return Product::with('unit', 'category', 'brand')->get();
});
```

**Expected Impact**: 70-90% faster (from ~140ms to ~15ms)

See [Performance Optimization Plan](../performance/PERFORMANCE_OPTIMIZATION_PLAN.md) for caching implementation.

### 7. Use EXPLAIN to Analyze Queries

```sql
EXPLAIN SELECT * FROM ar_invoices
WHERE contact_id = 123 AND status = 'posted';
```

**Look for:**
- `type: ref` (good - using index)
- `type: ALL` (bad - full table scan)
- `Extra: Using where; Using index` (excellent - covering index)

### 8. Index Covering Queries

**Covering Index**: Index contains ALL columns needed for query (no table lookup required)

**Example:**
```sql
-- Query needs: contact_id, status, total_amount
CREATE INDEX idx_ar_invoices_covering (contact_id, status, total_amount);

-- Now this query can be satisfied entirely from index
SELECT total_amount FROM ar_invoices
WHERE contact_id = 123 AND status = 'posted';
```

**Performance Gain**: 30-50% faster (avoids table access)

---

## Appendix: Table Statistics

### Complete Table List (39+ Tables)

| # | Module | Table Name | Primary Entity | Avg Rows (Estimate) |
|---|--------|------------|----------------|---------------------|
| 1 | Product | products | Product | 1,000-10,000 |
| 2 | Product | units | Unit | 20-50 |
| 3 | Product | categories | Category | 50-200 |
| 4 | Product | brands | Brand | 50-500 |
| 5 | Inventory | warehouses | Warehouse | 5-20 |
| 6 | Inventory | warehouse_locations | WarehouseLocation | 100-1,000 |
| 7 | Inventory | stock | Stock | 5,000-50,000 |
| 8 | Inventory | product_batches | ProductBatch | 1,000-10,000 |
| 9 | Inventory | inventory_movements | InventoryMovement | 10,000-100,000+ |
| 10 | Contacts | contacts | Contact | 500-5,000 |
| 11 | Contacts | contact_addresses | ContactAddress | 1,000-10,000 |
| 12 | Contacts | contact_documents | ContactDocument | 500-5,000 |
| 13 | Contacts | contact_persons | ContactPerson | 1,000-10,000 |
| 14 | Sales | sales_orders | SalesOrder | 5,000-50,000 |
| 15 | Sales | sales_order_items | SalesOrderItem | 20,000-200,000 |
| 16 | Purchase | purchase_orders | PurchaseOrder | 2,000-20,000 |
| 17 | Purchase | purchase_order_items | PurchaseOrderItem | 10,000-100,000 |
| 18 | Finance | ar_invoices | ARInvoice | 5,000-50,000 |
| 19 | Finance | ap_invoices | APInvoice | 2,000-20,000 |
| 20 | Finance | payments | Payment | 10,000-100,000 |
| 21 | Finance | payment_applications | PaymentApplication | 15,000-150,000 |
| 22 | Finance | bank_accounts | BankAccount | 5-20 |
| 23 | Finance | payment_methods | PaymentMethod | 5-10 |
| 24 | Accounting | accounts | Account | 90-500 |
| 25 | Accounting | journal_entries | JournalEntry | 20,000-200,000+ |
| 26 | Accounting | journal_lines | JournalLine | 60,000-600,000+ |
| 27 | Accounting | fiscal_periods | FiscalPeriod | 12-60 |
| 28 | Accounting | journals | Journal | 6-10 |
| 29 | Accounting | journal_sequences | JournalSequence | 36-600 |
| 30 | Accounting | exchange_rates | ExchangeRate | 500-5,000 |
| 31 | Accounting | account_balances | AccountBalance | 1,000-30,000 |
| 32 | Accounting | idempotency_keys | IdempotencyKey | 10,000-100,000 |
| 33 | Accounting | critical_action_logs | CriticalActionLog | 50,000-500,000+ |

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-10-28 | Initial comprehensive ERD documentation |

---

**Next Steps:**

1. ✅ Review ERD diagrams in DrawIO
2. ⏳ Create Flow diagrams (Fase 3)
3. ⏳ Create Lifecycle diagrams (Fase 4)
4. ⏳ Complete business rules documentation (Fase 5)

---

**Related Documentation:**

- [C4 Diagrams Guide](../architecture/C4_DIAGRAMS_GUIDE.md)
- [Performance Optimization Plan](../performance/PERFORMANCE_OPTIMIZATION_PLAN.md)
- [Phase 3 Complete Report](../development/PHASE3_COMPLETE_2025_10_27.md)
- [Event-Driven Integration](../development/EVENT_DRIVEN_INTEGRATION_2025_10_27.md)
