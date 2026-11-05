<?php

use App\Models\Incident;
use App\Models\SupervisionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->supervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);

    $this->collaborator = User::factory()->create([
        'nip' => '2351151100',
        'name' => 'Anastasia Naning Sudiar, S.Kep.Ners',
        'app_roles' => 1,
    ]);

    $this->otherSupervisor = User::factory()->create([
        'nip' => '2134010899',
        'name' => 'Florentina Hardini, S.Kep.Ners',
        'app_roles' => 1,
    ]);

    $this->admin = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali',
        'app_roles' => 0,
    ]);
});

test('supervision create page renders master rooms, multi-collaborator picker, and logout button', function () {
    $response = $this->actingAs($this->supervisor)->get(route('supervisi.create'));

    $response->assertSuccessful();
    $response->assertSee('Logbook Supervisi Ruang Perawatan');
    $response->assertSee('Kolaborator Supervisi (Dapat Lebih dari Satu)');
    $response->assertSee('Keluar Sesi SSO');
    $response->assertSee('FRANSISKUS');
    $response->assertSee('Anastasia Naning Sudiar, S.Kep.Ners');
    $response->assertDontSee('Pegawai Aplikasi Lain');
});

test('can store a new supervision report with multiple collaborators and without explicit shift input', function () {
    $payload = [
        'report_date' => '2026-11-05',
        'room_name' => 'Bangsal Maria (Wanita Dewasa)',
        'patient_count' => 12,
        'nurse_count' => 3,
        'collaborators' => [
            'Anastasia Naning Sudiar, S.Kep.Ners',
            'Cicilia Rumini, S.Kep.Ners',
        ],
        'general_notes' => 'Operan shift berjalan lancar.',
        'incidents' => [
            [
                'category' => 1,
                'incident_time' => '09:30',
                'description' => 'Keluarga pasien mengeluhkan pendingin ruangan (AC) kamar 204 kurang dingin.',
                'action_taken' => 'Perawat menghubungi bagian teknisi sarpras.',
                'status' => 'in_progress',
            ],
        ],
    ];

    $response = $this->actingAs($this->supervisor)->post(route('supervisi.store'), $payload);

    $report = SupervisionReport::where('room_name', 'Bangsal Maria (Wanita Dewasa)')->first();
    expect($report)->not->toBeNull()
        ->and($report->supervisor_name)->toBe($this->supervisor->name)
        ->and($report->collaboratorList())->toBe([
            'Anastasia Naning Sudiar, S.Kep.Ners',
            'Cicilia Rumini, S.Kep.Ners',
        ]);

    $response->assertRedirect(route('supervisi.show', $report));
});

test('collaborator and creator can view, edit logbook, and update incident status while unrelated supervisor is forbidden', function () {
    $report = SupervisionReport::create([
        'report_date' => now()->toDateString(),
        'shift' => 'pagi',
        'room_name' => 'ICU (Intensive Care Unit)',
        'patient_count' => 6,
        'nurse_count' => 3,
        'supervisor_name' => $this->supervisor->name,
        'nurse_in_charge' => 'Anastasia Naning Sudiar, S.Kep.Ners | Cicilia Rumini, S.Kep.Ners',
        'general_notes' => 'Pasien bed 2 kondisi kritis.',
        'status' => 'submitted',
    ]);

    $incident = Incident::create([
        'supervision_report_id' => $report->id,
        'category' => 2,
        'incident_time' => '15:20',
        'description' => 'Syringe pump nomor 4 alarm berbunyi baterai drop.',
        'action_taken' => 'Menunggu teknisi.',
        'status' => 'in_progress',
    ]);

    // Collaborator can view and edit
    $this->actingAs($this->collaborator)
        ->get(route('supervisi.show', $report))
        ->assertSuccessful()
        ->assertSee('ICU (Intensive Care Unit)');

    // Collaborator can update incident status
    $this->actingAs($this->collaborator)
        ->patch(route('supervisi.incidents.update-status', [$report, $incident]), [
            'status' => 'resolved',
            'action_taken' => 'Alat sudah diganti unit cadangan.',
        ])
        ->assertRedirect(route('supervisi.show', $report));

    expect($incident->fresh()->status)->toBe('resolved')
        ->and($incident->fresh()->action_taken)->toBe('Alat sudah diganti unit cadangan.');

    // Collaborator can update full logbook
    $this->actingAs($this->collaborator)
        ->put(route('supervisi.update', $report), [
            'report_date' => now()->toDateString(),
            'room_name' => 'ICU (Intensive Care Unit)',
            'patient_count' => 7,
            'nurse_count' => 4,
            'collaborators' => ['Anastasia Naning Sudiar, S.Kep.Ners'],
            'general_notes' => 'Updated by collaborator.',
            'incidents' => [
                [
                    'category' => 2,
                    'incident_time' => '15:20',
                    'description' => 'Syringe pump nomor 4 alarm berbunyi baterai drop.',
                    'action_taken' => 'Alat sudah diganti unit cadangan.',
                    'status' => 'resolved',
                ],
            ],
        ])
        ->assertRedirect(route('supervisi.show', $report));

    expect($report->fresh()->patient_count)->toBe(7);

    $latestIncident = $report->fresh()->incidents()->first();

    // Unrelated supervisor cannot view, edit, or update incident
    $this->actingAs($this->otherSupervisor)
        ->get(route('supervisi.show', $report))
        ->assertForbidden();

    $this->actingAs($this->otherSupervisor)
        ->get(route('supervisi.edit', $report))
        ->assertForbidden();

    $this->actingAs($this->otherSupervisor)
        ->patch(route('supervisi.incidents.update-status', [$report, $latestIncident]), [
            'status' => 'resolved',
        ])
        ->assertForbidden();

    // Admin has full access to all reports
    $this->actingAs($this->admin)
        ->get(route('supervisi.show', $report))
        ->assertSuccessful();
});

test('store requires mandatory room and at least one collaborator', function () {
    $response = $this->actingAs($this->supervisor)->post(route('supervisi.store'), [
        'report_date' => '',
        'room_name' => '',
    ]);

    $response->assertSessionHasErrors(['report_date', 'room_name', 'patient_count', 'nurse_count']);
});
