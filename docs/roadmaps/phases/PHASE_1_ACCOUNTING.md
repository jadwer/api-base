# 📊 FASE 1: Regeneración Accounting Module
## Estructura Empresarial con GL Completo

**Objetivo:** Regenerar módulo Accounting con estructura empresarial completa

---

## 🎯 **OBJETIVO**

Regenerar completamente el módulo Accounting utilizando la configuración empresarial, implementando General Ledger completo con reglas de negocio empresariales, audit trails, y preparación para integración con Finance.

## 📦 **ENTIDADES A REGENERAR**

### **Estructura Empresarial**
```
ACCOUNTING MODULE:
├── Account (Catálogo de cuentas jerárquico)
├── FiscalPeriod (Períodos fiscales con control de cierre)
├── Journal (Diarios contables con secuenciación)
├── JournalSequence (Secuenciación automática por año fiscal)
├── JournalEntry (Asientos contables con workflow)
├── JournalLine (Líneas de asiento con subledger)
└── ExchangeRate (Tipos de cambio históricos)
```

### **Campos Empresariales Críticos**
- **Audit Trail:** created_by, updated_by, posted_by, approved_by
- **Workflow States:** draft → approved → posted → reversed
- **Secuenciación:** Auto-numbering con fiscal year support
- **Multi-Currency:** Exchange rates con historical tracking
- **Subledger:** Contact references en journal lines

---

## 🛠️ **IMPLEMENTACIÓN**

### **Paso 1: Regeneración con Blueprint Generator**

```bash
# Regenerar usando configuración empresarial
php artisan module:advanced-blueprint Accounting --config="temp/accounting-enterprise-final.json" --force

# Verificar estructura generada
php artisan validate:module-structure Accounting
```

### **Paso 2: Implementar Servicios Empresariales**

#### **2.1 AccountingService** 
```php
class AccountingService
{
    public function postJournalEntry(JournalEntry $entry): bool
    {
        return DB::transaction(function () use ($entry) {
            // Idempotencia empresarial
            if ($entry->status === 'posted') {
                return true;
            }
            
            // Validaciones críticas
            $this->validateBalance($entry);
            $this->validatePeriod($entry);
            $this->validateAccounts($entry);
            
            // Asignar secuencia con lock
            if (!$entry->number) {
                $entry->number = $this->getNextSequence($entry);
            }
            
            // Posting con audit trail
            $entry->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by_id' => auth()->id()
            ]);
            
            return true;
        });
    }
}
```

#### **2.2 SequenceService (PostgreSQL Compliant)**
```php
class SequenceService  
{
    public function getNextSequence(Journal $journal, Carbon $date): string
    {
        return DB::transaction(function () use ($journal, $date) {
            // First-or-create atómico para evitar race conditions
            $sequence = JournalSequence::firstOrCreate(
                [
                    'company_id' => $journal->company_id,
                    'journal_id' => $journal->id,
                    'fiscal_year' => $date->year
                ],
                [
                    'current_number' => 0,
                    'prefix' => $journal->prefix,
                    'created_by_id' => auth()->id()
                ]
            );
            
            // Lock específico y increment atómico
            $sequence = JournalSequence::where('id', $sequence->id)
                ->lockForUpdate()
                ->first();
                
            $sequence->increment('current_number');
            
            // Formato unificado: {prefix}-{YYYY}-{MM}-{#####}
            return sprintf('%s-%04d-%02d-%05d',
                $sequence->prefix,
                $date->year,
                $date->month,
                $sequence->current_number
            );
        });
    }
}
```

### **Paso 3: Implementar Database Constraints PostgreSQL**

```sql
-- Constraints empresariales críticos (PostgreSQL)
ALTER TABLE journal_entries 
ADD CONSTRAINT chk_balanced_entry 
CHECK (total_debit = total_credit);

ALTER TABLE journal_lines
ADD CONSTRAINT chk_debit_or_credit
CHECK ((debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0));

ALTER TABLE accounts
ADD CONSTRAINT chk_valid_account_type
CHECK (account_type IN ('asset', 'liability', 'equity', 'revenue', 'expense', 'contra'));

-- Triggers incrementales para balance GL (PostgreSQL)
CREATE OR REPLACE FUNCTION update_journal_entry_totals_incremental()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE journal_entries 
        SET 
            total_debit = total_debit + NEW.debit,
            total_credit = total_credit + NEW.credit
        WHERE id = NEW.journal_entry_id;
        
    ELSIF TG_OP = 'UPDATE' THEN
        UPDATE journal_entries 
        SET 
            total_debit = total_debit - OLD.debit + NEW.debit,
            total_credit = total_credit - OLD.credit + NEW.credit
        WHERE id = NEW.journal_entry_id;
        
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE journal_entries 
        SET 
            total_debit = total_debit - OLD.debit,
            total_credit = total_credit - OLD.credit
        WHERE id = OLD.journal_entry_id;
    END IF;
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER tr_journal_lines_balance_update
    AFTER INSERT OR UPDATE OR DELETE ON journal_lines
    FOR EACH ROW
    EXECUTE FUNCTION update_journal_entry_totals_incremental();
```

### **Paso 4: Implement Authorizers Empresariales**

