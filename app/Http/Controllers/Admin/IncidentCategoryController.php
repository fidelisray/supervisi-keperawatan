<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncidentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentCategoryController extends Controller
{
    public function index(): View
    {
        $categories = IncidentCategory::query()
            ->withCount('incidents')
            ->orderBy('id')
            ->get();

        return view('admin.incident-categories.index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'incident_category' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:1000'],
            'is_active' => ['nullable', 'in:true,false'],
        ]);

        $name = trim((string) $validated['incident_category']);
        $description = trim((string) $validated['description']);
        $isActive = ($validated['is_active'] ?? 'true') === 'false' ? 'false' : 'true';
        $adminNip = (string) ($request->user()?->nip ?? '');

        IncidentCategory::query()->create([
            'incident_category' => $name,
            'description' => $description,
            'created_by' => $adminNip,
            'is_active' => $isActive,
        ]);

        return redirect()->route('admin.incident-categories.index')
            ->with('success', sprintf('Kategori masalah "%s" berhasil ditambahkan.', $name));
    }

    public function update(Request $request, IncidentCategory $incidentCategory): RedirectResponse
    {
        $validated = $request->validate([
            'incident_category' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:1000'],
            'is_active' => ['required', 'in:true,false'],
        ]);

        $name = trim((string) $validated['incident_category']);
        $description = trim((string) $validated['description']);
        $adminNip = (string) ($request->user()?->nip ?? '');

        $incidentCategory->update([
            'incident_category' => $name,
            'description' => $description,
            'updated_by' => $adminNip,
            'is_active' => $validated['is_active'],
        ]);

        return redirect()->route('admin.incident-categories.index')
            ->with('success', sprintf('Kategori masalah "%s" berhasil diperbarui.', $name));
    }

    public function toggleActive(Request $request, IncidentCategory $incidentCategory): RedirectResponse
    {
        $newStatus = $incidentCategory->isActive() ? 'false' : 'true';
        $adminNip = (string) ($request->user()?->nip ?? '');

        $incidentCategory->update([
            'is_active' => $newStatus,
            'updated_by' => $adminNip,
        ]);

        $statusText = $newStatus === 'true' ? 'diaktifkan kembali' : 'disembunyikan dari halaman input logbook';

        return redirect()->route('admin.incident-categories.index')
            ->with('success', sprintf('Kategori "%s" berhasil %s.', $incidentCategory->incident_category, $statusText));
    }

    public function destroy(Request $request, IncidentCategory $incidentCategory): RedirectResponse
    {
        $adminNip = (string) ($request->user()?->nip ?? '');
        $categoryName = $incidentCategory->incident_category;

        $incidentCategory->update([
            'is_active' => 'false',
            'deleted_by' => $adminNip,
        ]);

        $incidentCategory->delete();

        return redirect()->route('admin.incident-categories.index')
            ->with('success', sprintf('Kategori masalah "%s" berhasil dihapus.', $categoryName));
    }
}

