// AJAX
$(document).ready(function() {
    const AJAX_TIMEOUT = 20000;
    const MAX_RETRY_COUNT = 3;
    const RETRY_INTERVAL = 2000;
    const UPDATE_INTERVAL = 60000;
    const TIME_UPDATE_INTERVAL = 1000;
    const LOCK_CHECK_INTERVAL = 120000;

    let retryCount = 0;
    let patientDataForTimer = [];
    let bedDataForTimer = [];

    function updatePatientCard() {
        $.ajax({
            url: '/molang_pasien/ajax/patients',
            method: 'GET',
            timeout: AJAX_TIMEOUT,
            beforeSend: function() {
                // Tampilkan indikator loading.
                $('#loading-indicator').show();
            },
            success: function(response) {
                console.log('Response: ',response);
                if (response.patients && response.customerTypeColors && response.beds) {
                    let patients = response.patients;
                    let customerTypeColors = response.customerTypeColors;
                    let beds = response.beds;

                    retryCount = 0; // Reset counter jika data berhasil termuat.
                    patientDataForTimer = patients; // Simpan data pasien ke variabel.
                    bedDataForTimer = beds; // Simpan data bed ke variabel.

                    // Kosongkan daftar dalam kolom.
                    $('#keperawatan-list').empty();
                    $('#farmasi-list').empty();
                    $('#tungguKasir-list').empty();
                    $('#selesaiKasir-list').empty();
                    $('#bed-list').empty();

                    // Loop untuk mengisi data.
                    patients.forEach(function(patient) {
                        // Warna berdasarkan CustomerType.
                        let headerColor = customerTypeColors[patient.CustomerType] || 'gray';
                        let customerTypeIcon = patient.customerTypeIcons ? `<img src="${patient.customerTypeIcons}" style="height: 33px" alt="customerTypeIcon" class="customer-type-icon">` : '';
                        let note = patient.short_note ? `<strong>Note:</strong> ${patient.short_note}` : '';

                        // Menambah icon untuk jangdik.
                        let iconHtml = patient.order_icon ? `<img src="${patient.order_icon}" alt="order Icon" class="order-icon">` : '';

                        // Template kartu pasien.
                        let patientCard = `
                            <div class="card">
                                <div class="card-header" style="background-color: ${headerColor};">
                                    <div class="patient-name" style="flex-grow: 1; width: 10vw;">
                                        <strong>${patient.PatientName.length > 15 ? patient.PatientName.slice(0, 15) + '...' : patient.PatientName} / ${patient.BedCode}</strong>
                                    </div>
                                    <span class="customerBadge badge-${patient.CustomerType}">${customerTypeIcon}</span>
                                </div>
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div> 
                                        <p>${note}</p>
                                        <p><strong>Wait Time:</strong><span id="wait-time-${patient.MedicalNo}"> ${patient.waitTimeFormatted}</span><br></p>
                                        <div class="progress mb-1">
                                            <div id="progress-bar-${patient.MedicalNo}" 
                                                class="progress-bar ${patient.progress_percentage > 100 ? 'progress-bar-red' : 'progress-bar-blue'}"
                                                role="progressbar"
                                                style="width: ${patient.progress_percentage}%"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        ${iconHtml}
                                    </div>
                                </div>
                            </div>
                        `;

                        let bayarCard = `
                            <div class="card">
                                <div class="card-header" style="background-color: ${headerColor};">
                                    <div class="patient-name" style="flex-grow: 1; width: 10vw;">
                                        <strong>${patient.PatientName.length > 15 ? patient.PatientName.slice(0, 15) + '...' : patient.PatientName} / ${patient.BedCode}</strong>
                                    </div>
                                    <span class="customerBadge badge-${patient.CustomerType}">${customerTypeIcon}</span>
                                </div>
                                <div class="card-body" id="selesaiCardBody">
                                    <img id="check-logo" src="Logo_img/accept.png" alt="Check" style="height: 20px; width: 20px; margin-left: 0;">
                                    <p class="blinking-text" style="color: red"><strong>Administrasi Selesai.</strong></p>
                                    ${patient.BolehPulang !==null ? `
                                        <img id="SIP-check-logo" src="Logo_img/accept.png" alt="Check" style="height: 20px; width: 20px; margin-left: 0;">    
                                        <p class="blinking-text" style="color: red"><strong>Cetak SIP.</strong></p>
                                    ` : `<p style="color: black"><strong>Cetak SIP.</strong></p>`}
                                </div>
                            </div>
                        `;

                        let billingCard = `
                            <div class="card">
                                <div class="card-header" style="background-color: ${headerColor};">
                                    <div class="patient-name" style="flex-grow: 1; width: 10vw;">
                                        <strong>${patient.PatientName.length > 15 ? patient.PatientName.slice(0, 15) + '...' : patient.PatientName} / ${patient.BedCode}</strong>
                                    </div>
                                    <span class="customerBadge badge-${patient.CustomerType}">${customerTypeIcon}</span>
                                </div>
                                <div class="card-body" id="selesaiCardBody">
                                ${patient.billingDate !== null ? `
                                    <p><strong>Billing Ready:</strong> ${patient.billingDate}<br></p>
                                    <p class="blinking-text"><strong>Tagihan pasien sudah siap.</strong></p>
                                ` : `
                                    <p><strong>Tagihan pasien dalam perhitungan.</strong></p>
                                `}
                                </div>
                            </div>
                        `;

                        // Menentukan kolom berdasarkan status pasien
                        if (patient.Keterangan === 'Tunggu Jangdik' || patient.Keterangan === 'Tunggu Keperawatan') {
                            $('#keperawatan-list').append(patientCard);
                        } else if (patient.Keterangan === 'Tunggu Farmasi') {
                            $('#farmasi-list').append(patientCard);
                        } else if (patient.Keterangan === 'Billing') {
                            $('#tungguKasir-list').append(billingCard);
                        } else if (patient.Keterangan === 'Bayar/Piutang') {
                            $('#selesaiKasir-list').append(bayarCard);
                        }
                    });

                    // Menampilkan data bed.
                    beds.forEach(function(bed) {
                        let bedCard = `
                            <div class="card">
                                <div class = "card-header" style = "background-color: lightgrey;">
                                    <strong>${bed.BedCode}</strong>
                                </div>
                                <div class = "card-body">
                                    <div> 
                                        <p><strong>Cleaning Time:</strong><span id="clean-time-${bed.BedCode}"> ${bed.cleaningTimeFormatted}</span><br></p>
                                        <div class="progress mb-1">
                                            <div id="progress-bar-${bed.BedCode}" 
                                                class="progress-bar ${bed.progressPercentage > 100 ? 'progress-bar-red' : 'progress-bar-blue'}"
                                                role="progressbar"
                                                style="width: ${bed.progressPercentage}%"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        $('#bed-list').append(bedCard);
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
            complete: function() {
                // Sembunyikan indikator loading.
                $('#loading-indicator').hide();
            },
            error: function(jqHXR, textStatus, errorThrown) {
                if (textStatus === 'timeout') {
                    console.warn('Koneksi timeout. mencoba menghubungkan ulang...');
                    $('#update-info').text('Koneksi timeout. mencoba menghubungkan ulang...');
                } else {
                    console.warn('Terjadi error: ', textStatus);
                    $('#update-info').text('Terjadi kesalahan.');
                    console.warn('Terjadi error: ', textStatus, errorThrown);
                    console.warn('Response: ', jqXHR.responseText);
                }

                // Percobaan koneksi ulang.
                if (retryCount < MAX_RETRY_COUNT) {
                    retryCount++;
                    $('#loading-text').text(`Koneksi timeout. Mencoba menghubungkan ulang... (${retryCount} dari 3)`);
                    setTimeout(updatePatientCard, RETRY_INTERVAL);
                } else {
                    // Jika sudah gagal 3 kali percobaan.
                    $('#update-info').text('GAGAL TERHUBUNG, MOHON REFRESH HALAMAN.');
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menghubungkan Ulang!',
                        text: 'Silahkan refresh halaman secara manual.',
                        confirmButtonText: 'Refresh'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#loading-indicator').show();
                            setTimeout(() => {
                                location.reload(); // Reload halaman saat tombol ditekan.
                            }, 1000);
                        }
                    });
                }
            }
        });
    }

    // Fungsi menghitung waktu tunggu pasien sejak assign rencana pulang.
    function updateTime() {
        if (patientDataForTimer && Array.isArray(patientDataForTimer)) {
            patientDataForTimer.forEach(patient => {
                if (patient != null) {
                    const waitTimeElementId = 'wait-time-' + patient.MedicalNo;
                    const progressBarElementId = 'progress-bar-' + patient.MedicalNo;

                    var waitTimeElement = document.getElementById(waitTimeElementId);
                    var progressBar = document.getElementById(progressBarElementId);

                    if (waitTimeElement && progressBar) {
                        // Cek jika status pasien bukan 'Bayar/Piutang'
                        if (patient.status !== 'Bayar/Piutang') {
                            var startTime = new Date(patient.start_time).getTime();
                            var currentTime = new Date().getTime();
                            var waitTimeInSeconds = Math.floor((currentTime - startTime) / 1000);

                            if(waitTimeInSeconds >= 0) {
                                var hours = Math.floor(waitTimeInSeconds / 3600);
                                var minutes = Math.floor((waitTimeInSeconds % 3600) / 60);
                                var seconds = waitTimeInSeconds % 60;
                                var waitTimeFormatted = ('0' + hours).slice(-2) + ':' + ('0' + minutes).slice(-2) + ':' + ('0' + seconds).slice(-2);

                                waitTimeElement.innerHTML = waitTimeFormatted;

                                // Standard wait time saat ini
                                var standardWaitTimeInSeconds = patient.standard_time * 60;

                                var progress_percentage = Math.min((waitTimeInSeconds / standardWaitTimeInSeconds) * 100, 100);
                                progressBar.style.width = progress_percentage + '%';

                                // Reset class progress bar
                                progressBar.classList.remove('progress-bar-red', 'progress-bar-blue');
                                if (waitTimeInSeconds > standardWaitTimeInSeconds) {
                                    progressBar.classList.add('progress-bar-red');
                                } else {
                                    progressBar.classList.add('progress-bar-blue');
                                }
                            }
                        } else {
                            console.warn(`Pasien on Bayar/Piutang.`);
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
    
    function updateCleaningTime() {
        if (bedDataForTimer && Array.isArray(bedDataForTimer)) {
            bedDataForTimer.forEach(bed => {
                if (bed != null) {
                    const cleaningTimeElementId = 'clean-time-' + bed.BedCode;
                    const bedProgressBarElementId = 'progress-bar-' + bed.BedCode;

                    var cleaningTimeElement = document.getElementById(cleaningTimeElementId);
                    var bedProgressBar = document.getElementById(bedProgressBarElementId);

                    if (cleaningTimeElement && bedProgressBar) {
                        // Cek jika status pasien bukan 'Bayar/Piutang'
                        if (bed.GCBedStatus == '0116^H') {
                            var emptyTime = new Date(bed.LastUnoccupiedDate).getTime();
                            var timeNow = new Date().getTime();
                            var cleaningTime = Math.floor((timeNow - emptyTime)/1000);
    
                            if(cleaningTime >= 0) {
                                var Hours = Math.floor(cleaningTime / 3600);
                                var Minutes = Math.floor((cleaningTime % 3600) / 60);
                                var Seconds = cleaningTime % 60;
                                var cleaningTimeFormatted = ('0' + Hours).slice(-2) + ':' + ('0' + Minutes).slice(-2) + ':' + ('0' + Seconds).slice(-2);

                                cleaningTimeElement.innerHTML = cleaningTimeFormatted;

                                var standardCleaningTimeInSeconds = bed.bed_standard_time * 60;

                                var progressPercentage = Math.min((cleaningTime / standardCleaningTimeInSeconds) * 100, 100);
                                bedProgressBar.style.width = progressPercentage + '%';

                                // Reset class progress bar
                                bedProgressBar.classList.remove('progress-bar-red', 'progress-bar-blue');
                                if (cleaningTime > standardCleaningTimeInSeconds) {
                                    bedProgressBar.classList.add('progress-bar-red');
                                } else {
                                    bedProgressBar.classList.add('progress-bar-blue');
                                }
                            }
                        } else {
                            console.warn(`Bed masih terisi.`);
                        }
                    }
                }
            });
        } else {
            console.log('Tipe bedDataForTimer:', typeof bedDataForTimer);
            console.log('Isi bedDataForTimer:', bedDataForTimer);
        }
    }

    function checkDataLockAndUpdate(retry = 0) {
        $.ajax({
            url: "/molang_pasien/ajax/process", // Route API untuk status terkunci atau tidak.
            type: "GET",
            success: function(response) {
                if (response.status === "locked") {
                    console.log("Menampilkan notifikasi.");
                    Swal.fire({
                        icon: 'info',
                        title: 'Data Update.',
                        text: response.message,
                        timer: 5000,
                        showConfirmButton: false
                    });

                    if (retry < MAX_RETRY_COUNT) {
                        console.log("Mencoba ulang ke-" + retry);
                        setTimeout(() => checkDataLockAndUpdate(retry + 1), 3000);
                    } else {
                        console.log("Bersiap mengulang lagi");
                        setTimeout(() => checkDataLockAndUpdate(0), 3000);
                    }
                }

                if (response.status === "success") {
                    console.log("Data sudah terupdate.");
                    updatePatientCard();
                }
            },
            error: function() {
                console.error("Koneksi ke API mengalami error");
                updatePatientCard();
            }
        });
    }

    updatePatientCard();
    updateTime();
    updateCleaningTime();
    checkDataLockAndUpdate();
    
    setInterval(updatePatientCard, UPDATE_INTERVAL);
    setInterval(updateTime, TIME_UPDATE_INTERVAL);
    setInterval(updateCleaningTime, TIME_UPDATE_INTERVAL);
    setInterval(checkDataLockAndUpdate, LOCK_CHECK_INTERVAL);
});