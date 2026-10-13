@props(['number' => null])
@php($whatsapp = filled($number) && $number !== '[Isi dari panel admin]' ? $number : config('school.whatsapp'))
@if (filled($whatsapp) && $whatsapp !== '[Isi dari panel admin]')
<a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}?text={{ rawurlencode('Assalamu’alaikum, saya ingin bertanya tentang sekolah.') }}"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="Hubungi sekolah melalui WhatsApp"
   class="fixed bottom-4 right-4 z-50 inline-flex min-h-12 items-center gap-2 rounded-full bg-green-600 px-4 py-3 font-bold text-white shadow-lg transition hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300">
    <span aria-hidden="true">WhatsApp</span>
    <span class="sr-only">Hubungi sekolah melalui WhatsApp</span>
</a>
@endif
