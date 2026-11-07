<?php

use App\Models\Incident;
use App\Models\SupervisionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('analytics page renders incident distribution and dynamic bar chart scoped to creator, collaborator, and admin', function () {
    $creatorSupervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);

    $otherSupervisor = User::factory()->create([
        'nip' => '2134010899',
        'name' => 'Florentina Hardini, S.Kep.Ners',
        'app_roles' => 1,
    ]);

    $admin = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali',
        'app_roles' => 0,
    ]);

    $report = SupervisionReport::create([
        'report_date' => '2026-11-05',
        'shift' => 'pagi',
        'room_name' => 'FRANSISKUS',
        'patient_count' => 14,
        'nurse_count' => 3,
        'supervisor_name' => $creatorSupervisor->name,
        'nurse_in_charge' => 'Anastasia Naning Sudiar, S.Kep.Ners',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report->id,
        'category' => 1,
        'incident_time' => '10:00',
        'description' => 'Keluhan keterlambatan diet pasien.',
        'action_taken' => 'Koordinasi instalasi gizi.',
        'status' => 'resolved',
    ]);

    // Creator supervisor sees their report data and the interactive Dynamic Bar Chart
    $response = $this->actingAs($creatorSupervisor)->get(route('supervisi.analytics'));
    $response->assertSuccessful();
    $response->assertSee('Analisis & Grafik Kejadian Ruangan');
    $response->assertSee('Cakupan: Logbook Saya & Kolaborator');
    $response->assertSee('Grafik Batang (Bar Chart) Dinamis Supervisi');
    $response->assertSee('Semua Data');
    $response->assertSee('Berdasarkan Incidents');
    $response->assertSee('Berdasarkan Ruangan');
    $response->assertSee('FRANSISKUS');
    $response->assertSee('Complain');

    /*
    // [GLOBAL VISIBILITY LOGIC - Aktifkan blok ini saat seluruh supervisor dapat melihat data analitik seluruh rumah sakit]
    $this->actingAs($otherSupervisor)
        ->get(route('supervisi.analytics'))
        ->assertSuccessful()
        ->assertSee('Cakupan: Seluruh Rumah Sakit')
        ->assertSee('FRANSISKUS');
    */

    // Unrelated supervisor does not see FRANSISKUS in their analytics table
    $this->actingAs($otherSupervisor)
        ->get(route('supervisi.analytics'))
        ->assertSuccessful()
        ->assertSee('Cakupan: Logbook Saya & Kolaborator')
        ->assertSee('Belum ada data ruangan pada periode ini.');

    // Admin sees all hospital analytics
    $this->actingAs($admin)
        ->get(route('supervisi.analytics'))
        ->assertSuccessful()
        ->assertSee('Cakupan: Seluruh Rumah Sakit')
        ->assertSee('FRANSISKUS');
});
