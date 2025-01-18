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
            font-size: 14px;
            background:rgb(255, 255, 255);
        }

        .container-fluid {
            margin: 0 1vw;
            padding: 0;
        }

        .table {
            table-layout: fixed;
            word-wrap: break-word;
            border: 1.5px solid rgb(130, 130, 130);
        }

        .table thead th {
            background-color: rgb(221, 221, 221);
        }

        th, td {
            text-align: center;
            vertical-align: middle;
        }

        .table-responsive {
            overflow-x: auto;
            width: 98%;
        }

        /* Indikator loading data */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba( 0, 0, 0, 0.5 );
            z-index: 9998;
        }

        .spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 9999;
        }

        #ambulance-load {
            height: 30vh;
            width: 27vw;
            margin: 0 auto 5px auto;
        }

        #loading-text {
            font-size: 20px;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div id="loading-indicator" style="display: none;">
        <div class="overlay"></div>
        <div class="spinner">
            <img src="{{ asset('Logo_img/ambulance.gif') }}" alt="Ambulance loading" id="ambulance-load">
            <p id="loading-text">HARAP TUNGGU...</p>
        </div>
    </div>
    <div class="container-fluid mt-2">
        <h2>Rekap Data Kepulangan Pasien</h2>
        
        <form action="{{ route('rkp') }}" method="GET">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="service_unit_name" class="form-label">Ruang Perawatan</label>
                    <select name="service_unit_name" id="service_unit_name" class="form-select">
                        <option value="">Semua</option>
                        @foreach($available_units as $unit)
                            <option value="{{ $unit }}" {{ old('service_unit_name', request('service_unit_name')) == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', request('start_date')) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', request('end_date')) }}" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Tampilkan Data</button>
            <button type="button" class="btn btn-success" onclick="window.location='{{ route('export.excel', request()->input()) }}'" id="print-excel">Export Excel</button>
        </form>

        @if(isset($data))
            <div class="table-responsive mt-4">
                <table id="tabel-rekap" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>PASIEN</th>
                            <th>RUANG PERAWATAN</th>
                            <th>RENCANA PULANG</th>
                            <th>JANGDIK</th>
                            <th>KEPERAWATAN</th>
                            <th>FARMASI</th>
                            <th>SELESAI BILLING</th>                                                  
                            <th>WAKTU PASIEN PULANG</th>                         
                            <th>RENCANA PULANG - PASIEN PULANG (hh:mm)</th>                         
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $row)
                            <tr>                              
                                <td style="background-color: 
                                    @php
                                        $color = $CustomerTypeColors->firstWhere('customer_type', $row->CustomerType)->color ?? '#ffffff'; 
                                    @endphp
                                    {{ $color }} !important;
                                ">
                                    <strong>{{ $row->PatientName }}</strong><br>
                                    <small>{{ $row->MedicalNo }}</small>
                                </td>
                                <td>{{ $row->ServiceUnitName }}</td>
                                <td>{{ $row->RencanaPulang }}</td>
                                <td>{{ $row->Jangdik }}</td>
                                <td>{{ $row->Keperawatan }}</td>
                                <td>{{ $row->Farmasi }}</td>
                                <td>{{ $row->SelesaiBilling }}</td>                                                        
                                <td>{{ $row->RoomDischargeDateTime }}</td>                            
                                <td>
                                    <span><strong>{{ $row->rpul_roomclose }}</strong></span>
                                    <div class="chart-bar" id="chart-bar-{{ $row->MedicalNo }}">
                                        <canvas id="chart-{{ $row->MedicalNo }}" width="100" height="45"></canvas>
                                    </div>
                                </td>                          
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination d-flex justify-content-center">
                {{ $data->appends(request()->input())->links() }}
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const loadingIndicator = document.getElementById('loading-indicator');

            form.addEventListener('submit', function(event) {
                loadingIndicator.style.display = 'block';
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @foreach($data as $row) 
                // Script untuk menggambar chart
                var ctx = document.getElementById('chart-{{ $row->MedicalNo }}').getContext('2d');
                var percentage = {{ $row->performancePercentage ?? 0 }};
                var myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Presentase'],
                        datasets: [{
                            label: 'Presentase',
                            data: [percentage],
                            backgroundColor: percentage > 80 ? 'green' : (percentage > 50 ? 'yellow' : 'red'),
                            borderColor: 'black',
                            borderWidth: 1.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        scales: {
                            x: {
                                display: false,
                                beginAtZero: true,
                                max: 100
                            },
                            y: {
                                display: false,
                                beginAtZero: true,
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                        }
                    }
                });
            @endforeach
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
