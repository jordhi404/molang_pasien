console.log("Script loaded");

$(document).ready(function() {
    let retryCount = 0;

    function updatePatientTable() {
        $.ajax({
            url: '/ajax/patients',
            method: 'GET',
            timeout: 20000, // Timeout setelah load 20 detik.
            success: function(data) {
                retryCount = 0; // Reset counter jika data berhasil termuat.
                let rows = '';

                // Urutkan data berdasarkan ServiceUnitName dan PatientName (tambahan jika di sisi JS)
                data.sort((a, b) => {
                    if (a.ServiceUnitName < b.ServiceUnitName) return -1;
                    if (a.ServiceUnitName > b.ServiceUnitName) return 1;
                    if (a.PatientName < b.PatientName) return -1;
                    if (a.PatientName > b.PatientName) return 1;
                    return 0;
                });


                data.forEach(function(patient) {
                    rows += `<tr>
                                <td><strong>${patient.PatientName}</strong><br> ${patient.MedicalNo} / ${patient.BedCode}</td>
                                <td>${patient.CustomerType}</td>
                                <td>${patient.CatRencanaPulang}</td>
                                <td>${patient.TungguJangdik}</td>
                                <td>${patient.Keperawatan}</td>
                                <td>${patient.TungguFarmasi}</td>
                                <td>${patient.SelesaiBilling}</td>
                                <td>${patient.Keterangan}</td>
                            </tr>`;
                });
                $('#patients-table tbody').html(rows);

                // Keterangan update.
                const now = new Date();
                const timestring = now.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
                $('#update-info').text(`Terakhir diperbarui pada ${timestring}`);
                console.log("Data updated successfully");
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
                    $('#update-info').text('Koneksi timeout');
                    $('#patients-table tbody').html(`<tr><td colspan="10"">Menghubungkan ulang... (${retryCount} dari 3)</td></tr>`);
                    setTimeout(updatePatientTable, 2000);
                } else {
                    // Jika sudah gagal 3 kali percobaan.
                    $('#patients-table tbody').html('<tr><td colspan="10" style="color: red;">Gagal memuat data. Silakan refresh halaman secara manual.</td></tr>');
                }
            }
        });
    }

    updatePatientTable();
    
    setInterval(updatePatientTable, 90000);
});