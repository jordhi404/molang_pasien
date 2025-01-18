<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Data Pasien dan Bed</title>
    <link rel="stylesheet" href="bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <style>
        html, body{
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            margin: 0 2vw;
            padding: 0;
            justify-content: center;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
    <div class="container mt-2">
        <h2>Rekap Data Pasien atau Bed</h2>
        
        <form action="{{ route('rekap.show') }}" method="GET">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="data_type" class="form-label">Pilih Jenis Data</label>
                    <select name="data_type" id="data_type" class="form-select" required>
                        <option value="pasien" {{ old('data_type', request('data_type')) == 'pasien' ? 'selected' : '' }}>Rekap Pasien</option>
                        <option value="bed" {{ old('data_type', request('data_type')) == 'bed' ? 'selected' : '' }}>Rekap Bed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', request('start_date')) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', request('end_date')) }}" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Tampilkan Data</button>
        </form>

        @if(isset($data))
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            @if($data_type == 'pasien')
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Ruang Perawatan</th>
                                <th>Penjamin Bayar</th>
                                <th>Kelas Perawatan</th>
                                <th>Rencana Pulang</th>
                                <th>Keperawatan</th>
                                <th>Farmasi</th>
                                <th>Kasir</th>
                                <th>Selesai Billing</th>
                            @else
                                <th>Kode Bed</th>
                                <th>Ruang Perawatan</th>
                                <th>Waktu Bed Kosong</th>
                                <th>Perkiraan Selesai Dibersihkan</th>
                                <th>Waktu Selesai Dibersihkan</th>
                                <th>Durasi Pembersihan</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $row)
                            <tr>
                                @if($data_type == 'pasien')
                                    <td>{{ $row->MedicalNo }}</td>
                                    <td>{{ $row->PatientName }}</td>
                                    <td>{{ $row->ServiceUnitName }}</td>
                                    <td>{{ $row->CustomerType }}</td>
                                    <td>{{ $row->ChargeClassName }}</td>
                                    <td>{{ $row->RencanaPulang }}</td>
                                    <td>{{ $row->Keperawatan }}</td>
                                    <td>{{ $row->Farmasi }}</td>
                                    <td>{{ $row->Kasir }}</td>
                                    <td>{{ $row->SelesaiBilling }}</td>
                                @else
                                    <td>{{ $row->BedCode }}</td>
                                    <td>{{ $row->ServiceUnitName }}</td>
                                    <td>{{ $row->BedUnoccupiedInReality }}</td>
                                    <td>{{ $row->ExpectedDoneCleaning }}</td>
                                    <td>{{ $row->DoneCleaningInReality }}</td>
                                    <td>{{ $row->CleaningDuration }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="pagination d-flex justify-content-center">
                    {{ $data->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
