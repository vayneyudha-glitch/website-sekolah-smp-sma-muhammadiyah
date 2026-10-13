<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PpdbApplication;
use App\Models\PpdbDocument;
use App\Models\PpdbSetting;
use App\Models\User;
use App\Services\SchoolNotifier;
use App\Services\PpdbReceiptPdf;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use RuntimeException;

class PpdbController extends Controller
{
    private const REQUIRED_DOCUMENTS = ['kk', 'akta', 'ijazah_rapor', 'pas_foto'];

    public function information(): View
    {
        return view('ppdb.information', [
            'settings' => PpdbSetting::query()->find(1),
        ]);
    }

    public function createAccount(): View|RedirectResponse
    {
        if (Auth::check()) {
            abort_unless(Auth::user()->role === User::ROLE_PENDAFTAR, 403);

            return redirect()->route('pendaftar.dashboard');
        }

        abort_unless(PpdbSetting::query()->whereKey(1)->value('is_open'), 403, 'Pendaftaran PPDB sedang ditutup.');

        return view('ppdb.register');
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(PpdbSetting::query()->whereKey(1)->value('is_open'), 403, 'Pendaftaran PPDB sedang ditutup.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'privacy_consent' => ['required', 'accepted'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            $user->forceFill(['role' => User::ROLE_PENDAFTAR])->save();
            $user->ppdbApplication()->create([
                'student_name' => $user->name,
                'privacy_consent_at' => now(),
                'privacy_policy_version' => '1.0',
            ]);

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('pendaftar.dashboard')->with('status', 'Akun pendaftar berhasil dibuat. Silakan lanjutkan pengisian data.');
    }

    public function dashboard(Request $request): View
    {
        $application = $this->application($request);
        abort_unless(
            $request->user()->role === User::ROLE_PENDAFTAR
                || ($request->user()->role === User::ROLE_SISWA && $application->status === PpdbApplication::STATUS_ACCEPTED),
            403,
        );

        return view('ppdb.dashboard', [
            'application' => $application->load('documents'),
            'requiredDocuments' => self::REQUIRED_DOCUMENTS,
        ]);
    }

    public function updateStudent(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === User::ROLE_PENDAFTAR, 403);
        $application = $this->application($request);
        abort_unless($application->status === PpdbApplication::STATUS_DRAFT, 403);

        $data = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'digits:10', 'unique:ppdb_applications,nisn,'.$application->id],
            'birth_place' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string', 'max:3000'],
            'previous_school' => ['required', 'string', 'max:255'],
            'school_level' => ['required', 'in:SMP,SMA'],
            'major' => ['required_if:school_level,SMA', 'nullable', 'string', 'max:120'],
        ]);

        $data['major'] = $data['school_level'] === 'SMA' ? ($data['major'] ?? null) : null;
        $application->update($data);
        $request->user()->update(['name' => $data['student_name']]);

        return redirect()->route('pendaftar.dashboard')->with('status', 'Data murid berhasil disimpan sebagai draf.');
    }

    public function updateGuardian(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === User::ROLE_PENDAFTAR, 403);
        $application = $this->application($request);
        abort_unless($application->status === PpdbApplication::STATUS_DRAFT, 403);

        $application->update($request->validate([
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relation' => ['required', 'string', 'max:50'],
            'guardian_phone' => ['required', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
        ]));

        return redirect()->route('pendaftar.dashboard')->with('status', 'Data orang tua/wali berhasil disimpan sebagai draf.');
    }

    public function uploadDocuments(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === User::ROLE_PENDAFTAR, 403);
        $application = $this->application($request);
        abort_if(in_array($application->status, [PpdbApplication::STATUS_ACCEPTED, PpdbApplication::STATUS_REJECTED], true), 403);

        $rules = [];
        foreach (['kk', 'akta', 'ijazah_rapor', 'pas_foto'] as $category) {
            $rules[$category] = ['nullable', 'file', 'max:2048', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png'];
        }
        $rules['lainnya'] = ['nullable', 'array', 'max:10'];
        $rules['lainnya.*'] = ['file', 'max:2048', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png'];
        $rules['lainnya_ulang'] = ['nullable', 'array', 'max:10'];
        $rules['lainnya_ulang.*'] = ['file', 'max:2048', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png'];
        $files = $request->validate($rules);
        abort_unless($application->status === PpdbApplication::STATUS_DRAFT || empty($files['lainnya']), 403);

        $filesToStore = [];
        foreach (['kk', 'akta', 'ijazah_rapor', 'pas_foto'] as $category) {
            if (! isset($files[$category])) {
                continue;
            }

            $existing = $application->documents()->where('category', $category)->latest()->first();
            if ($existing && $existing->status !== PpdbDocument::STATUS_REJECTED) {
                return back()->withErrors([$category => 'Berkas hanya dapat diunggah ulang jika statusnya ditolak.']);
            }

            $filesToStore[] = [$category, $files[$category], $existing];
        }

        foreach ($filesToStore as [$category, $file, $existing]) {
            $this->storeDocument($request, $application, $category, $file, $existing);
        }
        foreach ($files['lainnya'] ?? [] as $file) {
            $this->storeDocument($request, $application, 'lainnya', $file);
        }
        foreach ($files['lainnya_ulang'] ?? [] as $documentId => $file) {
            $existing = $application->documents()
                ->whereKey($documentId)
                ->where('category', 'lainnya')
                ->firstOrFail();
            abort_unless($existing->status === PpdbDocument::STATUS_REJECTED, 403);
            $this->storeDocument($request, $application, 'lainnya', $file, $existing);
        }

        return redirect()->route('pendaftar.dashboard')->with('status', 'Berkas berhasil diunggah dan tersimpan secara privat.');
    }

    public function submit(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === User::ROLE_PENDAFTAR, 403);
        $application = $this->application($request);
        abort_unless($application->status === PpdbApplication::STATUS_DRAFT, 403);

        Validator::make($application->toArray(), [
            'student_name' => ['required', 'string', 'max:255'],
            'nisn' => ['required', 'digits:10', 'unique:student_profiles,nisn'],
            'birth_place' => ['required', 'string'],
            'birth_date' => ['required', 'date', 'before:today'],
            'address' => ['required', 'string'],
            'previous_school' => ['required', 'string'],
            'guardian_name' => ['required', 'string'],
            'guardian_relation' => ['required', 'string'],
            'guardian_phone' => ['required', 'string'],
            'school_level' => ['required', 'in:SMP,SMA'],
            'major' => ['required_if:school_level,SMA', 'nullable', 'string'],
        ])->validate();

        $presentCategories = $application->documents()->pluck('category')->all();
        $missing = array_diff(self::REQUIRED_DOCUMENTS, $presentCategories);
        if ($missing !== []) {
            return back()->withErrors(['documents' => 'Lengkapi unggahan KK, akta kelahiran, ijazah/rapor, dan pas foto sebelum mengirim pendaftaran.']);
        }

        DB::transaction(function () use ($application): void {
            $application->update([
                'registration_number' => sprintf('PPDB-%s-%06d', now()->format('Y'), $application->id),
                'status' => PpdbApplication::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);
        });
        app(SchoolNotifier::class)->ppdbSubmitted($application);

        return redirect()->route('pendaftar.dashboard')->with('status', 'Pendaftaran berhasil dikirim. Nomor pendaftaran Anda: '.$application->registration_number);
    }

    public function downloadDocument(Request $request, PpdbDocument $document): mixed
    {
        $application = $document->application;
        abort_unless(
            $request->user()->role === User::ROLE_ADMIN || $application->user_id === $request->user()->id,
            403,
        );

        $disk = Storage::disk('local');
        abort_unless($disk->exists($document->path), 404);

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'downloaded',
            'record_type' => PpdbDocument::class,
            'record_id' => $document->id,
            'description' => 'Mengunduh berkas PPDB '.$document->category,
            'metadata' => ['application_id' => $application->id, 'category' => $document->category],
            'ip_address' => $request->ip(),
        ]);

        return $disk->download($document->path, $document->original_name, [
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function receipt(Request $request, PpdbReceiptPdf $receipt): Response
    {
        $application = $this->application($request);
        abort_unless($application->registration_number !== null, 404);

        return $receipt->response($application->load('user'));
    }

    private function application(Request $request): PpdbApplication
    {
        return PpdbApplication::query()->where('user_id', $request->user()->id)->firstOrFail();
    }

    private function storeDocument(
        Request $request,
        PpdbApplication $application,
        string $category,
        \Illuminate\Http\UploadedFile $file,
        ?PpdbDocument $existing = null,
    ): void {
        $mimeType = $file->getMimeType();
        if ($mimeType === null) {
            throw new RuntimeException('Tipe MIME berkas PPDB tidak dapat diverifikasi.');
        }

        $path = $file->store('ppdb/'.$application->id, 'local');
        if (! is_string($path)) {
            throw new RuntimeException('Berkas PPDB gagal disimpan ke penyimpanan privat.');
        }

        $oldPath = $existing?->path;
        $document = $existing ?? new PpdbDocument;
        $document->fill([
            'ppdb_application_id' => $application->id,
            'category' => $category,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'path' => $path,
            'mime_type' => $mimeType,
            'status' => PpdbDocument::STATUS_WAITING,
            'review_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->save();

        AuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => $existing ? 'reuploaded' : 'uploaded',
            'record_type' => PpdbDocument::class,
            'record_id' => $document->id,
            'description' => ($existing ? 'Mengunggah ulang' : 'Mengunggah').' berkas PPDB '.$category,
            'metadata' => ['application_id' => $application->id, 'category' => $category],
            'ip_address' => $request->ip(),
        ]);

        if ($oldPath !== null && ! Storage::disk('local')->delete($oldPath) && Storage::disk('local')->exists($oldPath)) {
            throw new RuntimeException('Berkas lama yang ditolak gagal dihapus dari penyimpanan privat.');
        }
    }
}
