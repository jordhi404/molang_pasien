<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ip_mappings;
use Illuminate\Support\Facades\Log;
use App\Models\Patient;
use App\Models\Bed;
use App\Models\bed_cleaning_record;
use App\Models\patient_transition;
use Illuminate\Support\Facades\DB;

class RanapController extends Controller
{
    // Fungsi untuk menentukan data di temp_data_ajax expired atau tidak.
    private function isDataExpired($data) {
        $expirationTime = 120;
        $updateAt = Carbon::parse($data->updated_at);
        $expired = $updateAt->diffInSeconds(now());

        Log::info("Cek Expired: " . $expired . " detik.");
        Log::info("UpdateAt: " . $updateAt);
        
        $isExpired = $expired > $expirationTime;
        Log::info("Hasil pengecekan expired: " . ($isExpired ? 'Kadaluarsa' : 'Masih valid'));

        return $isExpired;
    }
    
    public function getPatientDataAjax(Request $request) {
        $data = DB::connection('pgsql')->table('temp_data_ajax')->first();
        $beds = collect(Bed::getBedToClean());
        $ipAddress = $request->ip();
        $unit = ip_mappings::on('pgsql')->where('ip_address', $ipAddress)->value('unit');
        $serviceUnit = $this->getServiceUnit($unit);

        // Cek apakah data sudah expired.
        if (!$data || $this->isDataExpired($data)) {
            // Data expired, hapus data lama lalu ambil data baru.
            DB::connection('pgsql')->table('temp_data_ajax')->truncate();
            Log::info('Truncate temp_data_ajax pada: ' . now());
            Patient::getPatientData();
            Log::info('getPatientData dipanggil pada: ' . now());
            $data = DB::connection('pgsql')->table('temp_data_ajax')->get();
            Log::info('Insert data baru ke temp_data_ajax pada: ' . now());
        } else {
            // Data tidak expired, ambil data yang sudah ada.
            $data = DB::connection('pgsql')->table('temp_data_ajax')->get();
            Log::info('Data tidak expired.');
        }
        $patients = collect($data);

        /* MENGAMBIL DATA PASIEN UNTUK DITAMPILKAN. */
        if ($unit !== 'TEKNOLOGI INFORMASI') {
            if ($unit == 'PENDAFTARAN') {
                $allowedWards = ['TJAN KHEE SWAN BARAT', 'TJAN KHEE SWAN TIMUR', 'RUANG ASA'];

                $patients = $patients->filter(function ($patient) use ($allowedWards) {
                    return in_array($patient->ServiceUnitName, $allowedWards);
                });
    
                $beds = $beds->filter(function ($bed) use ($allowedWards) {
                    return in_array($bed->ServiceUnitName, $allowedWards);
                });
            } else {
                $patients = $patients->filter(function ($patient) use ($serviceUnit) {
                    return $patient->ServiceUnitName == $serviceUnit;
                });
    
                $beds = $beds->filter(function ($bed) use ($serviceUnit) {
                    return $bed->ServiceUnitName == $serviceUnit;
                });
            }
        }

        Log::info('IP client: ' . $ipAddress);
        Log::info('unit IP address: ' . $unit);
        Log::info('Service Unit: ' . $serviceUnit);

        /* WARNA HEADER KARTU BERDASARKAN customerType (PENJAMIN BAYAR). */
        $customerTypeColors = DB::table('customer_type_colors')->pluck('color', 'customer_type');
        $customerTypeIcon = DB::table('customer_type_colors')->pluck('logo_path', 'customer_type');

        foreach ($patients as $patient) {
            // Patient's short note.
            $patient->short_note = $patient->CatRencanaPulang ? Str::limit($patient->CatRencanaPulang, 10) : null;
            $currentTime = Carbon::now();
            $status = $patient->Keterangan;
            $dischargeTime = Carbon::parse($patient-> RencanaPulang);
            $customerTypeIcons = $customerTypeIcon[$patient->CustomerType] ?? null;
            $billingDate = Carbon::parse($patient->Billing)->format('d/m/Y H:i');


            $patient -> billingDate = $billingDate;
            $patient -> customerTypeIcons = $customerTypeIcons;

            $patient -> customerTypeIcons = '/molang_pasien' . $customerTypeIcons;


            // Mapping status ke kolom tabel.
            $column = $this-> mapStatusToColumn($status);

            // Cek apakah entri pasien sudah ada di tabel patient_transitions
            $transition = patient_transition::firstOrCreate(
                ['MedicalNo' => $patient->MedicalNo], // Kunci unik
                [
                    'PatientName' => $patient->PatientName,
                    'ServiceUnitName' => $patient->ServiceUnitName,
                    'CustomerType' => $patient->CustomerType,
                    'RencanaPulang' => $patient->RencanaPulang,
                ]
            );

            // Jika data baru dibuat, gunakan dischargeTime untuk kolom status.
            if ($transition->wasRecentlyCreated) {
                // Cek dischargeTime dibandingkan currentTime.
                if ($dischargeTime->gt($currentTime)) {
                    $dischargeTime = $currentTime;
                }
                $transition->update([$column => $dischargeTime]);
                $startTime = $dischargeTime;
            } else {
                // Jika kolom status terkait kosong, isi dengan currentTime.
                if (!$transition->{$column}) {
                    $transition->update([$column => $currentTime]);
                    $startTime = $currentTime;
                } else {
                    // Ambil waktu mulai dari kolom status.
                    $startTime = Carbon::parse($transition->{$column})->setTimezone('Asia/Jakarta');

                    // Validasi waktu mulai: jika tidak valid, isi dengan currentTime
                    if (!$startTime || $startTime->gt($currentTime)) {
                        $transition->update([$column => $currentTime]);
                        $startTime = $currentTime;
                    }
                }
            }

            $patient->start_time = $startTime->toIso8601String();

            // Ambil standard_time dari tabel standard_times.
            $standardTime = DB::table('standard_times')
                            ->where('keterangan', $patient->Keterangan)
                            ->value('standard_time');
            $patient->standard_time = $standardTime;

            // Mengecek unit jangdik dengan standing order.
            $orderTypes = DB::table('order_types')->get();

            if ($status == 'Tunggu Jangdik') {
                foreach ($orderTypes as $orderType) {
                    if (str_contains($patient->TungguJangdik, $orderType->code_prefix)) {
                        $patient->order_icon = '/molang_pasien' . $orderType->icon_path;
                        break;
                    }
                }
            } elseif ($status == 'Tunggu Farmasi') {
                foreach ($orderTypes as $orderType) {
                    if (str_contains($patient->TungguFarmasi, $orderType->code_prefix)) {
                        $patient->order_icon = '/molang_pasien' . $orderType->icon_path;
                        break;
                    }
                }
            }
        }

        foreach ($beds as $bed) {
            $BedCode = $bed->BedCode; // Untuk debugging
            $GCBedStatus = $bed->GCBedStatus;
            $BedStatus = $bed->BedStatus;

            $bedCleaningRecord = bed_cleaning_record::firstOrCreate(
                ['BedCode' => $BedCode], // Kunci unik
                [
                    'ServiceUnitName' => $bed->ServiceUnitName,
                    'LastUnoccupiedDate' => $bed->LastUnoccupiedDate,
                ]
            );

            // Jika status bed menunjukkan sedang dibersihkan
            if ($GCBedStatus == '0116^H' || $BedStatus == 'SEDANG DIBERSIHKAN') {
                // Jika bed belum tercatat, isi BedUnoccupiedInReality dan ExpectedDoneCleaning
                if (!$bedCleaningRecord->BedUnoccupiedInReality) {
                    $bedCleaningRecord->BedUnoccupiedInReality = Carbon::now();
                    $bedCleaningRecord->ExpectedDoneCleaning = Carbon::now()->addMinutes(20);
                    $bedCleaningRecord->save();
                }
            } else{
                // Jika bed sudah selesai dibersihkan, isi DoneCleaningInReality dan CleaningDuration
                if (!$bedCleaningRecord->DoneCleaningInReality) {
                    $bedCleaningRecord->DoneCleaningInReality = Carbon::now();

                    // Menghitung durasi pembersihan bed
                    if ($bedCleaningRecord->BedUnoccupiedInReality) {
                        $bedCleaningRecord->CleaningDuration = Carbon::parse($bedCleaningRecord->DoneCleaningInReality)
                                                                ->diffInSeconds(Carbon::parse($bedCleaningRecord->BedUnoccupiedInReality));
                    }
                    $bedCleaningRecord->save();
                }
            }
            $bedStandardTime = DB::table('standard_times')
                            ->where('keterangan', $bed->BedStatus)
                            ->value('standard_time');
            $bed->bed_standard_time = $bedStandardTime;

            // Log::info('Bed status bed ' . $BedCode . ': ' . $BedStatus);
        }

        return response()->json([
            'patients' => $patients->values()->toArray(),
            'beds' => $beds->values()->toArray(),
            'customerTypeColors' => $customerTypeColors->toArray(),
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
    protected function getServiceUnit($unit){
        return DB::table('service_units')->where('unit_code', $unit)->value('unit_service_name');
    }
    
    // Mengambil mapping status pasien.
    protected function mapStatusToColumn($status) {
        return DB::table('status_mappings')->where('keterangan', $status)->value('status_value');
    }    
}