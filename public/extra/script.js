// AJAX
$(document).ready(function() {
    let retryCount = 0;
    let patientDataForTimer = [];

    function updatePatientCard() {
        $.ajax({
            url: '/ajax/patients',
            method: 'GET',
            timeout: 20000, // Timeout setelah load 20 detik.
            success: function(response) {
                console.log('Response: ',response);
                if (response.patients && response.customerTypeColors) {
                    let patients = response.patients;
                    let customerTypeColors = response.customerTypeColors;

                    retryCount = 0; // Reset counter jika data berhasil termuat.
                    patientDataForTimer = patients; // Simpan data pasien ke variabel.

                    // Kosongkan daftar dalam kolom.
                    $('#jangdik-list').empty();
                    $('#keperawatan-list').empty();
                    $('#farmasi-list').empty();
                    $('#tungguKasir-list').empty();
                    $('#selesaiKasir-list').empty();

                    // Loop untuk mengisi data.
                    patients.forEach(function(patient) {
                        // Warna berdasarkan CustomerType.
                        let headerColor = customerTypeColors[patient.CustomerType] || 'gray';
                        let note = patient.short_note ? `<strong>Note:</strong> ${patient.short_note}` : '';

                        // Template kartu pasien.
                        let patientCard = `
                            <div class="card">
                                <div class="card-header" style="background-color: ${headerColor};">
                                    <strong>${patient.PatientName.length > 15 ? patient.PatientName.slice(0, 15) + '...' : patient.PatientName} / ${patient.MedicalNo}</strong>
                                </div>
                                <div class="card-body">
                                    <p>
                                        ${note}
                                        ${patient.CatRencanaPulang !== null ? 
                                            `<a class="more-link" data-bs-toggle="popover"
                                                title="${patient.PatientName}'s Note"
                                                data-bs-content="${patient.CatRencanaPulang}">
                                                selengkapnya
                                            </a>` : ''}
                                    </p>
                                    <p><strong>Wait Time:</strong><span id="wait-time-${patient.MedicalNo}"> ${patient.wait_time}</span><br></p>
                                    <div class="progress mb-1">
                                        <div id="progress-bar-${patient.MedicalNo}" 
                                            class="progress-bar ${patient.progress_percentage > 100 ? 'progress-bar-red' : 'progress-bar-blue'}"
                                            role="progressbar"
                                            style="width: ${patient.progress_percentage}%"
                                            aria-valuenow="${patient.progress_percentage}"
                                            aria-valuemin="0"
                                            aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        let selesaiKasirCard = `
                            <div class="card">
                                <div class="card-header" style="background-color: ${headerColor};">
                                    <strong>${patient.PatientName.length > 15 ? patient.PatientName.slice(0, 15) + '...' : patient.PatientName} / ${patient.MedicalNo}</strong>
                                </div>
                                <div class="card-body" id="selesaiCardBody">
                                    <p class="blinking-text"><strong>Administrasi Selesai.</strong></p>
                                    <img id="check-logo" src="Logo_img/accept.png" alt="Check" style="height: 20px; width: 20px; margin-right: 0;">
                                </div>
                            </div>
                        `;

                        // Menentukan kolom berdasarkan status pasien
                        if (patient.Keterangan === 'Tunggu Jangdik') {
                            $('#jangdik-list').append(patientCard);
                        } else if (patient.Keterangan === 'Tunggu Keperawatan') {
                            $('#keperawatan-list').append(patientCard);
                        } else if (patient.Keterangan === 'Tunggu Farmasi') {
                            $('#farmasi-list').append(patientCard);
                        } else if (patient.Keterangan === 'Tunggu Kasir') {
                            $('#tungguKasir-list').append(patientCard);
                        } else if (patient.Keterangan === 'Selesai Kasir') {
                            $('#selesaiKasir-list').append(selesaiKasirCard);
                        }
                    });

                    // Keterangan update.
                    const now = new Date();
                    const timestring = now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
                    $('#update-info').text(`[Terakhir diperbarui pada ${timestring}]`);
                    console.log("Data updated successfully");

                    // Memanggil fungsi updateTime untuk memperbarui waktu tunggu.
                    updateTime(response);
                } else {
                    console.log('response is not an array.');
                }
            },
            error: function(jqHXR, textStatus) {
                if (textStatus === 'timeout') {
                    console.warn('Koneksi timeout. mencoba menghubungkan ulang...');
                } else {
                    console.warn('Terjadi error: ', textStatus);
                }

                // Percobaan koneksi ulang.
                if (retryCount < 3) {
                    retryCount++;
                    $('#update-info').text(`Koneksi timeout. Mencoba menghubungkan ulang... (${retryCount} dari 3)`);
                    setTimeout(updatePatientCard, 2000);
                } else {
                    // Jika sudah gagal 3 kali percobaan.
                    $('#update-info').text('Gagal memuat data. Silakan refresh halaman secara manual.');
                }
            }
        });
    }

    // Fungsi menghitung waktu tunggu pasien sejak assign rencana pulang.
    function updateTime() {
        if (patientDataForTimer && Array.isArray(patientDataForTimer)) {
            console.log('Tipe patientDataForTimer:', typeof patientDataForTimer);
            console.log('Isi patientDataForTimer:', patientDataForTimer);
            patientDataForTimer.forEach(patient => {
                if (patient != null) {
                    const waitTimeElementId = 'wait-time-' + patient.MedicalNo;
                    const progressBarElementId = 'progress-bar-' + patient.MedicalNo;

                    var waitTimeElement = document.getElementById(waitTimeElementId);
                    var progressBar = document.getElementById(progressBarElementId);

                    if (waitTimeElement && progressBar) {
                        // Cek jika status pasien bukan 'SelesaiKasir'
                        if (patient.status !== 'SelesaiKasir') {
                            var dischargeTime = new Date(patient.RencanaPulang).getTime();
                            var currentTime = new Date().getTime();
                            var waitTimeInSeconds = Math.floor((currentTime - dischargeTime) / 1000);

                            if(waitTimeInSeconds >= 0) {
                                var hours = Math.floor(waitTimeInSeconds / 3600);
                                var minutes = Math.floor((waitTimeInSeconds % 3600) / 60);
                                var seconds = waitTimeInSeconds % 60;
                                var waitTimeFormatted = ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2);

                                waitTimeElement.innerHTML = waitTimeFormatted;

                                var standardWaitTimeInSeconds = (patient.keterangan === 'TungguFarmasi') ? 3600 : 1800; // 1 jam untuk TungguFarmasi, 15 menit untuk lainnya

                                var progressPercentage = Math.min((waitTimeInSeconds / standardWaitTimeInSeconds) * 100, 100);
                                progressBar.style.width = progressPercentage + '%';

                                // Reset class progress bar
                                progressBar.classList.remove('progress-bar-red', 'progress-bar-blue');
                                if (waitTimeInSeconds > standardWaitTimeInSeconds) {
                                    progressBar.classList.add('progress-bar-red');
                                } else {
                                    progressBar.classList.add('progress-bar-blue');
                                }
                            }
                        } else {
                            console.warn(`Element for patient ${patient.MedicalNo} is not found.`);
                        }
                    }
                }
            });
        } else {
            console.warn('response is empty or not defined.'); // Jika response kosong atau tidak valid
            console.log('Tipe patientDataForTimer:', typeof patientDataForTimer);
            console.log('Isi patientDataForTimer:', patientDataForTimer);
        }
    }

    updatePatientCard();
    updateTime();
    
    setInterval(updatePatientCard, 90000);
    setInterval(updateTime, 1000);
});

// Fungsi popover untuk note pasien.
document.addEventListener('DOMContentLoaded', function () {
    // Inisialisasi semua popover
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            trigger: 'hover'
        });
    });
});

// // Toggle tooltip visibility on click
// function toggleTooltip() {
//     const tooltip = document.getElementById('tooltip');
//     tooltip.classList.toggle('show');
// }

// // Close tooltip if user clicks outside
// document.addEventListener('click', function(event) {
//     const isClickInside = event.target.closest('.info-btn') || event.target.closest('.info-tooltip');
//     const tooltip = document.getElementById('tooltip');

//     if (!isClickInside && tooltip.classList.contains('show')) {
//         tooltip.classList.remove('show');
//     }
// });