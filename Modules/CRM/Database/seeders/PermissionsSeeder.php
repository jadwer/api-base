<?php

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Database\Seeders\Concerns\BulkPermissions;

class PermissionsSeeder extends Seeder
{
    use BulkPermissions;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔐 Seeding CRM permissions...');

        $permissions = [
            'god',
            'admin',
            'tech',
            'customer',
            // Leads (5 permissions)
            'crm.leads.index',
            'crm.leads.show',
            'crm.leads.store',
            'crm.leads.update',
            'crm.leads.destroy',
            // Opportunities (5 permissions)
            'crm.opportunities.index',
            'crm.opportunities.show',
            'crm.opportunities.store',
            'crm.opportunities.update',
            'crm.opportunities.destroy',
            // Quotes (5 permissions)
            'crm.quotes.index',
            'crm.quotes.show',
            'crm.quotes.store',
            'crm.quotes.update',
            'crm.quotes.destroy',
            // Quote Items (5 permissions)
            'crm.quote-items.index',
            'crm.quote-items.show',
            'crm.quote-items.store',
            'crm.quote-items.update',
            'crm.quote-items.destroy',
            // Activities (5 permissions)
            'crm.activities.index',
            'crm.activities.show',
            'crm.activities.store',
            'crm.activities.update',
            'crm.activities.destroy',
            // Campaigns (5 permissions)
            'crm.campaigns.index',
            'crm.campaigns.show',
            'crm.campaigns.store',
            'crm.campaigns.update',
            'crm.campaigns.destroy',
            // Pipeline Stages (5 permissions)
            'crm.pipeline-stages.index',
            'crm.pipeline-stages.show',
            'crm.pipeline-stages.store',
            'crm.pipeline-stages.update',
            'crm.pipeline-stages.destroy',
        ];

        $this->bulkCreatePermissions($permissions);

        $this->command->info('✅ CRM Permissions seeded successfully!');
    }
}
