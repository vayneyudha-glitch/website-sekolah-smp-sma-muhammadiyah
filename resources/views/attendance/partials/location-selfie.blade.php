<div data-challenge-url="{{ route('attendance.challenge') }}">
    <label class="flex items-start gap-3 text-sm text-gray-700">
        <input type="checkbox" name="location_consent" value="1" required @checked(old('location_consent')) class="mt-1 rounded border-gray-300 text-muhammadiyah-hijau focus:ring-muhammadiyah-hijau">
        <span>Saya menyetujui penggunaan lokasi perangkat untuk verifikasi absensi guru.</span>
    </label>
    <button type="button" data-camera-start class="min-h-11 rounded-lg border border-gray-300 px-4 font-semibold">Aktifkan kamera</button>
    <button type="button" data-camera-capture class="ml-2 hidden min-h-11 rounded-lg border border-gray-300 px-4 font-semibold">Ambil selfie</button>
    <video data-selfie-preview autoplay playsinline class="mt-3 hidden max-h-72 w-full rounded-lg bg-black"></video>
    <label class="mt-3 block font-semibold">Selfie</label>
    <input type="file" name="selfie" accept="image/jpeg,image/png,image/webp" required data-selfie-input class="mt-1 block w-full text-sm">
    <p class="mt-1 text-sm text-gray-600">Selfie disimpan privat dan tidak dapat diakses melalui tautan publik.</p>
    <p data-camera-message role="status" class="mt-1 text-sm text-red-700"></p>
</div>
<input type="hidden" name="latitude" data-latitude>
<input type="hidden" name="longitude" data-longitude>
<input type="hidden" name="accuracy_meters" data-accuracy>
<input type="hidden" name="device_token" data-device-token>
<p data-location-message role="status" class="text-sm text-red-700"></p>
