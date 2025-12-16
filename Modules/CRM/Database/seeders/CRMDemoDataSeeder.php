<?php

namespace Modules\CRM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CRM\Models\PipelineStage;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Campaign;
use Modules\CRM\Models\Activity;
use Modules\CRM\Models\Opportunity;
use Modules\User\Models\User;
use Carbon\Carbon;

class CRMDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating CRM demo data...');

        // Get admin user for assignments
        $admin = User::where('email', 'admin@example.com')->first();
        if (!$admin) {
            $admin = User::first();
        }

        // Create campaigns first
        $campaigns = $this->createCampaigns($admin);
        $this->command->info("Created {$campaigns->count()} campaigns");

        // Create leads
        $leads = $this->createLeads($admin, $campaigns);
        $this->command->info("Created {$leads->count()} leads");

        // Create activities
        $activityCount = $this->createActivities($admin, $leads, $campaigns);
        $this->command->info("Created {$activityCount} activities");

        // Create opportunities (if model exists)
        if (class_exists(Opportunity::class)) {
            $opportunityCount = $this->createOpportunities($admin, $leads);
            $this->command->info("Created {$opportunityCount} opportunities");
        }
    }

    private function createCampaigns($user)
    {
        $campaigns = collect();

        $campaignData = [
            [
                'name' => 'Campaña de Email Marketing Q4 2024',
                'type' => 'email',
                'status' => 'active',
                'budget' => 25000,
                'actual_cost' => 18500,
                'expected_revenue' => 150000,
                'actual_revenue' => 95000,
                'target_audience' => 'PYMES del sector tecnología',
                'description' => 'Campaña de email marketing dirigida a pequeñas y medianas empresas del sector tecnológico.',
            ],
            [
                'name' => 'Webinar: Transformación Digital 2025',
                'type' => 'webinar',
                'status' => 'planning',
                'budget' => 15000,
                'actual_cost' => null,
                'expected_revenue' => 80000,
                'actual_revenue' => null,
                'target_audience' => 'Directores de TI y CTOs',
                'description' => 'Webinar sobre las tendencias de transformación digital para el próximo año.',
            ],
            [
                'name' => 'Feria Tecnológica CDMX',
                'type' => 'event',
                'status' => 'completed',
                'budget' => 50000,
                'actual_cost' => 48000,
                'expected_revenue' => 200000,
                'actual_revenue' => 185000,
                'target_audience' => 'Empresas del sector industrial',
                'description' => 'Participación en la feria tecnológica más importante de la Ciudad de México.',
            ],
            [
                'name' => 'LinkedIn Ads - Software ERP',
                'type' => 'social_media',
                'status' => 'active',
                'budget' => 35000,
                'actual_cost' => 22000,
                'expected_revenue' => 120000,
                'actual_revenue' => 45000,
                'target_audience' => 'Gerentes de Operaciones y Finanzas',
                'description' => 'Campaña de anuncios en LinkedIn para promoción de software ERP.',
            ],
            [
                'name' => 'Telemarketing Clientes Inactivos',
                'type' => 'telemarketing',
                'status' => 'paused',
                'budget' => 10000,
                'actual_cost' => 4500,
                'expected_revenue' => 60000,
                'actual_revenue' => 12000,
                'target_audience' => 'Clientes con más de 6 meses sin compra',
                'description' => 'Campaña de reactivación de clientes mediante llamadas telefónicas.',
            ],
        ];

        foreach ($campaignData as $data) {
            $startDate = Carbon::now()->subMonths(rand(1, 3));
            $endDate = $data['status'] === 'completed' ? Carbon::now()->subWeeks(rand(1, 4)) : Carbon::now()->addMonths(rand(1, 3));

            $campaigns->push(Campaign::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'status' => $data['status'],
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $data['status'] === 'planning' ? null : $endDate,
                'budget' => $data['budget'],
                'actual_cost' => $data['actual_cost'],
                'expected_revenue' => $data['expected_revenue'],
                'actual_revenue' => $data['actual_revenue'],
                'target_audience' => $data['target_audience'],
                'description' => $data['description'],
                'metadata' => [
                    'platform' => $this->getPlatformByType($data['type']),
                    'goal' => 'Lead Generation',
                    'target_leads' => rand(50, 500),
                ],
            ]));
        }

        return $campaigns;
    }

    private function createLeads($user, $campaigns)
    {
        $leads = collect();

        $leadData = [
            [
                'title' => 'Implementación ERP para Grupo Industrial MX',
                'company_name' => 'Grupo Industrial MX',
                'contact_person' => 'Roberto Fernández',
                'source' => 'website',
                'status' => 'qualified',
                'rating' => 'hot',
                'estimated_value' => 250000,
            ],
            [
                'title' => 'CRM para Distribuidora del Norte',
                'company_name' => 'Distribuidora del Norte S.A.',
                'contact_person' => 'Ana María López',
                'source' => 'referral',
                'status' => 'new',
                'rating' => 'warm',
                'estimated_value' => 85000,
            ],
            [
                'title' => 'Sistema de Inventarios - Tech Solutions',
                'company_name' => 'Tech Solutions de México',
                'contact_person' => 'Carlos Mendoza',
                'source' => 'trade show',
                'status' => 'contacted',
                'rating' => 'warm',
                'estimated_value' => 120000,
            ],
            [
                'title' => 'Facturación Electrónica - Comercializadora ABC',
                'company_name' => 'Comercializadora ABC',
                'contact_person' => 'Patricia Sánchez',
                'source' => 'cold call',
                'status' => 'qualified',
                'rating' => 'hot',
                'estimated_value' => 45000,
            ],
            [
                'title' => 'Sistema Contable - Consultores Asociados',
                'company_name' => 'Consultores Asociados SC',
                'contact_person' => 'Miguel Ángel Torres',
                'source' => 'email campaign',
                'status' => 'new',
                'rating' => 'cold',
                'estimated_value' => 35000,
            ],
            [
                'title' => 'ERP Completo - Manufacturas del Pacífico',
                'company_name' => 'Manufacturas del Pacífico',
                'contact_person' => 'Laura Hernández',
                'source' => 'website',
                'status' => 'converted',
                'rating' => 'hot',
                'estimated_value' => 380000,
            ],
            [
                'title' => 'Módulo de Ventas - Servicios Integrales',
                'company_name' => 'Servicios Integrales MX',
                'contact_person' => 'Fernando Ruiz',
                'source' => 'social media',
                'status' => 'contacted',
                'rating' => 'warm',
                'estimated_value' => 65000,
            ],
            [
                'title' => 'Sistema HR - Corporativo Central',
                'company_name' => 'Corporativo Central SA de CV',
                'contact_person' => 'Elena Vázquez',
                'source' => 'referral',
                'status' => 'qualified',
                'rating' => 'hot',
                'estimated_value' => 95000,
            ],
        ];

        $counter = 0;
        foreach ($leadData as $data) {
            $campaign = $counter < $campaigns->count() ? $campaigns[$counter] : $campaigns->random();

            $leads->push(Lead::create([
                'title' => $data['title'],
                'source' => $data['source'],
                'status' => $data['status'],
                'rating' => $data['rating'],
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_person' => $data['contact_person'],
                'email' => strtolower(str_replace(' ', '.', $data['contact_person'])) . '@' . strtolower(str_replace(' ', '', explode(' ', $data['company_name'])[0])) . '.com',
                'phone' => '55' . rand(10000000, 99999999),
                'estimated_value' => $data['estimated_value'],
                'estimated_close_date' => Carbon::now()->addMonths(rand(1, 6)),
                'converted_at' => $data['status'] === 'converted' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'notes' => 'Lead generado desde ' . $data['source'] . '. Seguimiento requerido.',
                'metadata' => [
                    'industry' => $this->getRandomIndustry(),
                    'company_size' => $this->getRandomCompanySize(),
                    'budget_range' => $this->getBudgetRange($data['estimated_value']),
                ],
            ]));

            $counter++;
        }

        return $leads;
    }

    private function createActivities($user, $leads, $campaigns)
    {
        $count = 0;

        // Create activities for each lead
        foreach ($leads as $lead) {
            // Create 2-5 activities per lead
            $activityCount = rand(2, 5);

            for ($i = 0; $i < $activityCount; $i++) {
                $type = collect(['call', 'email', 'meeting', 'note', 'task'])->random();
                $status = collect(['pending', 'scheduled', 'completed', 'completed'])->random();

                Activity::create([
                    'activity_type' => $type,
                    'subject' => $this->getActivitySubject($type, $lead->company_name),
                    'description' => $this->getActivityDescription($type),
                    'activity_date' => $status === 'completed'
                        ? Carbon::now()->subDays(rand(1, 30))
                        : Carbon::now()->addDays(rand(1, 14)),
                    'duration' => $this->getDurationByType($type),
                    'outcome' => $status === 'completed' ? $this->getRandomOutcome() : null,
                    'status' => $status,
                    'user_id' => $user->id,
                    'lead_id' => $lead->id,
                    'contact_id' => null,
                    'campaign_id' => null,
                    'metadata' => [
                        'priority' => collect(['low', 'medium', 'high'])->random(),
                        'tags' => $this->getRandomTags(),
                    ],
                ]);
                $count++;
            }
        }

        // Create some campaign activities
        foreach ($campaigns as $campaign) {
            Activity::create([
                'activity_type' => 'task',
                'subject' => "Revisar métricas de {$campaign->name}",
                'description' => 'Revisión semanal de métricas y KPIs de la campaña.',
                'activity_date' => Carbon::now()->addDays(rand(1, 7)),
                'duration' => null,
                'outcome' => null,
                'status' => 'scheduled',
                'user_id' => $user->id,
                'lead_id' => null,
                'contact_id' => null,
                'campaign_id' => $campaign->id,
                'metadata' => [
                    'priority' => 'medium',
                    'tags' => ['campaign', 'metrics'],
                ],
            ]);
            $count++;
        }

        return $count;
    }

    private function createOpportunities($user, $leads)
    {
        $count = 0;

        // Create opportunities for qualified and converted leads
        $qualifiedLeads = $leads->filter(fn($lead) => in_array($lead->status, ['qualified', 'converted']));

        foreach ($qualifiedLeads as $lead) {
            // Status can only be: open, won, lost, abandoned
            $status = $lead->status === 'converted' ? 'won' : 'open';
            $probability = match($status) {
                'won' => 100,
                default => rand(30, 85),
            };

            Opportunity::create([
                'name' => "Oportunidad: {$lead->title}",
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'stage' => $this->getStageByStatus($status),
                'status' => $status,
                'amount' => $lead->estimated_value,
                'probability' => $probability,
                'close_date' => $status === 'won'
                    ? Carbon::now()->subDays(rand(1, 30))
                    : Carbon::now()->addMonths(rand(1, 4)),
                'forecast_category' => $this->getForecastCategory($probability),
                'description' => "Oportunidad generada a partir del lead: {$lead->title}",
                'won_at' => $status === 'won' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'lost_at' => null,
                'loss_reason' => null,
                'metadata' => [
                    'source_lead' => $lead->id,
                    'competitors' => $this->getRandomCompetitors(),
                ],
            ]);
            $count++;
        }

        return $count;
    }

    // Helper methods
    private function getPlatformByType(string $type): string
    {
        return match ($type) {
            'email' => 'Email',
            'social_media' => collect(['Facebook', 'LinkedIn', 'Instagram'])->random(),
            'event' => 'Events',
            'webinar' => collect(['Zoom', 'Google Meet'])->random(),
            'direct_mail' => 'Direct Mail',
            'telemarketing' => 'Phone',
            default => 'Other',
        };
    }

    private function getRandomIndustry(): string
    {
        return collect(['Technology', 'Manufacturing', 'Retail', 'Services', 'Healthcare', 'Finance'])->random();
    }

    private function getRandomCompanySize(): string
    {
        return collect(['1-10', '11-50', '51-200', '201-500', '500+'])->random();
    }

    private function getBudgetRange(float $value): string
    {
        if ($value < 50000) return '< $50k';
        if ($value < 100000) return '$50k-$100k';
        if ($value < 250000) return '$100k-$250k';
        return '$250k+';
    }

    private function getActivitySubject(string $type, string $company): string
    {
        return match ($type) {
            'call' => "Llamada de seguimiento - {$company}",
            'email' => "Email de propuesta - {$company}",
            'meeting' => "Reunión de demostración - {$company}",
            'note' => "Notas de calificación - {$company}",
            'task' => "Preparar cotización - {$company}",
        };
    }

    private function getActivityDescription(string $type): string
    {
        return match ($type) {
            'call' => 'Llamada para dar seguimiento al interés mostrado por el prospecto.',
            'email' => 'Envío de información detallada sobre nuestra solución.',
            'meeting' => 'Reunión programada para presentar demostración del producto.',
            'note' => 'Documentación de requisitos y expectativas del cliente.',
            'task' => 'Preparación de propuesta comercial personalizada.',
        };
    }

    private function getDurationByType(string $type): ?int
    {
        return match ($type) {
            'call' => rand(10, 45),
            'meeting' => rand(30, 120),
            default => null,
        };
    }

    private function getRandomOutcome(): string
    {
        return collect([
            'successful',
            'callback requested',
            'follow up scheduled',
            'information sent',
            'proposal requested',
        ])->random();
    }

    private function getRandomTags(): array
    {
        return collect(['follow-up', 'demo', 'pricing', 'proposal', 'negotiation', 'closing'])
            ->random(rand(1, 3))
            ->toArray();
    }

    private function getStageByStatus(string $status): string
    {
        return match ($status) {
            'won' => 'closed_won',
            'lost' => 'closed_lost',
            default => collect(['qualification', 'proposal', 'negotiation'])->random(),
        };
    }

    private function getForecastCategory(int $probability): string
    {
        if ($probability >= 90) return 'closed';
        if ($probability >= 70) return 'commit';
        if ($probability >= 50) return 'best_case';
        return 'pipeline';
    }

    private function getRandomCompetitors(): array
    {
        return collect(['SAP', 'Oracle', 'Microsoft Dynamics', 'Salesforce', 'HubSpot', 'Odoo'])
            ->random(rand(1, 3))
            ->toArray();
    }
}
