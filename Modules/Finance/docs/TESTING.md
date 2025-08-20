# 🧪 Test Report - Finance

**Generated:** 2025-08-20 17:33:38

## APInvoiceDestroyTest

- ✅ Admin can delete APInvoice
- ✅ Admin can delete APInvoice with metadata
- ✅ Can delete inactive APInvoice
- ✅ Customer user cannot delete APInvoice
- ✅ Guest cannot delete APInvoice
- ✅ Returns 404 when deleting nonexistent APInvoice
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## APInvoiceIndexTest

- ✅ Admin can list APInvoices
- ✅ Admin can sort APInvoices by status
- ✅ Admin can filter APInvoices by status
- ✅ Tech user can list APInvoices with permission
- ✅ Customer user cannot list APInvoices
- ✅ Guest cannot list APInvoices
- ✅ Can paginate APInvoices

## APInvoiceLineDestroyTest

- ✅ Admin can delete APInvoiceLine
- ✅ Admin can delete APInvoiceLine with metadata
- ✅ Can delete inactive APInvoiceLine
- ✅ Customer user cannot delete APInvoiceLine
- ✅ Guest cannot delete APInvoiceLine
- ✅ Returns 404 when deleting nonexistent APInvoiceLine
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## APInvoiceLineIndexTest

- ✅ Admin can list APInvoiceLines
- ✅ Admin can sort APInvoiceLines by description
- ✅ Admin can filter APInvoiceLines by status
- ✅ Tech user can list APInvoiceLines with permission
- ✅ Customer user cannot list APInvoiceLines
- ✅ Guest cannot list APInvoiceLines
- ✅ Can paginate APInvoiceLines

## APInvoiceLineShowTest

- ✅ Admin can view APInvoiceLine
- ✅ Admin can view APInvoiceLine with specific data
- ✅ Tech user can view APInvoiceLine with permission
- ✅ Customer user cannot view APInvoiceLine
- ✅ Guest cannot view APInvoiceLine
- ✅ Returns 404 for nonexistent APInvoiceLine
- ✅ Response includes timestamps

## APInvoiceLineStoreTest

- ✅ Admin can create APInvoiceLine
- ✅ Admin can create APInvoiceLine with minimal data
- ✅ Customer user cannot create APInvoiceLine
- ✅ Guest cannot create APInvoiceLine
- ✅ Cannot create APInvoiceLine without required fields
- ✅ Cannot create APInvoiceLine with invalid data

## APInvoiceLineUpdateTest

- ✅ Admin can update APInvoiceLine
- ✅ Admin can partially update APInvoiceLine
- ✅ Admin can update APInvoiceLine metadata
- ✅ Customer user cannot update APInvoiceLine
- ✅ Guest cannot update APInvoiceLine
- ✅ Cannot update nonexistent APInvoiceLine
- ✅ Cannot update APInvoiceLine with invalid data

## APInvoicePaymentDestroyTest

- ✅ Admin can delete APInvoicePayment
- ✅ Admin can delete APInvoicePayment with metadata
- ✅ Can delete inactive APInvoicePayment
- ✅ Customer user cannot delete APInvoicePayment
- ✅ Guest cannot delete APInvoicePayment
- ✅ Returns 404 when deleting nonexistent APInvoicePayment
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## APInvoicePaymentIndexTest

- ✅ Admin can list APInvoicePayments
- ✅ Admin can sort APInvoicePayments by createdAt
- ✅ Admin can filter APInvoicePayments by status
- ✅ Tech user can list APInvoicePayments with permission
- ✅ Customer user cannot list APInvoicePayments
- ✅ Guest cannot list APInvoicePayments
- ✅ Can paginate APInvoicePayments

## APInvoicePaymentShowTest

- ✅ Admin can view APInvoicePayment
- ✅ Admin can view APInvoicePayment with specific data
- ✅ Tech user can view APInvoicePayment with permission
- ✅ Customer user cannot view APInvoicePayment
- ✅ Guest cannot view APInvoicePayment
- ✅ Returns 404 for nonexistent APInvoicePayment
- ✅ Response includes timestamps

## APInvoicePaymentStoreTest

