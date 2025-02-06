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
    <div id="loading-indicator" style="display: none;">
        <div class="overlay"></div>
        <div class="spinner">
            <img src="{{ asset('Logo_img/ambulance.gif') }}" alt="Ambulance loading" id="ambulance-load">
            <p id="loading-text">LOADING...</p>
        </div>
    </div>
    <div class="container">
        <nav class="navbar navbar-expand-sm">
            <div class="d-flex align-items-center justify-content-start">
                <!-- <img src="{{ asset('Logo_img/hospital-bed.png') }}" alt="ranap" style="height: 50px; width: 40px; margin-right: 20px; margin-bottom:10px"> -->
                <div class="d-flex flex-column">
                    <h6 class="mb-1"><strong>PASIEN RENCANA PULANG, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</strong></h6>
                </div>
            </div>
            <div class="ma-auto">
                <p id="update-info"> Memuat Data Terbaru...</p>
            </div>
        </nav>
    </div>
    
    <div class="content-container">
        <div class="row d-flex">

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

            <!-- Kolom Billing -->
            <div class="col" id="kasir-column">
                <div class="header" id="column-title">
                    Billing
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="tungguKasir-list">    
                    <!-- Data akan di-load menggunakan AJAX -->    
                </div>
            </div>

            <!-- Kolom Bayar/Piutang -->
            <div class="col" id="selesai-kasir-column">
                <div class="header" id="column-title">
                    Bayar/Piutang
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="selesaiKasir-list">
                    <!-- Data akan di-load menggunakan AJAX -->
                </div>
            </div>

            <!-- Kolom Bed -->
            <div class="col" id="toClean-list">
                <div class="header" id="column-title">
                    Bed Dibersihkan
                </div>
                <hr class="border-5"/>
                <div class="scrollable" id="bed-list">
                    <!-- Data akan di-load menggunakan AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="bootstrap-5.3.3-dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>

    <script src="{{ asset('extra/script.js') }}"></script>

    <script>
        // Konfigurasi Pusher
        Pusher.logToConsole = true;
        var pusher = new Pusher('base64:TlAZweLNiE36FZiSZNy66R7oZbyVnnpeM1I4gc3QGRw=', {
            cluster: 'ap1',
            forceTLS: true
        });

        // Subscribe channel 'data_update'.
        var channel = pusher.subscribe('data-update');
        
        // Event diterima, lakukan aksi
        channel.bind('data_updated', function(data) {
            console.log('Data update: ', data.message);

            // Random delay supaya tidak ada tarikan data bersamaan di temp_data_ajax
            const randomDelay = Math.floor(Math.random() * (5000 - 1000 + 1)) + 3000;

            // Menunggu dengan delay acak sebelum refresh.
            setTimeout(function() {
                // Refresh data
                $.ajax({
                    url: "/ajax/process",  // Sesuaikan dengan route API-mu
                    type: "GET",
                    success: function(response) {
                        if (response.status === "locked") {
                            Swal.fire({
                                icon: 'info',
                                title: 'Informasi',
                                text: response.message,
                                // timer: 10000,
                                showConfirmButton: false
                            });
                        } else {
                            // Render data ke halaman jika tidak terkunci
                            renderPatientData(response.patients);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Gagal mengupdate data. Silahkan refresh manual setelah beberapa saat.',
                            timer: 10000,
                            showConfirmButton: false
                        });
                    }
                });
            }, randomDelay);
        });
    </script>
</body>
</html>