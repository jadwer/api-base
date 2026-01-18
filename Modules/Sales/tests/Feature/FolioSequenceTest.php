<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\Sales\Models\FolioSequence;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class FolioSequenceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::findOrCreate('sales.folio-sequences.index');
        Permission::findOrCreate('sales.folio-sequences.show');
        Permission::findOrCreate('sales.folio-sequences.update');

        // Create admin user with permissions
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo([
            'sales.folio-sequences.index',
            'sales.folio-sequences.show',
            'sales.folio-sequences.update',
        ]);
    }

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
        FolioSequence::create([
            'document_type' => 'quote',
            'prefix' => 'COT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
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
        FolioSequence::create([
            'document_type' => 'quote',
            'prefix' => 'COT',
            'include_year' => true,
            'year_format' => 'y',
            'padding' => 6,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/folio-sequences/quote');

        $response->assertOk()
            ->assertJsonPath('data.documentType', 'quote')
            ->assertJsonPath('data.prefix', 'COT');
    }

    /** @test */
    public function admin_can_update_folio_sequence(): void
    {
        FolioSequence::create([
            'document_type' => 'quote',
            'prefix' => 'COT',
            'padding' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson('/api/v1/folio-sequences/quote', [
                'prefix' => 'QUO',
                'padding' => 6,
                'include_year' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.prefix', 'QUO')
            ->assertJsonPath('data.padding', 6);
    }

    /** @test */
    public function admin_can_set_initial_sequence(): void
    {
        FolioSequence::create([
            'document_type' => 'quote',
            'prefix' => 'COT',
            'current_sequence' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/folio-sequences/quote/set-initial', [
                'start_from' => 1000,
            ]);

        $response->assertOk();

        $sequence = FolioSequence::where('document_type', 'quote')->first();
        $this->assertEquals(999, $sequence->current_sequence); // start_from - 1
    }

    /** @test */
    public function admin_can_preview_next_folio(): void
    {
        FolioSequence::create([
            'document_type' => 'quote',
            'prefix' => 'COT',
            'include_year' => true,
            'year_format' => 'y',
            'padding' => 6,
            'current_sequence' => 31,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/folio-sequences/quote/preview-next');

        $year = now()->format('y');
        $response->assertOk()
            ->assertJsonPath('data.next_folio', "COT{$year}000032")
            ->assertJsonPath('data.current_sequence', 31);
    }

    /** @test */
    public function unauthorized_user_cannot_access_folio_sequences(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/folio-sequences');

        $response->assertForbidden();
    }
}
