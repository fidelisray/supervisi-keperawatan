<?php

namespace Database\Seeders;

use App\Models\IncidentCategory;
use Illuminate\Database\Seeder;

class IncidentCategorySeeder extends Seeder
{
    /**
     * Seed the default incident categories into tb_master_nurse_supervision_incidents_category.
     */
    public function run(): void
    {
        $defaultAdminNip = '5508150824';

        $categories = [
            [
                'incident_category' => 'Complain',
                'description' => 'Keluhan atau komplain dari pasien maupun keluarga pasien terkait pelayanan keperawatan, fasilitas, atau administrasi.',
            ],
            [
                'incident_category' => 'Fasilitas',
                'description' => 'Kendala sarana prasarana, alat kesehatan, atau fasilitas ruang perawatan yang memerlukan perbaikan atau kalibrasi.',
            ],
            [
                'incident_category' => 'JKN >= 6 Hari',
                'description' => 'Pemantauan khusus pasien pengguna jaminan JKN dengan lama rawat inap 6 hari atau lebih.',
            ],
            [
                'incident_category' => 'Lain-Lain',
                'description' => 'Kejadian operasional atau klinis lainnya di luar kategori komplain, fasilitas, dan pemantauan JKN.',
            ],
        ];

        foreach ($categories as $item) {
            IncidentCategory::withTrashed()->updateOrCreate(
                ['incident_category' => $item['incident_category']],
                [
                    'description' => $item['description'],
                    'created_by' => $defaultAdminNip,
                    'updated_by' => null,
                    'deleted_by' => null,
                    'deleted_at' => null,
                    'is_active' => 'true',
                ]
            );
        }
    }
}

