<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RanapV2</title>
    <link rel="stylesheet" href="bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="extra/style.css">
</head>
<body>
    <div class="container-fluid">
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="container-fluid">
                <div class="navbar-title d-flex flex-column">
                    <h2 style="margin-bottom: 5px;">Dashboard Rawat Inap</h2>
                    <p style="margin: 0; font-size: 20px;">{{date ('d F Y')}}</p>
                </div>
                <p id="update-info" style="font-size: 14px; color: gray;">Memuat data terbaru...</p>
            </div>
        </nav>
        <table class="table table-bordered" id="patients-table">
            <thead>
                <tr>
                    <th>PATIENT NAME</th>
                    <th>PENJAMIN BAYAR</th>
                    <th>NOTE PASIEN</th>
                    <th>ORDER JANGDIK</th>
                    <th>KEPERAWATAN</th>
                    <th>ORDER FARMASI</th>
                    <th>SELESAI PEMBAYARAN</th>
                    <th>KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                
            </tbody>
        </table>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="JS/bootstrap.bundle.min.js"></script>
    <script src="extra/script.js"></script>
    
</body>
</html>