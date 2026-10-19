<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ChildCategory;
use Tests\KfhiTestCase;

class ChildCategoryTest extends KfhiTestCase
{
    public function test_each_child_is_counted_once_per_category_and_archived_are_excluded(): void
    {
        $admin = $this->makeUser('admin');
        $a    = $this->makeChild('Education');
        $b    = $this->makeChild('Education');
        $gone = $this->makeChild('Education');

        $this->asUser($admin)->putJson("/api/children/{$a->id}/categories", ['categories' => ['education', 'health', 'health']])->assertOk();
        $this->asUser($admin)->putJson("/api/children/{$b->id}/categories", ['categories' => ['health']])->assertOk();
        $this->asUser($admin)->putJson("/api/children/{$gone->id}/categories", ['categories' => ['shelter']])->assertOk();
        $gone->forceFill(['status' => 'archived'])->save();

        $this->assertSame(2, ChildCategory::where('child_id', $a->id)->count());   // duplicate "health" ignored

        $this->asUser($admin)->getJson('/api/reports/dashboard')->assertOk()
            ->assertJsonPath('categories.education', 1)
            ->assertJsonPath('categories.health', 2)
            ->assertJsonPath('categories.nutrition', 0)
            ->assertJsonPath('categories.shelter', 0);
    }

    public function test_saving_replaces_old_categories_and_is_audited(): void
    {
        $admin = $this->makeUser('admin');
        $child = $this->makeChild('Education');

        $this->asUser($admin)->putJson("/api/children/{$child->id}/categories", ['categories' => ['education']])->assertOk();
        $this->asUser($admin)->putJson("/api/children/{$child->id}/categories", ['categories' => ['nutrition']])->assertOk();

        $this->assertSame(['nutrition'], ChildCategory::where('child_id', $child->id)->pluck('category')->all());

        $this->asUser($admin)->putJson("/api/children/{$child->id}/categories", ['categories' => []])->assertOk();
        $this->assertSame(0, ChildCategory::where('child_id', $child->id)->count());

        $this->assertSame(3, AuditLog::where('action', 'UPDATE_CHILD_CATEGORIES')->count());
    }

    public function test_unknown_categories_are_rejected(): void
    {
        $admin = $this->makeUser('admin');
        $child = $this->makeChild('Education');

        $this->asUser($admin)->putJson("/api/children/{$child->id}/categories", ['categories' => ['food']])
            ->assertStatus(422);
        $this->assertSame(0, ChildCategory::count());
    }

    public function test_field_roles_cannot_edit_and_cannot_read_children_outside_their_programs(): void
    {
        $child = $this->makeChild('Education');

        $inside  = $this->makeUser('field_officer', ['Education']);
        $outside = $this->makeUser('field_officer', ['Other']);

        $this->asUser($inside)->putJson("/api/children/{$child->id}/categories", ['categories' => ['health']])->assertForbidden();
        $this->asUser($inside)->getJson("/api/children/{$child->id}/categories")->assertOk();
        $this->asUser($outside)->getJson("/api/children/{$child->id}/categories")->assertForbidden();
    }
}