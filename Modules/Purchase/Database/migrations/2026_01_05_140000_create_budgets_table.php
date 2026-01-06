<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PU-M003: Budget Control
 *
 * Budgets can be defined by:
 * - Period (monthly, quarterly, annual)
 * - Department/Cost Center
 * - Category (products, services, equipment)
 * - Supplier (optional)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();

            // Budget identification
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // Budget type: department, category, project, supplier
            $table->enum('budget_type', ['department', 'category', 'project', 'supplier', 'general'])
                  ->default('general');

            // Optional references
            $table->string('department_code')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('project_code')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');

            // Period
            $table->enum('period_type', ['monthly', 'quarterly', 'annual', 'custom'])
                  ->default('monthly');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('fiscal_year')->nullable();

            // Amounts
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('committed_amount', 15, 2)->default(0)
                  ->comment('POs approved but not yet invoiced');
            $table->decimal('spent_amount', 15, 2)->default(0)
                  ->comment('Actually invoiced/paid');
            $table->decimal('available_amount', 15, 2)->storedAs('budgeted_amount - committed_amount - spent_amount');

            // Control settings
            $table->decimal('warning_threshold', 5, 2)->default(80.00)
                  ->comment('Percentage at which to warn (e.g., 80%)');
            $table->decimal('critical_threshold', 5, 2)->default(95.00)
                  ->comment('Percentage at which to require approval');
            $table->boolean('hard_limit')->default(false)
                  ->comment('If true, block purchases over budget');
            $table->boolean('allow_overcommit')->default(false)
                  ->comment('Allow commitments even if over budget');

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['budget_type', 'is_active']);
            $table->index(['start_date', 'end_date']);
            $table->index('department_code');
            $table->index('fiscal_year');
        });

        // Budget allocations - track which POs are committed against which budgets
        Schema::create('budget_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');

            $table->decimal('allocated_amount', 15, 2);
            $table->enum('status', ['committed', 'spent', 'released', 'cancelled'])
                  ->default('committed');

            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['budget_id', 'status']);
            $table->unique(['budget_id', 'purchase_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_allocations');
        Schema::dropIfExists('budgets');
    }
};
