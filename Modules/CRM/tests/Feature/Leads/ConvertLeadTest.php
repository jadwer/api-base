<?php

namespace Modules\CRM\Tests\Feature\Leads;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\CRM\Models\Lead;
use Modules\CRM\Models\Opportunity;
use Modules\User\Models\User;

class ConvertLeadTest extends TestCase
{
    public function test_admin_can_convert_lead_creating_contact_and_opportunity(): void
    {
        $admin = $this->getAdminUser();
        $owner = User::factory()->create();

        $lead = Lead::factory()->qualified()->create([
            'user_id' => $owner->id,
            'contact_id' => null,
            'company_name' => 'Acme SA de CV',
            'email' => 'ventas@acme.test',
            'phone' => '5555555555',
            'estimated_value' => 25000.00,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/leads/{$lead->id}/convert", [
                'create_contact' => true,
                'create_opportunity' => true,
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'lead' => ['id', 'status'],
            'contact' => ['id', 'name'],
            'opportunity' => ['id', 'title'],
        ]);
        $response->assertJsonPath('lead.status', 'converted');
        $response->assertJsonPath('contact.name', 'Acme SA de CV');

        // Lead marcado como converted y ligado al contacto
        $lead->refresh();
        $this->assertEquals('converted', $lead->status);
        $this->assertNotNull($lead->converted_at);
        $this->assertNotNull($lead->contact_id);

        // Contacto creado como prospecto (no cliente todavia)
        $contact = Contact::find($lead->contact_id);
        $this->assertNotNull($contact);
        $this->assertEquals('company', $contact->contact_type);
        $this->assertEquals('Acme SA de CV', $contact->name);
        $this->assertEquals('ventas@acme.test', $contact->email);
        $this->assertFalse($contact->is_customer);
        $this->assertEquals('active', $contact->status);

        // Oportunidad ligada al lead
        $opportunity = Opportunity::where('lead_id', $lead->id)->first();
        $this->assertNotNull($opportunity);
        $this->assertEquals($lead->title, $opportunity->name);
        $this->assertEquals(25000.00, $opportunity->amount);
        $this->assertEquals('open', $opportunity->status);
        $this->assertEquals($lead->contact_id, $opportunity->contact_id);
    }

    public function test_convert_without_flags_does_not_create_contact_or_opportunity(): void
    {
        $admin = $this->getAdminUser();
        $owner = User::factory()->create();

        $lead = Lead::factory()->qualified()->create([
            'user_id' => $owner->id,
            'contact_id' => null,
        ]);

        $contactsBefore = Contact::count();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/leads/{$lead->id}/convert", [
                'create_contact' => false,
                'create_opportunity' => false,
            ]);

        $response->assertOk();
        $response->assertJsonPath('lead.status', 'converted');
        $response->assertJsonMissingPath('contact');
        $response->assertJsonMissingPath('opportunity');

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);
        $this->assertNotNull($lead->converted_at);
        $this->assertNull($lead->contact_id);

        $this->assertEquals($contactsBefore, Contact::count());
        $this->assertEquals(0, Opportunity::where('lead_id', $lead->id)->count());
    }

    public function test_convert_does_not_create_duplicate_contact_when_lead_already_has_one(): void
    {
        $admin = $this->getAdminUser();
        $owner = User::factory()->create();
        $existingContact = Contact::factory()->create();

        $lead = Lead::factory()->qualified()->create([
            'user_id' => $owner->id,
            'contact_id' => $existingContact->id,
        ]);

        $contactsBefore = Contact::count();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/leads/{$lead->id}/convert", [
                'create_contact' => true,
                'create_opportunity' => false,
            ]);

        $response->assertOk();
        $this->assertEquals($contactsBefore, Contact::count());

        $lead->refresh();
        $this->assertEquals($existingContact->id, $lead->contact_id);
        $this->assertEquals('converted', $lead->status);
    }

    public function test_user_without_permission_cannot_convert_lead(): void
    {
        $tech = $this->getTechUser();
        $owner = User::factory()->create();

        $lead = Lead::factory()->qualified()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/leads/{$lead->id}/convert", [
                'create_contact' => true,
                'create_opportunity' => true,
            ]);

        $response->assertStatus(403);

        $lead->refresh();
        $this->assertNotEquals('converted', $lead->status);
    }

    public function test_cannot_convert_already_converted_lead(): void
    {
        $admin = $this->getAdminUser();
        $owner = User::factory()->create();

        $lead = Lead::factory()->converted()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/leads/{$lead->id}/convert", [
                'create_contact' => true,
                'create_opportunity' => true,
            ]);

        $response->assertStatus(422);
    }

    public function test_guest_cannot_convert_lead(): void
    {
        $owner = User::factory()->create();

        $lead = Lead::factory()->qualified()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->postJson("/api/v1/leads/{$lead->id}/convert", [
            'create_contact' => true,
            'create_opportunity' => true,
        ]);

        $response->assertStatus(401);
    }
}