```php
class JournalEntryAuthorizer implements Authorizer
{
    public function store(Request $request): bool
    {
        return $request->user()->hasPermissionTo('accounting.journal-entries.store');
    }
    
    public function update(Request $request, Model $entry): bool
    {
        // Solo draft entries pueden editarse
        if ($entry->status !== 'draft') {
            return false;
        }
        
        return $request->user()->hasPermissionTo('accounting.journal-entries.update');
    }
    
    public function post(Request $request, Model $entry): bool
    {
        // Validación de período abierto
        if ($entry->fiscalPeriod->status !== 'open') {
            return false;
        }
        
        return $request->user()->hasPermissionTo('accounting.journal-entries.post');
    }
}
```

### **Paso 5: Seeders Empresariales**

```php
class AccountingDatabaseSeeder extends Seeder
{
    public function run()
    {
        // Catálogo de cuentas mexicano empresarial
        $this->createChartOfAccounts();
        
        // Períodos fiscales con años completos
        $this->createFiscalPeriods();
        
        // Diarios estándar empresariales
        $this->createJournals();
        
        // Exchange rates históricos
        $this->createExchangeRates();
        
        // Permisos granulares
        $this->assignPermissions();
    }
    
    private function createChartOfAccounts()
    {
        // Estructura jerárquica empresarial
        $accounts = [
            ['code' => '1000', 'name' => 'ACTIVO', 'account_type' => 'asset', 'level' => 1, 'is_postable' => false],
            ['code' => '1100', 'name' => 'ACTIVO CIRCULANTE', 'account_type' => 'asset', 'level' => 2, 'parent_code' => '1000'],
            ['code' => '1101', 'name' => 'Caja y Bancos', 'account_type' => 'asset', 'level' => 3, 'parent_code' => '1100'],
            // ... estructura completa
        ];
    }
}
```

---

## ✅ **CRITERIOS DE ACEPTACIÓN**

### **Generación Exitosa**
- [ ] Módulo Accounting regenerado con 7 entidades
- [ ] Todas las migraciones ejecutan sin errores
- [ ] Schemas JSON:API generados correctamente
- [ ] Tests base generados (35+ test files)

### **Servicios Empresariales**
- [ ] AccountingService implementado con posting logic
- [ ] SequenceService con fiscal year support
- [ ] Database constraints aplicados
- [ ] Audit trail funcionando

### **Authorizers y Permisos**
- [ ] Authorizers empresariales implementados
- [ ] Validaciones de estado (draft/posted)
- [ ] Permisos granulares asignados
- [ ] Tests de autorización pasando

### **Seeders y Data**
- [ ] Catálogo de cuentas mexicano cargado
- [ ] Períodos fiscales 2024-2026 creados
- [ ] Diarios estándar configurados
- [ ] Exchange rates iniciales cargados

---

## 🧪 **TESTING EMPRESARIAL**

### **Tests Críticos Adicionales**
```php
class JournalEntryPostingTest extends TestCase
{
    public function test_cannot_post_unbalanced_entry()
    {
        // Test constraint de balance
    }
    
    public function test_sequence_generation_with_concurrent_access()
    {
        // Test de concurrencia en secuencias
    }
    
    public function test_audit_trail_tracking()
    {
        // Test de audit trail completo
    }
    
    public function test_period_closure_validation()
    {
        // Test validación de períodos cerrados
    }
}
```

### **Performance Tests**
```php
class AccountingPerformanceTest extends TestCase
{
    public function test_bulk_journal_entry_posting()
    {
        // Test posting de 1000+ entries
    }
    
    public function test_chart_of_accounts_hierarchy_query()
    {
        // Test queries jerárquicas optimizadas
    }
}
```

---

## 📊 **MÉTRICAS DE ÉXITO**

### **Cobertura de Tests**
- **Target:** 95%+ code coverage
- **Crítico:** 100% en servicios empresariales
- **Tests:** 50+ test methods minimum

### **Performance Benchmarks**
- **Journal Entry Posting:** < 100ms per entry
- **Sequence Generation:** < 50ms with concurrency
- **Account Hierarchy Query:** < 200ms for 500+ accounts

### **Business Rules Compliance**
- **Balanced Entries:** 100% enforcement
- **Period Validation:** 100% compliance
- **Audit Trail:** 100% coverage

---

## 📅 **PLAN DE DESARROLLO**

### **Etapa 1: Regeneración Base**
- Ejecutar blueprint generator
- Validar estructura generada
- Configurar migraciones iniciales

### **Etapa 2: Servicios Empresariales** 
- Implementar AccountingService
- Implementar SequenceService  
- Aplicar database constraints

### **Etapa 3: Authorizers y Permisos**
- Implementar authorizers empresariales
- Configurar permisos granulares
- Tests de autorización

### **Etapa 4: Seeders y Testing**
- Crear seeders empresariales
- Implementar tests críticos
- Performance testing

### **Etapa 5: QA y Validación**
- Code review completo
- Testing integration
- Documentación final

---

## 🔗 **PREPARACIÓN PARA FINANCE**

### **Integration Points**
- [ ] Models preparados para foreign keys de Finance
- [ ] Authorizers con métodos para Finance integration
- [ ] Services con interfaces para AR/AP posting

### **Database Preparación**
- [ ] Índices optimizados para queries cross-module
- [ ] Constraints preparados para Finance relationships
- [ ] Performance tuning para volume empresarial

---

## 🚀 **SIGUIENTE FASE**

Una vez completada la FASE 1, proceder con **FASE 2: Regeneración de Finance** con integración automática a Accounting.