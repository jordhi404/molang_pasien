<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ranap</title>
    <link rel="icon" href="{{ asset('Logo_img/logo_rs.jpg') }}" type="image/x-icon">
    <link rel="stylesheet" href="bootstrap-5.3.3-dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="extra/style.css">
</head>
<body>
    <div class="container">
        <nav class="navbar navbar-expand-sm">
            <div class="d-flex align-items-center justify-content-start">
                <!-- <img src="{{ asset('Logo_img/hospital-bed.png') }}" alt="ranap" style="height: 50px; width: 40px; margin-right: 20px; margin-bottom:10px"> -->
                <div class="d-flex flex-column">
                    <h6 class="mb-1"><strong>PASIEN RENCANA PULANG, {{ date('d F Y') }}</strong></h6>
                </div>
            </div>
            <div class="ma-auto">
                <p id="update-info"> Memuat Data Terbaru...</p>
            </div>
        </nav>
    </div>
    
    <div class="content-container">
        <div class="row d-flex">

            <!-- Kolom Jangdik -->
            <div class="col" id="jangdik-column">
                <div class="header" id="column-title">
                    Jangdik
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="jangdik-list">
                    <!-- Data akan di-load menggunakan AJAX -->
                </div>
            </div>

            <!-- Kolom Keperawatan -->
            <div class="col" id="keperawatan-column">
                <div class="header" id="column-title">
                    Keperawatan
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="keperawatan-list">
                    <!-- Data akan di-load menggunakan AJAX -->
                </div>
            </div>

            <!-- Kolom Farmasi -->
            <div class="col" id="farmasi-column">
                <div class="header" id="column-title">
                    Farmasi
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="farmasi-list"> 
                    <!-- Data akan di-load menggunakan AJAX -->
                </div>
            </div>

            <!-- Kolom Tunggu Kasir -->
            <div class="col" id="kasir-column">
                <div class="header" id="column-title">
                    Kasir
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="tungguKasir-list">    
                    <!-- Data akan di-load menggunakan AJAX -->    
                </div>
            </div>

            <!-- Kolom Selesai Kasir -->
            <div class="col" id="selesai-kasir-column">
                <div class="header" id="column-title">
                    Selesai
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="selesaiKasir-list">
                    <!-- Data akan di-load menggunakan AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('extra/script.js') }}"></script>
</body>
</html>