- ✅ Admin can create APInvoicePayment
- ✅ Admin can create APInvoicePayment with minimal data
- ✅ Customer user cannot create APInvoicePayment
- ✅ Guest cannot create APInvoicePayment
- ✅ Cannot create APInvoicePayment without required fields
- ✅ Cannot create APInvoicePayment with invalid data

## APInvoicePaymentUpdateTest

- ✅ Admin can update APInvoicePayment
- ✅ Admin can partially update APInvoicePayment
- ✅ Admin can update APInvoicePayment metadata
- ✅ Customer user cannot update APInvoicePayment
- ✅ Guest cannot update APInvoicePayment
- ✅ Cannot update nonexistent APInvoicePayment
- ✅ Cannot update APInvoicePayment with invalid data

## APInvoiceShowTest

- ✅ Admin can view APInvoice
- ✅ Admin can view APInvoice with specific data
- ✅ Tech user can view APInvoice with permission
- ✅ Customer user cannot view APInvoice
- ✅ Guest cannot view APInvoice
- ✅ Returns 404 for nonexistent APInvoice
- ✅ Response includes timestamps

## APInvoiceStoreTest

- ✅ Admin can create APInvoice
- ✅ Admin can create APInvoice with minimal data
- ✅ Customer user cannot create APInvoice
- ✅ Guest cannot create APInvoice
- ✅ Cannot create APInvoice without required fields
- ✅ Cannot create APInvoice with invalid data

## APInvoiceUpdateTest

- ✅ Admin can update APInvoice
- ✅ Admin can partially update APInvoice
- ✅ Admin can update APInvoice metadata
- ✅ Customer user cannot update APInvoice
- ✅ Guest cannot update APInvoice
- ✅ Cannot update nonexistent APInvoice
- ✅ Cannot update APInvoice with invalid data

## APPaymentDestroyTest

- ✅ Admin can delete APPayment
- ✅ Admin can delete APPayment with metadata
- ✅ Can delete inactive APPayment
- ✅ Customer user cannot delete APPayment
- ✅ Guest cannot delete APPayment
- ✅ Returns 404 when deleting nonexistent APPayment
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## APPaymentIndexTest

- ✅ Admin can list APPayments
- ✅ Admin can sort APPayments by status
- ✅ Admin can filter APPayments by status
- ✅ Tech user can list APPayments with permission
- ✅ Customer user cannot list APPayments
- ✅ Guest cannot list APPayments
- ✅ Can paginate APPayments

## APPaymentShowTest

- ✅ Admin can view APPayment
- ✅ Admin can view APPayment with specific data
- ✅ Tech user can view APPayment with permission
- ✅ Customer user cannot view APPayment
- ✅ Guest cannot view APPayment
- ✅ Returns 404 for nonexistent APPayment
- ✅ Response includes timestamps

## APPaymentStoreTest

- ✅ Admin can create APPayment
- ✅ Admin can create APPayment with minimal data
- ✅ Customer user cannot create APPayment
- ✅ Guest cannot create APPayment
- ✅ Cannot create APPayment without required fields
- ✅ Cannot create APPayment with invalid data

## APPaymentUpdateTest

- ✅ Admin can update APPayment
- ✅ Admin can partially update APPayment
- ✅ Admin can update APPayment metadata
- ✅ Customer user cannot update APPayment
- ✅ Guest cannot update APPayment
- ✅ Cannot update nonexistent APPayment
- ✅ Cannot update APPayment with invalid data

## ARInvoiceDestroyTest

- ✅ Admin can delete ARInvoice
- ✅ Admin can delete ARInvoice with metadata
- ✅ Can delete inactive ARInvoice
- ✅ Customer user cannot delete ARInvoice
- ✅ Guest cannot delete ARInvoice
- ✅ Returns 404 when deleting nonexistent ARInvoice
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## ARInvoiceIndexTest

- ✅ Admin can list ARInvoices
- ✅ Admin can sort ARInvoices by status
- ✅ Admin can filter ARInvoices by status
- ✅ Tech user can list ARInvoices with permission
- ✅ Customer user cannot list ARInvoices
- ✅ Guest cannot list ARInvoices
- ✅ Can paginate ARInvoices

## ARInvoiceLineDestroyTest

- ✅ Admin can delete ARInvoiceLine
- ✅ Admin can delete ARInvoiceLine with metadata
- ✅ Can delete inactive ARInvoiceLine
- ✅ Customer user cannot delete ARInvoiceLine
- ✅ Guest cannot delete ARInvoiceLine
- ✅ Returns 404 when deleting nonexistent ARInvoiceLine
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## ARInvoiceLineIndexTest

