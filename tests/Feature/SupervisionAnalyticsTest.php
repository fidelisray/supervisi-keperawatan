<?php

use App\Models\Incident;
use App\Models\SupervisionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('analytics page renders incident distribution charts scoped to user role', function () {
    $supervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);

    $report = SupervisionReport::create([
        'report_date' => '2026-11-05',
        'shift' => 'pagi',
        'room_name' => 'FRANSISKUS',
        'patient_count' => 14,
        'nurse_count' => 3,
        'supervisor_name' => $supervisor->name,
        'nurse_in_charge' => 'Anastasia Naning Sudiar, S.Kep.Ners',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report->id,
        'category' => 'complain',
        'incident_time' => '10:00',
        'description' => 'Keluhan keterlambatan diet pasien.',
        'action_taken' => 'Koordinasi instalasi gizi.',
        'status' => 'resolved',
    ]);

    $response = $this->actingAs($supervisor)->get(route('supervisi.analytics'));

    $response->assertSuccessful();
    $response->assertSee('Analisis & Grafik Kejadian Ruangan');
    $response->assertSee('FRANSISKUS');
    $response->assertSee('Complain Pasien/Keluarga');
});
