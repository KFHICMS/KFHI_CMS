<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\KfhiTestCase;

class ChildPhotoTest extends KfhiTestCase
{
    public function test_photo_can_be_uploaded_and_read_back_privately(): void
    {
        Storage::fake('local');
        $officer = $this->makeUser('child_officer');
        $child   = $this->makeChild('Education');

        $this->asUser($officer)->post("/api/children/{$child->id}/photo", [
            'photo' => UploadedFile::fake()->image('kid.jpg', 200, 200),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('has_photo', true);

        $this->assertNotNull($child->fresh()->photo_path);
        Storage::disk('local')->assertExists($child->fresh()->photo_path);

        $this->asUser($officer)->get("/api/children/{$child->id}/photo")->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken('')->getJson("/api/children/{$child->id}/photo")->assertUnauthorized();
    }

    public function test_bad_files_and_unauthorised_users_are_refused(): void
    {
        Storage::fake('local');
        $officer = $this->makeUser('child_officer');
        $child   = $this->makeChild('Education');

        $this->asUser($officer)->post("/api/children/{$child->id}/photo", [
            'photo' => UploadedFile::fake()->create('notes.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->asUser($officer)->post("/api/children/{$child->id}/photo", [
            'photo' => UploadedFile::fake()->image('huge.jpg')->size(4096),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $field = $this->makeUser('field_officer', ['Education']);
        $this->asUser($field)->post("/api/children/{$child->id}/photo", [
            'photo' => UploadedFile::fake()->image('kid.jpg'),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }
}