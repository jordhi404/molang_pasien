console.log("Script loaded");

$(document).ready(function() {
    let retryCount = 0;

    function updatePatientTable() {
        $.ajax({
            url: '/ajax/patients',
            method: 'GET',
            timeout: 10000, // Timeout setelah load 10 detik.
            success: function(data) {
                retryCount = 0; // Reset counter jika data berhasil termuat.
                let rows = '';
                data.forEach(function(patient) {
                    rows += `<tr>
                                <td>${patient.RegistrationNo}</td>
                                <td>${patient.ServiceUnitName}</td>
                                <td>${patient.BedCode}</td>
                                <td>${patient.MedicalNo}</td>
                                <td>${patient.PatientName}</td>
                                <td>${patient.CustomerType}</td>
                                <td>${patient.ChargeClassName}</td>
                                <td>${patient.RencanaPulang}</td>
                                <td>${patient.CatRencanaPulang}</td>
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
                    $('#patients-table tbody').html('<tr><td colspan="10">Menghubungkan ulang($retryCount/3)...</td></tr>');
                    setTimeout(updatePatientTable, 2000);
                } else {
                    // Jika sudah gagal 3 kali percobaan.
                    $('#patients-table tbody').html('<tr><td colspan="10" style="color: red;">Gagal memuat data. Silakan refresh halaman secara manual.</td></tr>');
                    alert('Gagal memuat data setelah 3 kali percobaan. Silahkan refresh secara manual.');
                }
            }
        });
    }

    updatePatientTable();
    
    setInterval(updatePatientTable, 90000);
});