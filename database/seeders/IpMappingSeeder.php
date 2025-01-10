<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ip_mappings;

class IpMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ip_mappings::on('pgsql')->create(
            ['ip_address' => '10.100.18.154', 'unit' => 'TEKNOLOGI INFORMASI']
        );
    }
}
