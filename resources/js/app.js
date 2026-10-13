

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.querySelectorAll('[data-attendance-status]').forEach((select) => {
    const form = select.closest('form');
    const reasonFields = form?.querySelector('[data-reason-fields]');
    const reasonInput = form?.querySelector('[data-reason-input]');
    const updateReasonFields = () => {
        const needsReason = ['izin', 'sakit'].includes(select.value);
        reasonFields?.classList.toggle('hidden', !needsReason);
        if (reasonInput) reasonInput.required = needsReason;
    };

    select.addEventListener('change', updateReasonFields);
    updateReasonFields();
});

document.querySelectorAll('[data-location-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.locationReady === 'true') return;

        const status = form.querySelector('[data-attendance-status]')?.value;
        if (!form.hasAttribute('data-camera-form') && status !== 'hadir') return;

        event.preventDefault();
        const message = form.querySelector('[data-location-message]');
        if (!navigator.geolocation) {
            message.textContent = 'Browser ini tidak menyediakan layanan lokasi. Pastikan situs dibuka melalui HTTPS atau localhost, lalu izinkan lokasi di browser.';
            return;
        }

        message.textContent = 'Mengambil lokasi perangkat…';
        navigator.geolocation.getCurrentPosition((position) => {
            form.querySelector('[data-latitude]').value = position.coords.latitude;
            form.querySelector('[data-longitude]').value = position.coords.longitude;
            form.querySelector('[data-accuracy]').value = position.coords.accuracy;

            const deviceToken = form.querySelector('[data-device-token]');
            if (deviceToken) {
                try {
                    let token = localStorage.getItem('school-attendance-device');
                    if (!token) {
                        token = crypto.randomUUID();
                        localStorage.setItem('school-attendance-device', token);
                    }
                    deviceToken.value = token;
                } catch {
                    message.textContent = 'Penyimpanan browser diblokir. Izinkan penyimpanan lokal agar perangkat dapat dikenali untuk peninjauan.';
                    return;
                }
            }

            form.dataset.locationReady = 'true';
            form.submit();
        }, (error) => {
            const details = error.code === error.PERMISSION_DENIED
                ? 'Izin lokasi ditolak. Izinkan akses lokasi untuk situs ini di pengaturan browser.'
                : error.code === error.POSITION_UNAVAILABLE
                    ? 'Lokasi tidak tersedia. Periksa GPS atau koneksi perangkat lalu coba lagi.'
                    : 'Pengambilan lokasi melewati batas waktu. Coba lagi di area dengan sinyal GPS yang lebih baik.';
            message.textContent = `${details} GPS browser hanya berfungsi melalui HTTPS atau localhost.`;
        }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
});

document.querySelectorAll('[data-camera-form]').forEach((form) => {
    const startButton = form.querySelector('[data-camera-start]');
    const captureButton = form.querySelector('[data-camera-capture]');
    const preview = form.querySelector('[data-selfie-preview]');
    const input = form.querySelector('[data-selfie-input]');
    const message = form.querySelector('[data-camera-message]');
    let stream = null;

    startButton?.addEventListener('click', async () => {
        message.textContent = '';
        if (!navigator.mediaDevices?.getUserMedia) {
            message.textContent = 'Kamera tidak tersedia. Pilih foto selfie melalui tombol pilih berkas.';
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
            preview.srcObject = stream;
            preview.classList.remove('hidden');
            captureButton.classList.remove('hidden');
        } catch {
            message.textContent = 'Kamera tidak dapat diakses. Izinkan akses kamera atau pilih foto selfie melalui tombol pilih berkas.';
        }
    });

    captureButton?.addEventListener('click', () => {
        if (!preview.videoWidth || !preview.videoHeight) {
            message.textContent = 'Kamera belum siap. Tunggu beberapa saat lalu coba lagi.';
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = preview.videoWidth;
        canvas.height = preview.videoHeight;
        canvas.getContext('2d').drawImage(preview, 0, 0);
        canvas.toBlob((blob) => {
            if (!blob) {
                message.textContent = 'Selfie gagal dibuat. Silakan ambil ulang.';
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'selfie.jpg', { type: 'image/jpeg' }));
            input.files = transfer.files;
            input.required = true;
            preview.classList.add('hidden');
            captureButton.classList.add('hidden');
            stream?.getTracks().forEach((track) => track.stop());
            message.textContent = 'Selfie berhasil diambil.';
        }, 'image/jpeg', 0.88);
    });

    form.addEventListener('submit', () => {
        stream?.getTracks().forEach((track) => track.stop());
    }, { once: true });
});
