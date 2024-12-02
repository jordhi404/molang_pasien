<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ip_mappings;
use Illuminate\Support\Facades\Log;
use App\Models\Patient;

class RanapController extends Controller
{
    public function getPatientDataAjax(Request $request) {
        $patients = collect(Patient::getPatientData());

        $ipAddress = $request->ip();

        $unit = ip_mappings::on('pgsql')->where('ip_address', $ipAddress)->value('unit');

        $serviceUnit = $this->getServiceUnit($unit);

        /* MENGAMBIL DATA PASIEN UNTUK DITAMPILKAN. */
        if ($unit !== 'TEKNOLOGI INFORMASI') {
            // Jika $unit bukan 'TEKNOLOGI INFORMASI'
            $patients = $patients->filter(function ($patient) use ($serviceUnit) {
                return $patient->ServiceUnitName == $serviceUnit;
            });
        }

        Log::info('IP client: ' . $ipAddress);
        Log::info('unit IP address: ' . $unit);
        Log::info('Service Unit: ' . $serviceUnit);

        /* WARNA HEADER KARTU BERDASARKAN customerType (PENJAMIN BAYAR). */
        $customerTypeColors = [
            'Rekanan' => 'orange',
            'Perusahaan' => 'pink',
            'Yayasan' => 'lime',
            'Karyawan - FASKES' => 'green',
            'Karyawan - PTGJ' => 'lightgreen',
            'Pemerintah' => 'red',
            'Rumah Sakit' => 'aqua',
            'BPJS - Kemenkes' => 'yellow',
            'Pribadi' => 'lightblue',
        ];

        foreach ($patients as $patient) {
            // Patient's short note.
            $patient->short_note = $patient->CatRencanaPulang ? Str::limit($patient->CatRencanaPulang, 10) : null;

            // Mengambil waktu rencana pulang
            $dischargeTime = Carbon::parse($patient->RencanaPulang);

            // Mengambil waktu saat ini.
            $currentTime = Carbon::now();

            // Menghitung waktu tunggu
            if ($dischargeTime->gt($currentTime)) {
                // Jika waktu rencana pulang di masa depan
                $waitTime = '00:00:00'; // Waktu tunggu belum dimulai
                $waitTimeInSeconds = 0; // Inisialisasi waitTimeInSeconds sebagai 0.
            } else {
                // Menghitung selisih waktu
                $waitTimeInSeconds = $dischargeTime->diffInSeconds($currentTime);

                // Format waktu tunggu dalam format hh:mm:ss
                $waitTime = gmdate('H:i:s', $waitTimeInSeconds);
            }    
            $patient->wait_time = $waitTime;

            // Menentukan stndard waktu berdasarkan status.
            $status = $patient->Keterangan; // Ambil status pasien dari variabel Keterangan.
            $standardWaitTimeInSeconds = $status === 'Tunggu Farmasi' ? 3600 : 1800; // Default time 1800 detik (30 menit). Jika Tunggu Farmasi standard nya 1 jam.
            
            // Persentase progress dan progress bar.
            $progressPercentage = min(($waitTimeInSeconds / $standardWaitTimeInSeconds) * 100, 100);
            $patient->progress_percentage = $progressPercentage;
        }

        return response()->json([
            'patients' => $patients->values()->toArray(),
            'customerTypeColors' => $customerTypeColors,
        ]);
    }

    /* FUNCTION UNTUK MENAMPILKAN DATA DI DASHBOARD RANAP. */
    public function showDashboardRanap(Request $request) {
        // Ambil data pasien dari getPatientDataAjax.
        $response = $this->getPatientDataAjax($request);
        $patients = $response->getData()->patients;

        /* MENGIRIM DATA KE VIEW. */
        return view('Ranap.ranap', compact('patients'));
    }

    // Fungsi untuk mendapatkan ServiceUnitName berdasarkan kode_bagian
    protected function getServiceUnit($unit)
    {
        // Mapping kode_bagian ke ServiceUnitName
        $serviceUnits = [
            'TJAN KHEE SWAN TIMUR' => 'TJAN KHEE SWAN TIMUR',
            'TJAN KHEE SWAN BARAT' => 'TJAN KHEE SWAN BARAT',
            'UPI DEWASA' => 'UPI DEWASA',
            'UPI ANAK' => 'UPI ANAK',
            'KWEE HAN TIONG' => 'KWEE HAN TIONG',
            'RUANG ASA' => 'RUANG ASA',
            'PERAWATAN ANAK' => 'PERAWATAN ANAK',
            'PERAWATAN IBU' => 'PERAWATAN IBU',
            'KBY FISIO GD.IBU' => 'KBY FISIO GD.IBU',
            'KEBIDANAN' => 'KEBIDANAN',
            'TEKNOLOGI INFORMASI' => 'TEKNOLOGI INFORMASI',
        ];

        return $serviceUnits[$unit] ?? null;
    }
}