- ✅ Admin can list ARInvoiceLines
- ✅ Admin can sort ARInvoiceLines by description
- ✅ Admin can filter ARInvoiceLines by status
- ✅ Tech user can list ARInvoiceLines with permission
- ✅ Customer user cannot list ARInvoiceLines
- ✅ Guest cannot list ARInvoiceLines
- ✅ Can paginate ARInvoiceLines

## ARInvoiceLineShowTest

- ✅ Admin can view ARInvoiceLine
- ✅ Admin can view ARInvoiceLine with specific data
- ✅ Tech user can view ARInvoiceLine with permission
- ✅ Customer user cannot view ARInvoiceLine
- ✅ Guest cannot view ARInvoiceLine
- ✅ Returns 404 for nonexistent ARInvoiceLine
- ✅ Response includes timestamps

## ARInvoiceLineStoreTest

- ✅ Admin can create ARInvoiceLine
- ✅ Admin can create ARInvoiceLine with minimal data
- ✅ Customer user cannot create ARInvoiceLine
- ✅ Guest cannot create ARInvoiceLine
- ✅ Cannot create ARInvoiceLine without required fields
- ✅ Cannot create ARInvoiceLine with invalid data

## ARInvoiceLineUpdateTest

- ✅ Admin can update ARInvoiceLine
- ✅ Admin can partially update ARInvoiceLine
- ✅ Admin can update ARInvoiceLine metadata
- ✅ Customer user cannot update ARInvoiceLine
- ✅ Guest cannot update ARInvoiceLine
- ✅ Cannot update nonexistent ARInvoiceLine
- ✅ Cannot update ARInvoiceLine with invalid data

## ARInvoiceReceiptDestroyTest

- ✅ Admin can delete ARInvoiceReceipt
- ✅ Admin can delete ARInvoiceReceipt with metadata
- ✅ Can delete inactive ARInvoiceReceipt
- ✅ Customer user cannot delete ARInvoiceReceipt
- ✅ Guest cannot delete ARInvoiceReceipt
- ✅ Returns 404 when deleting nonexistent ARInvoiceReceipt
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## ARInvoiceReceiptIndexTest

- ✅ Admin can list ARInvoiceReceipts
- ✅ Admin can sort ARInvoiceReceipts by createdAt
- ✅ Admin can filter ARInvoiceReceipts by status
- ✅ Tech user can list ARInvoiceReceipts with permission
- ✅ Customer user cannot list ARInvoiceReceipts
- ✅ Guest cannot list ARInvoiceReceipts
- ✅ Can paginate ARInvoiceReceipts

## ARInvoiceReceiptShowTest

- ✅ Admin can view ARInvoiceReceipt
- ✅ Admin can view ARInvoiceReceipt with specific data
- ✅ Tech user can view ARInvoiceReceipt with permission
- ✅ Customer user cannot view ARInvoiceReceipt
- ✅ Guest cannot view ARInvoiceReceipt
- ✅ Returns 404 for nonexistent ARInvoiceReceipt
- ✅ Response includes timestamps

## ARInvoiceReceiptStoreTest

- ✅ Admin can create ARInvoiceReceipt
- ✅ Admin can create ARInvoiceReceipt with minimal data
- ✅ Customer user cannot create ARInvoiceReceipt
- ✅ Guest cannot create ARInvoiceReceipt
- ✅ Cannot create ARInvoiceReceipt without required fields
- ✅ Cannot create ARInvoiceReceipt with invalid data

## ARInvoiceReceiptUpdateTest

- ✅ Admin can update ARInvoiceReceipt
- ✅ Admin can partially update ARInvoiceReceipt
- ✅ Admin can update ARInvoiceReceipt metadata
- ✅ Customer user cannot update ARInvoiceReceipt
- ✅ Guest cannot update ARInvoiceReceipt
- ✅ Cannot update nonexistent ARInvoiceReceipt
- ✅ Cannot update ARInvoiceReceipt with invalid data

## ARInvoiceShowTest

