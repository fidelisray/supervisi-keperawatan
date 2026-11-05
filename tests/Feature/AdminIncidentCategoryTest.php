<?php

use App\Models\IncidentCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('administrator can create, update, hide, and soft delete incident categories with audit nip tracking', function () {
    $admin = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali',
        'app_roles' => 0,
    ]);

    $supervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);

    // Supervisor is forbidden from accessing the admin incident categories page
    $this->actingAs($supervisor)
        ->get(route('admin.incident-categories.index'))
        ->assertForbidden();

    // Admin can view the page
    $this->actingAs($admin)
        ->get(route('admin.incident-categories.index'))
        ->assertSuccessful()
        ->assertSee('Manajemen Kategori Masalah & Poin Kejadian');

    // Admin creates a new category
    $this->actingAs($admin)
        ->post(route('admin.incident-categories.store'), [
            'incident_category' => 'Kendala SIMRS',
            'description' => 'Gangguan jaringan atau modul SIMRS di nurse station.',
            'is_active' => 'true',
        ])
        ->assertRedirect(route('admin.incident-categories.index'));

    $category = IncidentCategory::where('incident_category', 'Kendala SIMRS')->first();
    expect($category)->not->toBeNull()
        ->and($category->created_by)->toBe('5508150824')
        ->and($category->is_active)->toBe('true');

    // Active category appears on supervision create page
    $this->actingAs($supervisor)
        ->get(route('supervisi.create'))
        ->assertSuccessful()
        ->assertSee('Kendala SIMRS');

    // Admin updates category
    $this->actingAs($admin)
        ->put(route('admin.incident-categories.update', $category), [
            'incident_category' => 'Kendala Jaringan SIMRS',
            'description' => 'Deskripsi diperbarui oleh admin.',
            'is_active' => 'true',
        ])
        ->assertRedirect(route('admin.incident-categories.index'));

    expect($category->fresh()->incident_category)->toBe('Kendala Jaringan SIMRS')
        ->and($category->fresh()->updated_by)->toBe('5508150824');

    // Admin hides category (toggle active -> 'false')
    $this->actingAs($admin)
        ->patch(route('admin.incident-categories.toggle-active', $category))
        ->assertRedirect(route('admin.incident-categories.index'));

    expect($category->fresh()->is_active)->toBe('false')
        ->and($category->fresh()->updated_by)->toBe('5508150824');

    // Follow the redirect to consume the flash message first
    $this->actingAs($admin)
        ->get(route('admin.incident-categories.index'))
        ->assertSuccessful()
        ->assertSee('Disembunyikan (is_active: false)');

    // Hidden category does not appear on supervision create page
    $this->actingAs($supervisor)
        ->get(route('supervisi.create'))
        ->assertSuccessful()
        ->assertDontSee('Kendala Jaringan SIMRS');

    // Admin soft-deletes category
    $this->actingAs($admin)
        ->delete(route('admin.incident-categories.destroy', $category))
        ->assertRedirect(route('admin.incident-categories.index'));

    $trashed = IncidentCategory::withTrashed()->find($category->id);
    expect($trashed)->not->toBeNull()
        ->and($trashed->trashed())->toBeTrue()
        ->and($trashed->deleted_by)->toBe('5508150824')
        ->and($trashed->is_active)->toBe('false');
});
