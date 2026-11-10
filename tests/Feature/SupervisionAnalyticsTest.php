<?php

use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\SupervisionReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('analytics page renders incident distribution and charts scoped to creator, collaborator, and admin', function () {
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

    // Creator supervisor sees their report data, categories, and analytical sections
    $response = $this->actingAs($creatorSupervisor)->get(route('supervisi.analytics'));
    $response->assertSuccessful();
    $response->assertSee('Analisis &amp; Grafik Kejadian Ruangan', false);
    $response->assertSee('Cakupan: Logbook Saya &amp; Kolaborator', false);
    $response->assertSee('Distribusi Kategori Masalah');
    $response->assertSee('Status Tindak Lanjut Insiden');
    $response->assertSee('Tren Kejadian &amp; Laporan Logbook Harian', false);
    $response->assertSee('Frekuensi Insiden &amp; Rekap per Ruangan / Bangsal', false);
    $response->assertSee('FRANSISKUS');
    $response->assertSee('Complain');

    // Unrelated supervisor does not see FRANSISKUS in their analytics table
    $this->actingAs($otherSupervisor)
        ->get(route('supervisi.analytics'))
        ->assertSuccessful()
        ->assertSee('Cakupan: Logbook Saya &amp; Kolaborator', false)
        ->assertSee('Belum ada data ruangan pada periode ini.');

    // Admin sees all hospital analytics
    $this->actingAs($admin)
        ->get(route('supervisi.analytics'))
        ->assertSuccessful()
        ->assertSee('Cakupan: Seluruh Rumah Sakit')
        ->assertSee('FRANSISKUS');
});

test('analytics page correctly applies room and status filters', function () {
    $supervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);

    $report1 = SupervisionReport::create([
        'report_date' => '2026-11-05',
        'shift' => 'pagi',
        'room_name' => 'FRANSISKUS',
        'patient_count' => 10,
        'nurse_count' => 2,
        'supervisor_name' => $supervisor->name,
        'nurse_in_charge' => 'Perawat 1',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report1->id,
        'category' => 1,
        'incident_time' => '09:00',
        'description' => 'Insiden Fransiskus Selesai',
        'action_taken' => 'Tindakan 1',
        'status' => 'resolved',
    ]);

    $report2 = SupervisionReport::create([
        'report_date' => '2026-11-06',
        'shift' => 'sore',
        'room_name' => 'TERESIA',
        'patient_count' => 8,
        'nurse_count' => 2,
        'supervisor_name' => $supervisor->name,
        'nurse_in_charge' => 'Perawat 2',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report2->id,
        'category' => 2,
        'incident_time' => '15:00',
        'description' => 'Insiden Teresia Pending',
        'action_taken' => 'Tindakan 2',
        'status' => 'in_progress',
    ]);

    // Filter by room=FRANSISKUS
    $resRoom = $this->actingAs($supervisor)->get(route('supervisi.analytics', ['room' => 'FRANSISKUS']));
    $resRoom->assertSuccessful();
    $resRoom->assertSee('FRANSISKUS');
    $resRoom->assertDontSee('TERESIA');

    // Filter by status=resolved
    $resStatus = $this->actingAs($supervisor)->get(route('supervisi.analytics', ['status' => 'resolved']));
    $resStatus->assertSuccessful();
    $resStatus->assertSee('FRANSISKUS');
    $resStatus->assertDontSee('TERESIA');
});

test('analytics drilldown endpoint returns json incident list scoped to user', function () {
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
        'category' => 1,
        'incident_time' => '10:00',
        'description' => 'Keluhan diet lambat',
        'action_taken' => 'Koordinasi gizi',
        'status' => 'resolved',
    ]);

    $response = $this->actingAs($supervisor)->getJson(route('supervisi.analytics.drilldown', [
        'room' => 'FRANSISKUS',
    ]));

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'room' => 'FRANSISKUS',
        'total' => 1,
    ]);
    $response->assertJsonFragment([
        'description' => 'Keluhan diet lambat',
        'room_name' => 'FRANSISKUS',
        'status' => 'resolved',
    ]);
});