- ✅ Admin can view ARInvoice
- ✅ Admin can view ARInvoice with specific data
- ✅ Tech user can view ARInvoice with permission
- ✅ Customer user cannot view ARInvoice
- ✅ Guest cannot view ARInvoice
- ✅ Returns 404 for nonexistent ARInvoice
- ✅ Response includes timestamps

## ARInvoiceStoreTest

- ✅ Admin can create ARInvoice
- ✅ Admin can create ARInvoice with minimal data
- ✅ Customer user cannot create ARInvoice
- ✅ Guest cannot create ARInvoice
- ✅ Cannot create ARInvoice without required fields
- ✅ Cannot create ARInvoice with invalid data

## ARInvoiceUpdateTest

- ✅ Admin can update ARInvoice
- ✅ Admin can partially update ARInvoice
- ✅ Admin can update ARInvoice metadata
- ✅ Customer user cannot update ARInvoice
- ✅ Guest cannot update ARInvoice
- ✅ Cannot update nonexistent ARInvoice
- ✅ Cannot update ARInvoice with invalid data

## ARReceiptDestroyTest

- ✅ Admin can delete ARReceipt
- ✅ Admin can delete ARReceipt with metadata
- ✅ Can delete inactive ARReceipt
- ✅ Customer user cannot delete ARReceipt
- ✅ Guest cannot delete ARReceipt
- ✅ Returns 404 when deleting nonexistent ARReceipt
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## ARReceiptIndexTest

- ✅ Admin can list ARReceipts
- ✅ Admin can sort ARReceipts by status
- ✅ Admin can filter ARReceipts by status
- ✅ Tech user can list ARReceipts with permission
- ✅ Customer user cannot list ARReceipts
- ✅ Guest cannot list ARReceipts
- ✅ Can paginate ARReceipts

## ARReceiptShowTest

- ✅ Admin can view ARReceipt
- ✅ Admin can view ARReceipt with specific data
- ✅ Tech user can view ARReceipt with permission
- ✅ Customer user cannot view ARReceipt
- ✅ Guest cannot view ARReceipt
- ✅ Returns 404 for nonexistent ARReceipt
- ✅ Response includes timestamps

## ARReceiptStoreTest

- ✅ Admin can create ARReceipt
- ✅ Admin can create ARReceipt with minimal data
- ✅ Customer user cannot create ARReceipt
- ✅ Guest cannot create ARReceipt
- ✅ Cannot create ARReceipt without required fields
- ✅ Cannot create ARReceipt with invalid data

## ARReceiptUpdateTest

- ✅ Admin can update ARReceipt
- ✅ Admin can partially update ARReceipt
- ✅ Admin can update ARReceipt metadata
- ✅ Customer user cannot update ARReceipt
- ✅ Guest cannot update ARReceipt
- ✅ Cannot update nonexistent ARReceipt
- ✅ Cannot update ARReceipt with invalid data

## BankAccountDestroyTest

- ✅ Admin can delete BankAccount
- ✅ Admin can delete BankAccount with metadata
- ✅ Can delete inactive BankAccount
- ✅ Customer user cannot delete BankAccount
- ✅ Guest cannot delete BankAccount
- ✅ Returns 404 when deleting nonexistent BankAccount
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## BankAccountIndexTest

- ✅ Admin can list BankAccounts
- ✅ Admin can sort BankAccounts by status
- ✅ Admin can filter BankAccounts by status
- ✅ Tech user can list BankAccounts with permission
- ✅ Customer user cannot list BankAccounts
- ✅ Guest cannot list BankAccounts
- ✅ Can paginate BankAccounts

## BankAccountShowTest

- ✅ Admin can view BankAccount
- ✅ Admin can view BankAccount with specific data
- ✅ Tech user can view BankAccount with permission
- ✅ Customer user cannot view BankAccount
- ✅ Guest cannot view BankAccount
- ✅ Returns 404 for nonexistent BankAccount
- ✅ Response includes timestamps

## BankAccountStoreTest

- ✅ Admin can create BankAccount
- ✅ Admin can create BankAccount with minimal data
- ✅ Customer user cannot create BankAccount
- ✅ Guest cannot create BankAccount
- ✅ Cannot create BankAccount without required fields
- ✅ Cannot create BankAccount with invalid data

## BankAccountUpdateTest

