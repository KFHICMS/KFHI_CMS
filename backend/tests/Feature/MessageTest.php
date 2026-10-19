<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Tests\KfhiTestCase;

class MessageTest extends KfhiTestCase
{
    private function send($from, $to, array $extra = [])
    {
        return $this->asUser($from)->postJson('/api/messages', array_merge([
            'recipient_id' => $to->id, 'subject' => 'Hello', 'body' => 'First message',
        ], $extra));
    }

    public function test_message_is_unread_for_the_recipient_until_opened(): void
    {
        $a = $this->makeUser('admin');
        $b = $this->makeUser('field_officer');

        $id = $this->send($a, $b)->assertCreated()->json('id');

        $this->asUser($b)->getJson('/api/messages/unread-count')->assertJsonPath('count', 1);
        $this->asUser($a)->getJson('/api/messages/unread-count')->assertJsonPath('count', 0);

        $this->asUser($b)->getJson('/api/messages')->assertOk()
            ->assertJsonPath('0.unread', true)
            ->assertJsonPath('0.subject', 'Hello')
            ->assertJsonPath('0.with.0', $a->name);

        $this->asUser($b)->getJson("/api/messages/$id")->assertOk()
            ->assertJsonPath('messages.0.body', 'First message')
            ->assertJsonPath('messages.0.mine', false);

        $this->asUser($b)->getJson('/api/messages/unread-count')->assertJsonPath('count', 0);
    }

    public function test_reply_is_stored_and_marks_the_conversation_unread_for_the_other_person(): void
    {
        $a = $this->makeUser('admin');
        $b = $this->makeUser('survey_enumerator');
        $id = $this->send($a, $b)->json('id');

        $this->asUser($b)->postJson("/api/messages/$id/reply", ['body' => 'Thanks, noted'])->assertCreated();

        $this->asUser($a)->getJson('/api/messages/unread-count')->assertJsonPath('count', 1);
        $this->asUser($b)->getJson('/api/messages/unread-count')->assertJsonPath('count', 0);

        $this->asUser($a)->getJson("/api/messages/$id")->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.1.body', 'Thanks, noted')
            ->assertJsonPath('messages.1.mine', false);
    }

    public function test_delete_hides_only_one_conversation_for_only_that_person_and_can_be_restored(): void
    {
        $a = $this->makeUser('admin');
        $b = $this->makeUser('field_officer');
        $first  = $this->send($a, $b)->json('id');
        $second = $this->send($a, $b, ['subject' => 'Other'])->json('id');

        $this->asUser($b)->deleteJson("/api/messages/$first")->assertOk();

        $this->asUser($b)->getJson('/api/messages')->assertJsonCount(1)->assertJsonPath('0.id', $second);
        $this->asUser($a)->getJson('/api/messages')->assertJsonCount(2);
        $this->asUser($b)->getJson('/api/messages?folder=trash')->assertJsonCount(1)->assertJsonPath('0.id', $first);

        $this->asUser($b)->postJson("/api/messages/$first/restore")->assertOk();
        $this->asUser($b)->getJson('/api/messages')->assertJsonCount(2);
    }

    public function test_a_new_reply_brings_a_deleted_conversation_back(): void
    {
        $a = $this->makeUser('admin');
        $b = $this->makeUser('field_officer');
        $id = $this->send($a, $b)->json('id');

        $this->asUser($b)->deleteJson("/api/messages/$id")->assertOk();
        $this->asUser($b)->getJson('/api/messages')->assertJsonCount(0);

        $this->asUser($a)->postJson("/api/messages/$id/reply", ['body' => 'Are you there?'])->assertCreated();

        $this->asUser($b)->getJson('/api/messages')->assertJsonCount(1)->assertJsonPath('0.unread', true);
    }

    public function test_outsiders_cannot_read_reply_or_delete(): void
    {
        $a = $this->makeUser('admin');
        $b = $this->makeUser('field_officer');
        $c = $this->makeUser('child_officer');
        $id = $this->send($a, $b)->json('id');

        $this->asUser($c)->getJson("/api/messages/$id")->assertNotFound();
        $this->asUser($c)->postJson("/api/messages/$id/reply", ['body' => 'Sneaky'])->assertNotFound();
        $this->asUser($c)->deleteJson("/api/messages/$id")->assertNotFound();
        $this->asUser($c)->getJson('/api/messages')->assertJsonCount(0);

        $this->app['auth']->forgetGuards();
        $this->withToken('')->getJson('/api/messages')->assertUnauthorized();
    }

    public function test_sending_is_validated(): void
    {
        $a = $this->makeUser('admin');
        $b = $this->makeUser('field_officer');
        $off = $this->makeUser('field_officer');
        $off->forceFill(['is_active' => false])->save();

        $this->send($a, $a)->assertStatus(422);                                   // yourself
        $this->send($a, $off)->assertStatus(422);                                 // deactivated person
        $this->send($a, $b, ['body' => ''])->assertStatus(422);                   // empty
        $this->send($a, $b, ['body' => str_repeat('x', 5001)])->assertStatus(422); // too long
        $this->send($a, $b, ['subject' => ''])->assertStatus(422);

        $this->asUser($a)->postJson('/api/messages', ['recipient_id' => 999999, 'subject' => 'x', 'body' => 'y'])
            ->assertStatus(422);
    }

    public function test_message_text_never_reaches_the_audit_log(): void
    {
        $a = $this->makeUser('admin');
        $b = $this->makeUser('field_officer');
        $id = $this->send($a, $b, ['body' => 'Very private text'])->json('id');
        $this->asUser($b)->postJson("/api/messages/$id/reply", ['body' => 'Very private reply'])->assertCreated();

        $this->assertSame(1, AuditLog::where('action', 'SEND_MESSAGE')->count());
        $this->assertSame(1, AuditLog::where('action', 'REPLY_MESSAGE')->count());
        $this->assertSame(0, AuditLog::where('details', 'like', '%private%')->count());
    }
}