<?php

namespace Tests\Feature;

use App\Models\QrToken;
use Tests\KfhiTestCase;

class IdCardTest extends KfhiTestCase
{
    private function tokenFromScanUrl(string $scanUrl): string
    {
        parse_str((string) parse_url($scanUrl, PHP_URL_QUERY), $query);

        return $query['t'];
    }

    public function test_id_card_data_contains_identification_fields_only(): void
    {
        $child = $this->makeChild('Education', [
            'gnd' => 'Hikkaduwa North', 'area' => 'Galle', 'office_code' => 'LKA005', 'guardian_type' => 'Parent',
            'health' => 'Chronic', 'disability_type' => 'Hearing', 'emergency_contacts' => '0771234567',
        ]);

        $this->asUser($this->makeUser('child_officer'))->getJson("/api/children/{$child->id}/id-card")
            ->assertOk()
            ->assertJsonPath('card.child_code', $child->child_code)
            ->assertJsonPath('card.gnd', 'Hikkaduwa North')
            ->assertJsonPath('card.guardian_type', 'Parent')
            ->assertJsonPath('qr', null)
            ->assertJsonMissingPath('card.health')
            ->assertJsonMissingPath('card.medical_info')
            ->assertJsonMissingPath('card.disability_type')
            ->assertJsonMissingPath('card.emergency_contacts');
    }

    public function test_id_card_returns_the_current_qr_without_revoking_or_creating_anything(): void
    {
        $child   = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');

        $generated = $this->tokenFromScanUrl(
            $this->asUser($officer)->postJson("/api/children/{$child->id}/qr")->assertOk()->json('scan_url')
        );

        $fromCard = $this->tokenFromScanUrl(
            $this->asUser($officer)->getJson("/api/children/{$child->id}/id-card")->assertOk()->json('qr.scan_url')
        );
        // asking again (a reprint) changes nothing
        $this->asUser($officer)->getJson("/api/children/{$child->id}/id-card")->assertOk();

        $this->assertSame(1, QrToken::count());
        $this->assertSame(1, QrToken::where('is_active', true)->count());

        // both the link from the first print and the link from the card endpoint still work
        $scanner = $this->makeUser('field_officer', ['Education']);
        $this->asUser($scanner)->postJson('/api/qr/resolve', ['token' => $generated])->assertOk();
        $this->asUser($scanner)->postJson('/api/qr/resolve', ['token' => $fromCard])
            ->assertOk()->assertJsonPath('child_code', $child->child_code);
    }

    public function test_only_roles_that_can_generate_qr_codes_can_open_the_id_card(): void
    {
        $child = $this->makeChild('Education');

        $this->asUser($this->makeUser('field_officer', ['Education']))
            ->getJson("/api/children/{$child->id}/id-card")->assertForbidden();
        $this->asUser($this->makeUser('survey_enumerator', ['Education']))
            ->getJson("/api/children/{$child->id}/id-card")->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson("/api/children/{$child->id}/id-card")->assertUnauthorized();
    }

    public function test_archived_children_have_no_id_card(): void
    {
        $child = $this->makeChild('Education', ['status' => 'archived']);

        $this->asUser($this->makeUser('child_officer'))
            ->getJson("/api/children/{$child->id}/id-card")->assertStatus(422);
    }

    public function test_viewing_an_id_card_is_audited(): void
    {
        $child   = $this->makeChild('Education');
        $officer = $this->makeUser('child_officer');

        $this->asUser($officer)->getJson("/api/children/{$child->id}/id-card")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $officer->id, 'action' => 'VIEW_ID_CARD', 'entity_id' => $child->id,
        ]);
    }
}