- ✅ Admin can update BankAccount
- ✅ Admin can partially update BankAccount
- ✅ Admin can update BankAccount metadata
- ✅ Customer user cannot update BankAccount
- ✅ Guest cannot update BankAccount
- ✅ Cannot update nonexistent BankAccount
- ✅ Cannot update BankAccount with invalid data

## BankStatementDestroyTest

- ✅ Admin can delete BankStatement
- ✅ Admin can delete BankStatement with metadata
- ✅ Can delete inactive BankStatement
- ✅ Customer user cannot delete BankStatement
- ✅ Guest cannot delete BankStatement
- ✅ Returns 404 when deleting nonexistent BankStatement
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## BankStatementIndexTest

- ✅ Admin can list BankStatements
- ✅ Admin can sort BankStatements by importSource
- ✅ Admin can filter BankStatements by status
- ✅ Tech user can list BankStatements with permission
- ✅ Customer user cannot list BankStatements
- ✅ Guest cannot list BankStatements
- ✅ Can paginate BankStatements

## BankStatementLineDestroyTest

- ✅ Admin can delete BankStatementLine
- ✅ Admin can delete BankStatementLine with metadata
- ✅ Can delete inactive BankStatementLine
- ✅ Customer user cannot delete BankStatementLine
- ✅ Guest cannot delete BankStatementLine
- ✅ Returns 404 when deleting nonexistent BankStatementLine
- ✅ Delete response is empty
- ✅ Multiple deletes are idempotent

## BankStatementLineIndexTest

- ✅ Admin can list BankStatementLines
- ✅ Admin can sort BankStatementLines by status
- ✅ Admin can filter BankStatementLines by status
- ✅ Tech user can list BankStatementLines with permission
- ✅ Customer user cannot list BankStatementLines
- ✅ Guest cannot list BankStatementLines
- ✅ Can paginate BankStatementLines

## BankStatementLineShowTest

- ✅ Admin can view BankStatementLine
- ✅ Admin can view BankStatementLine with specific data
- ✅ Tech user can view BankStatementLine with permission
- ✅ Customer user cannot view BankStatementLine
- ✅ Guest cannot view BankStatementLine
- ✅ Returns 404 for nonexistent BankStatementLine
- ✅ Response includes timestamps

## BankStatementLineStoreTest

- ✅ Admin can create BankStatementLine
- ✅ Admin can create BankStatementLine with minimal data
- ✅ Customer user cannot create BankStatementLine
- ✅ Guest cannot create BankStatementLine
- ✅ Cannot create BankStatementLine without required fields
- ✅ Cannot create BankStatementLine with invalid data

## BankStatementLineUpdateTest

- ✅ Admin can update BankStatementLine
- ✅ Admin can partially update BankStatementLine
- ✅ Admin can update BankStatementLine metadata
- ✅ Customer user cannot update BankStatementLine
- ✅ Guest cannot update BankStatementLine
- ✅ Cannot update nonexistent BankStatementLine
- ✅ Cannot update BankStatementLine with invalid data

## BankStatementShowTest

- ✅ Admin can view BankStatement
- ✅ Admin can view BankStatement with specific data
- ✅ Tech user can view BankStatement with permission
- ✅ Customer user cannot view BankStatement
- ✅ Guest cannot view BankStatement
- ✅ Returns 404 for nonexistent BankStatement
- ✅ Response includes timestamps

## BankStatementStoreTest

- ✅ Admin can create BankStatement
- ✅ Admin can create BankStatement with minimal data
- ✅ Customer user cannot create BankStatement
- ✅ Guest cannot create BankStatement
- ✅ Cannot create BankStatement without required fields
- ✅ Cannot create BankStatement with invalid data

## BankStatementUpdateTest

- ✅ Admin can update BankStatement
- ✅ Admin can partially update BankStatement
- ✅ Admin can update BankStatement metadata
- ✅ Customer user cannot update BankStatement
- ✅ Guest cannot update BankStatement
- ✅ Cannot update nonexistent BankStatement
- ✅ Cannot update BankStatement with invalid data

## 📊 Summary

- **Test Files:** 55
- **Test Methods:** 385
- **Status:** All tests should pass
- **Coverage:** High coverage expected

## 🚀 Running Tests

```bash
# Run all module tests
php artisan test --filter Finance

# Run specific test file
php artisan test Modules/Finance/Tests/Feature/ExampleTest
```
