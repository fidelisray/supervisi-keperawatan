<?php

use App\Models\Incident;
use App\Models\SupervisionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);
});

test('supervision create page can be rendered with master rooms and registered app_id 6 nurses', function () {
    $response = $this->actingAs($this->user)->get(route('supervisi.create'));

    $response->assertSuccessful();
    $response->assertSee('Logbook Supervisi Ruang Perawatan');
    $response->assertSee('Data Operasional Ruangan');
    $response->assertSee('Kejadian di Ruangan Selama Dinas Shift');
    $response->assertSee('FRANSISKUS');
    $response->assertSee('V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep');
    $response->assertSee('Anastasia Naning Sudiar, S.Kep.Ners');
    $response->assertDontSee('Pegawai Aplikasi Lain');
});

test('supervision index page displays reports and metrics', function () {
    SupervisionReport::create([
        'report_date' => now()->toDateString(),
        'shift' => 'pagi',
        'room_name' => 'Bangsal Antonius (Pria Dewasa)',
        'patient_count' => 15,
        'nurse_count' => 4,
        'supervisor_name' => $this->user->name,
        'nurse_in_charge' => 'Anastasia Naning Sudiar, S.Kep.Ners',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($this->user)->get(route('supervisi.index'));

    $response->assertSuccessful();
    $response->assertSee('Monitoring Supervisi Ruang Perawatan');
    $response->assertSee('Bangsal Antonius (Pria Dewasa)');
    $response->assertSee('15 Pasien');
});

test('can store a new supervision report with incidents using authenticated supervisor name', function () {
    $payload = [
        'report_date' => '2026-11-04',
        'shift' => 'pagi',
        'room_name' => 'Bangsal Maria (Wanita Dewasa)',
        'patient_count' => 12,
        'nurse_count' => 3,
        'supervisor_name' => $this->user->name,
        'nurse_in_charge' => 'Anastasia Naning Sudiar, S.Kep.Ners',
        'general_notes' => 'Operan shift berjalan lancar.',
        'incidents' => [
            [
                'category' => 'complain',
                'incident_time' => '09:30',
                'description' => 'Keluarga pasien mengeluhkan pendingin ruangan (AC) kamar 204 kurang dingin.',
                'action_taken' => 'Perawat menghubungi bagian teknisi sarpras untuk pengecekan filter AC.',
                'status' => 'in_progress',
            ],
            [
                'category' => 'jkn_6_hari',
                'incident_time' => '10:15',
                'description' => 'Pasien Bed 3 BPJS hari rawat ke-7, perlu review DPJP untuk rencana pulang.',
                'action_taken' => 'Dokter DPJP sudah visit dan menjadwalkan KRS besok pagi.',
                'status' => 'resolved',
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->post(route('supervisi.store'), $payload);

    $this->assertDatabaseHas('tb_nurse_supervision_reports', [
        'room_name' => 'Bangsal Maria (Wanita Dewasa)',
        'shift' => 'pagi',
        'patient_count' => 12,
        'nurse_count' => 3,
        'supervisor_name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
    ], 'sqlsrv');

    $report = SupervisionReport::where('room_name', 'Bangsal Maria (Wanita Dewasa)')->first();
    expect($report)->not->toBeNull();

    $this->assertDatabaseCount('tb_nurse_supervision_incidents', 2, 'sqlsrv');
    $this->assertDatabaseHas('tb_nurse_supervision_incidents', [
        'supervision_report_id' => $report->id,
        'category' => 'complain',
        'incident_time' => '09:30',
    ], 'sqlsrv');
    $this->assertDatabaseHas('tb_nurse_supervision_incidents', [
        'supervision_report_id' => $report->id,
        'category' => 'jkn_6_hari',
        'status' => 'resolved',
    ], 'sqlsrv');

    $response->assertRedirect(route('supervisi.show', $report));
});

test('store requires mandatory room and shift information', function () {
    $response = $this->actingAs($this->user)->post(route('supervisi.store'), [
        'report_date' => '',
        'shift' => 'invalid_shift',
        'room_name' => '',
    ]);

    $response->assertSessionHasErrors(['report_date', 'shift', 'room_name', 'patient_count', 'nurse_count']);
});

test('supervision show page displays report and full incidents details', function () {
    $report = SupervisionReport::create([
        'report_date' => now()->toDateString(),
        'shift' => 'sore',
        'room_name' => 'ICU (Intensive Care Unit)',
        'patient_count' => 6,
        'nurse_count' => 3,
        'supervisor_name' => $this->user->name,
        'nurse_in_charge' => 'Anastasia Naning Sudiar, S.Kep.Ners',
        'general_notes' => 'Pasien bed 2 kondisi kritis.',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report->id,
        'category' => 'fasilitas',
        'incident_time' => '15:20',
        'description' => 'Syringe pump nomor 4 alarm berbunyi baterai drop.',
        'action_taken' => 'Alat diganti dengan unit cadangan dari ruang alat.',
        'status' => 'resolved',
    ]);

    $response = $this->actingAs($this->user)->get(route('supervisi.show', $report));

    $response->assertSuccessful();
    $response->assertSee('ICU (Intensive Care Unit)');
    $response->assertSee('Shift Sore');
    $response->assertSee('Syringe pump nomor 4 alarm berbunyi baterai drop.');
    $response->assertSee('Alat diganti dengan unit cadangan dari ruang alat.');
});
