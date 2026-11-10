<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'sqlsrv';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_master_nurse_supervision_role_permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'app_id',
        'role_id',
        'permission_key',
    ];

    /**
     * All available permissions in the system.
     *
     * @var array<string, array{label: string, group: string, description: string}>
     */
    public const AVAILABLE_PERMISSIONS = [
        'create_report' => [
            'label' => 'Buat Laporan Supervisi',
            'group' => 'Operasional Logbook',
            'description' => 'Mengakses form input dan membuat laporan supervisi baru.',
        ],
        'view_all_reports' => [
            'label' => 'Lihat Seluruh Laporan',
            'group' => 'Operasional Logbook',
            'description' => 'Melihat seluruh laporan ruangan tanpa dibatasi pembuat/kolaborator.',
        ],
        'edit_report' => [
            'label' => 'Ubah Laporan Supervisi',
            'group' => 'Operasional Logbook',
            'description' => 'Mengubah data operasional dan rincian logbook supervisi.',
        ],
        /*
        'update_incident_status' => [
            'label' => 'Tindak Lanjut Insiden',
            'group' => 'Operasional Logbook',
            'description' => 'Memperbarui status penanganan dan mengisi solusi kejadian.',
        ],
        */
        'view_analytics' => [
            'label' => 'Akses Grafik Analisis',
            'group' => 'Analisis & Pelaporan',
            'description' => 'Melihat dashboard grafik statistik dan frekuensi insiden ruangan.',
        ],
        'manage_roles' => [
            'label' => 'Kelola Hak Akses Pengguna',
            'group' => 'Administrasi Sistem',
            'description' => 'Mengakses panel manajemen role dan penugasan akses pegawai.',
        ],
        'manage_incident_categories' => [
            'label' => 'Kelola Kategori Insiden',
            'group' => 'Administrasi Sistem',
            'description' => 'Menambah, mengubah, dan menghapus master kategori masalah.',
        ],
    ];

    /**
     * Default permissions for Supervisor Keperawatan (Role 1).
     *
     * @var list<string>
     */
    public const DEFAULT_SUPERVISOR_PERMISSIONS = [
        'create_report',
        'edit_report',
        // 'update_incident_status',
        'view_analytics',
    ];

    /**
     * Get grouped permissions for UI display.
     *
     * @return array<string, array<string, array{label: string, description: string}>>
     */
    public static function groupedPermissions(): array
    {
        $grouped = [];
        foreach (self::AVAILABLE_PERMISSIONS as $key => $meta) {
            $grouped[$meta['group']][$key] = [
                'label' => $meta['label'],
                'description' => $meta['description'],
            ];
        }

        return $grouped;
    }

    /**
     * Get the role associated with this permission.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RefAppRole::class, 'role_id', 'role_id')
            ->where('app_id', AppRole::APP_ID);
    }
}
