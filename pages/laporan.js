(function () {
    const button = document.getElementById('gpsButton');
    const status = document.getElementById('gpsStatus');
    const latitude = document.getElementById('latitude');
    const longitude = document.getElementById('longitude');
    const accuracy = document.getElementById('akurasi_gps_meter');

    if (!button || !status || !latitude || !longitude || !accuracy) {
        return;
    }

    button.addEventListener('click', function () {
        if (!navigator.geolocation) {
            status.textContent = 'Browser tidak mendukung akses lokasi.';
            return;
        }

        status.textContent = 'Mengambil lokasi...';
        button.disabled = true;

        navigator.geolocation.getCurrentPosition(function (position) {
            latitude.value = position.coords.latitude.toFixed(8);
            longitude.value = position.coords.longitude.toFixed(8);
            accuracy.value = Math.round(position.coords.accuracy || 0);
            status.textContent = 'Lokasi berhasil diambil: ' + latitude.value + ', ' + longitude.value + ' (akurasi ' + accuracy.value + ' meter).';
            button.disabled = false;
        }, function () {
            status.textContent = 'Gagal mengambil lokasi. Pastikan izin lokasi browser aktif.';
            button.disabled = false;
        }, {
            enableHighAccuracy: true,
            timeout: 12000,
            maximumAge: 0
        });
    });
})();