test('analytics export endpoint streams csv with utf8 bom, semicolons, and properly scoped data', function () {
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
        'description' => 'Keluhan diet lambat',
        'action_taken' => 'Koordinasi gizi',
        'status' => 'resolved',
    ]);

    // Creator gets CSV containing FRANSISKUS and incident
    $resCreator = $this->actingAs($creatorSupervisor)->get(route('supervisi.analytics.export'));
    $resCreator->assertSuccessful();
    $resCreator->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($resCreator->headers->get('Content-Disposition'))->toContain('.csv');

    $contentCreator = $resCreator->streamedContent();
    expect($contentCreator)->toStartWith("\xEF\xBB\xBF");
    expect($contentCreator)->toContain('Tanggal Supervisi');
    expect($contentCreator)->toContain('Shift;Ruangan;');
    expect($contentCreator)->toContain('FRANSISKUS');
    expect($contentCreator)->toContain('Keluhan diet lambat');

    // Unrelated supervisor gets CSV that does not contain FRANSISKUS
    $resOther = $this->actingAs($otherSupervisor)->get(route('supervisi.analytics.export'));
    $resOther->assertSuccessful();
    $contentOther = $resOther->streamedContent();
    expect($contentOther)->not->toContain('FRANSISKUS');

    // Admin gets CSV containing FRANSISKUS
    $resAdmin = $this->actingAs($admin)->get(route('supervisi.analytics.export'));
    $resAdmin->assertSuccessful();
    $contentAdmin = $resAdmin->streamedContent();
    expect($contentAdmin)->toContain('FRANSISKUS');
});

test('analytics export endpoint respects filtered scope vs all scope', function () {
    $supervisor = User::factory()->create([
        'nip' => '1672010896',
        'name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'app_roles' => 1,
    ]);

    $report1 = SupervisionReport::create([
        'report_date' => '2026-11-05',
        'shift' => 'pagi',
        'room_name' => 'FRANSISKUS',
        'patient_count' => 10,
        'nurse_count' => 2,
        'supervisor_name' => $supervisor->name,
        'nurse_in_charge' => 'Perawat 1',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report1->id,
        'category' => 1,
        'incident_time' => '09:00',
        'description' => 'Insiden Fransiskus',
        'action_taken' => 'Tindakan 1',
        'status' => 'resolved',
    ]);

    $report2 = SupervisionReport::create([
        'report_date' => '2026-11-06',
        'shift' => 'sore',
        'room_name' => 'TERESIA',
        'patient_count' => 8,
        'nurse_count' => 2,
        'supervisor_name' => $supervisor->name,
        'nurse_in_charge' => 'Perawat 2',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report2->id,
        'category' => 2,
        'incident_time' => '15:00',
        'description' => 'Insiden Teresia',
        'action_taken' => 'Tindakan 2',
        'status' => 'in_progress',
    ]);

    // Export with filter room=FRANSISKUS
    $resFiltered = $this->actingAs($supervisor)->get(route('supervisi.analytics.export', [
        'scope' => 'filtered',
        'room' => 'FRANSISKUS',
    ]));
    $resFiltered->assertSuccessful();
    $contentFiltered = $resFiltered->streamedContent();
    expect($contentFiltered)->toContain('FRANSISKUS');
    expect($contentFiltered)->not->toContain('TERESIA');

    // Export with scope=all (ignores filter parameter)
    $resAll = $this->actingAs($supervisor)->get(route('supervisi.analytics.export', [
        'scope' => 'all',
        'room' => 'FRANSISKUS',
    ]));
    $resAll->assertSuccessful();
    $contentAll = $resAll->streamedContent();
    expect($contentAll)->toContain('FRANSISKUS');
    expect($contentAll)->toContain('TERESIA');
});

test('analytics assigns slate gray hex color to Lain-Lain category in chart and legend', function () {
    $admin = User::factory()->create([
        'nip' => '5508150824',
        'name' => 'Purwanto Ali',
        'app_roles' => 0,
    ]);

    $categoryLain = IncidentCategory::firstOrCreate(
        ['incident_category' => 'Lain-Lain'],
        ['description' => 'Kategori lain-lain', 'is_active' => 'true', 'created_by' => $admin->nip]
    );

    $report = SupervisionReport::create([
        'report_date' => '2026-11-05',
        'shift' => 'pagi',
        'room_name' => 'FRANSISKUS',
        'patient_count' => 10,
        'nurse_count' => 2,
        'supervisor_name' => $admin->name,
        'nurse_in_charge' => 'Perawat 1',
        'status' => 'submitted',
    ]);

    Incident::create([
        'supervision_report_id' => $report->id,
        'category' => $categoryLain->id,
        'incident_time' => '10:00',
        'description' => 'Insiden lainnya di bangsal',
        'action_taken' => 'Tindak lanjut',
        'status' => 'resolved',
    ]);

    $response = $this->actingAs($admin)->get(route('supervisi.analytics'));
    $response->assertSuccessful();

    $categoryStats = $response->viewData('categoryStats');
    expect($categoryStats)->toHaveKey($categoryLain->id)
        ->and($categoryStats[$categoryLain->id]['hex_color'])->toBe('#64748b');

    $chartPayload = $response->viewData('chartPayload');
    expect($chartPayload['category']['colors'])->toContain('#64748b');
});
