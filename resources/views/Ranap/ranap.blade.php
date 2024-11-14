<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RanapV2</title>
    <link rel="stylesheet" href="CSS/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2 class="my-4">Daftar Pasien Rencana Pulang</h2>
        <link rel="stylesheet" href="CSS/dashboards-style.css">
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nama Pasien</th>
                    <th>Medical No</th>
                    <th>Jadwal Rencana Pulang</th>
                    <th>Tunggu Jangdik</th>
                    <th>Keperawatan</th>
                    <th>Tunggu Farmasi</th>
                    
                </tr>
            </thead>
            <tbody>
                @foreach($patients as $patient)
                    <tr>
                        <td>{{ $patient->PatientName }}</td>
                        <td>{{ $patient->MedicalNo }}</td>
                        <td>{{ $patient->RencanaPulang }}</td>
                        <td>{{ $patient->TungguJangdik }}</td> <!-- Status Tunggu Jangdik -->
                        <td>{{ $patient->Keperawatan }}</td> <!-- Status Keperawatan -->
                        <td>{{ $patient->TungguFarmasi }}</td> <!-- Status Tunggu Farmasi -->
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="JS/bootstrap.bundle.min.js"></script>
    
</body>
</html>