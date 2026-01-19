<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Sales\Models\FolioSequence;

class FolioSequenceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_generates_sequential_folios(): void
    {
        FolioSequence::create([
            'document_type' => 'test',
            'prefix' => 'TEST',
            'include_year' => true,
            'year_format' => 'y',
            'separator' => '',
            'padding' => 6,
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $folio1 = FolioSequence::getNextFolio('test');
        $folio2 = FolioSequence::getNextFolio('test');
        $folio3 = FolioSequence::getNextFolio('test');

        $year = now()->format('y');
        $this->assertEquals("TEST{$year}000001", $folio1);
        $this->assertEquals("TEST{$year}000002", $folio2);
        $this->assertEquals("TEST{$year}000003", $folio3);
    }

    /** @test */
    public function it_respects_custom_padding(): void
    {
        FolioSequence::create([
            'document_type' => 'padded',
            'prefix' => 'P',
            'include_year' => false,
            'separator' => '-',
            'padding' => 4,
            'current_sequence' => 99,
            'is_active' => true,
        ]);

        $folio = FolioSequence::getNextFolio('padded');

        $this->assertEquals('P-0100', $folio);
    }

    /** @test */
    public function it_uses_separator_correctly(): void
    {
        FolioSequence::create([
            'document_type' => 'separated',
            'prefix' => 'COT',
            'include_year' => true,
            'year_format' => 'Y',
            'separator' => '-',
            'padding' => 5,
            'current_sequence' => 31,
            'is_active' => true,
        ]);

        $folio = FolioSequence::getNextFolio('separated');
        $year = now()->format('Y');

        $this->assertEquals("COT-{$year}-00032", $folio);
    }

    /** @test */
    public function it_resets_yearly_when_configured(): void
    {
        $lastYear = now()->year - 1;

        $sequence = FolioSequence::create([
            'document_type' => 'yearly_reset',
            'prefix' => 'YR',
            'include_year' => true,
            'year_format' => 'y',
            'separator' => '',
            'padding' => 4,
            'current_sequence' => 500,
            'reset_yearly' => true,
            'last_reset_year' => $lastYear,
            'is_active' => true,
        ]);

        $folio = FolioSequence::getNextFolio('yearly_reset');
        $year = now()->format('y');

        // Should reset to 1 because last_reset_year is last year
        $this->assertEquals("YR{$year}0001", $folio);

        // Verify the sequence was reset
        $sequence->refresh();
        $this->assertEquals(1, $sequence->current_sequence);
        $this->assertEquals(now()->year, $sequence->last_reset_year);
    }

    /** @test */
    public function it_falls_back_to_default_format_when_no_sequence_configured(): void
    {
        $folio = FolioSequence::getNextFolio('nonexistent');

        // Should generate default format: PREFIX-YYHHMIS
        $this->assertMatchesRegularExpression('/^NON-\d{8}$/', $folio);
    }

    /** @test */
    public function it_previews_next_folio_without_incrementing(): void
    {
        $sequence = FolioSequence::create([
            'document_type' => 'preview_test',
            'prefix' => 'PRV',
            'include_year' => false,
            'separator' => '-',
            'padding' => 3,
            'current_sequence' => 10,
            'is_active' => true,
        ]);

        $preview1 = $sequence->previewNextFolio();
        $preview2 = $sequence->previewNextFolio();

        $this->assertEquals('PRV-011', $preview1);
        $this->assertEquals('PRV-011', $preview2);

        // Current sequence should not have changed
        $this->assertEquals(10, $sequence->current_sequence);
    }

    /** @test */
    public function admin_can_list_folio_sequences(): void
    {
        $admin = $this->getAdminUser();

        FolioSequence::create([
            'document_type' => 'test_list_api',
            'prefix' => 'TST',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/folio-sequences');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'documentType',
                        'prefix',
                        'includeYear',
                        'padding',
                        'currentSequence',
                        'nextFolio',
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_view_folio_sequence(): void
    {
        $admin = $this->getAdminUser();

        FolioSequence::create([
            'document_type' => 'test_view_api',
            'prefix' => 'VIW',
            'include_year' => true,
            'year_format' => 'y',
            'padding' => 6,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/folio-sequences/test_view_api');

        $response->assertOk()
            ->assertJsonPath('data.documentType', 'test_view_api')
            ->assertJsonPath('data.prefix', 'VIW');
    }

    /** @test */
    public function admin_can_update_folio_sequence(): void
    {
        $admin = $this->getAdminUser();

        FolioSequence::create([
            'document_type' => 'test_update_api',
            'prefix' => 'UPD',
            'padding' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/folio-sequences/test_update_api', [
                'prefix' => 'NEW',
                'padding' => 6,
                'include_year' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.prefix', 'NEW')
            ->assertJsonPath('data.padding', 6);
    }

    /** @test */
    public function admin_can_set_initial_sequence(): void
    {
        $admin = $this->getAdminUser();

        FolioSequence::create([
            'document_type' => 'test_initial_api',
            'prefix' => 'INI',
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/folio-sequences/test_initial_api/set-initial', [
                'start_from' => 1000,
            ]);

        $response->assertOk();

        $sequence = FolioSequence::where('document_type', 'test_initial_api')->first();
        $this->assertEquals(999, $sequence->current_sequence); // start_from - 1
    }

    /** @test */
    public function admin_can_preview_next_folio(): void
    {
        $admin = $this->getAdminUser();

        FolioSequence::create([
            'document_type' => 'test_preview_api',
            'prefix' => 'PRE',
            'include_year' => true,
            'year_format' => 'y',
            'padding' => 6,
            'current_sequence' => 31,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/folio-sequences/test_preview_api/preview-next');

        $year = now()->format('y');
        $response->assertOk()
            ->assertJsonPath('data.next_folio', "PRE{$year}000032")
            ->assertJsonPath('data.current_sequence', 31);
    }

    /** @test */
    public function unauthorized_user_cannot_access_folio_sequences(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/folio-sequences');

        $response->assertForbidden();
    }
}
