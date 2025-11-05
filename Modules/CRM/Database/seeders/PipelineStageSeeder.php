<?php

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CRM\Models\PipelineStage;

class PipelineStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Seeding CRM Pipeline Stages...');

        // Lead Pipeline Stages
        $leadStages = [
            [
                'name' => 'New',
                'type' => 'lead',
                'probability' => 10,
                'sort_order' => 1,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Contacted',
                'type' => 'lead',
                'probability' => 30,
                'sort_order' => 2,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Qualified',
                'type' => 'lead',
                'probability' => 50,
                'sort_order' => 3,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Unqualified',
                'type' => 'lead',
                'probability' => 0,
                'sort_order' => 4,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => true,
            ],
            [
                'name' => 'Converted',
                'type' => 'lead',
                'probability' => 100,
                'sort_order' => 5,
                'is_active' => true,
                'is_closed_won' => true,
                'is_closed_lost' => false,
            ],
        ];

        // Opportunity Pipeline Stages
        $opportunityStages = [
            [
                'name' => 'Prospecting',
                'type' => 'opportunity',
                'probability' => 10,
                'sort_order' => 1,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Qualification',
                'type' => 'opportunity',
                'probability' => 25,
                'sort_order' => 2,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Proposal',
                'type' => 'opportunity',
                'probability' => 50,
                'sort_order' => 3,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Negotiation',
                'type' => 'opportunity',
                'probability' => 75,
                'sort_order' => 4,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Closed Won',
                'type' => 'opportunity',
                'probability' => 100,
                'sort_order' => 5,
                'is_active' => true,
                'is_closed_won' => true,
                'is_closed_lost' => false,
            ],
            [
                'name' => 'Closed Lost',
                'type' => 'opportunity',
                'probability' => 0,
                'sort_order' => 6,
                'is_active' => true,
                'is_closed_won' => false,
                'is_closed_lost' => true,
            ],
        ];

        // Create lead stages
        foreach ($leadStages as $stage) {
            PipelineStage::firstOrCreate(
                ['name' => $stage['name'], 'type' => $stage['type']],
                $stage
            );
        }

        // Create opportunity stages
        foreach ($opportunityStages as $stage) {
            PipelineStage::firstOrCreate(
                ['name' => $stage['name'], 'type' => $stage['type']],
                $stage
            );
        }

        $this->command->info('✅ CRM Pipeline Stages seeded successfully!');
        $this->command->info('   - 5 Lead stages created');
        $this->command->info('   - 6 Opportunity stages created');
    }
}
