<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolFacilityRequest;
use App\Http\Requests\SchoolGalleryRequest;
use App\Http\Requests\UpdateSchoolProfileRequest;
use App\Models\AuditLog;
use App\Models\SchoolFacility;
use App\Models\SchoolGallery;
use App\Models\SchoolProfileSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class AdminSchoolContentController extends Controller
{
    public function editProfile(): View
    {
        $setting = SchoolProfileSetting::query()->findOrFail(1);
        $this->authorize('update', $setting);

        return view('admin.school-profile.edit', ['setting' => $setting]);
    }

    public function updateProfile(UpdateSchoolProfileRequest $request): RedirectResponse
    {
        $setting = SchoolProfileSetting::query()->findOrFail(1);
        $data = $request->validated();
        $before = $setting->getAttributes();
        DB::transaction(function () use ($setting, $data, $before): void {
            $setting->update($data);
            $this->audit('updated', $setting, 'Memperbarui profil sekolah', [
                'before' => ['name' => $before['name']],
                'after' => ['name' => $setting->name],
            ]);
        });

        return back()->with('status', 'Profil sekolah berhasil diperbarui.');
    }

    public function facilities(): View
    {
        $this->authorize('viewAny', SchoolFacility::class);

        return view('admin.school-profile.facilities', [
            'facilities' => SchoolFacility::query()->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function createFacility(): View
    {
        $this->authorize('create', SchoolFacility::class);

        return view('admin.school-profile.facility-form', ['facility' => null]);
    }

    public function editFacility(SchoolFacility $facility): View
    {
        $this->authorize('update', $facility);

        return view('admin.school-profile.facility-form', compact('facility'));
    }

    public function storeFacility(SchoolFacilityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $photo = $data['photo'] ?? null;
        unset($data['photo']);
        if ($photo instanceof UploadedFile) {
            $data['photo_path'] = $this->storeImage($photo, 'facilities');
        }

        $facility = DB::transaction(function () use ($data): SchoolFacility {
            $facility = SchoolFacility::query()->create($data);
            $this->audit('created', $facility, 'Menambahkan fasilitas sekolah');

            return $facility;
        });

        return redirect()->route('admin.school-content.facilities')->with('status', 'Fasilitas berhasil ditambahkan.');
    }

    public function updateFacility(SchoolFacilityRequest $request, SchoolFacility $facility): RedirectResponse
    {
        $data = $request->validated();
        $photo = $data['photo'] ?? null;
        unset($data['photo']);
        $oldPhoto = $facility->photo_path;
        if ($photo instanceof UploadedFile) {
            $data['photo_path'] = $this->storeImage($photo, 'facilities');
        }
        DB::transaction(function () use ($facility, $data): void {
            $facility->update($data);
            $this->audit('updated', $facility, 'Memperbarui fasilitas sekolah');
        });
        if ($photo instanceof UploadedFile && $oldPhoto) {
            $this->deleteImage($oldPhoto);
        }

        return redirect()->route('admin.school-content.facilities')->with('status', 'Fasilitas berhasil diperbarui.');
    }

    public function destroyFacility(SchoolFacility $facility): RedirectResponse
    {
        $this->authorize('delete', $facility);
        $path = $facility->photo_path;
        DB::transaction(function () use ($facility): void {
            $this->audit('deleted', $facility, 'Menghapus fasilitas sekolah');
            $facility->delete();
        });
        if ($path) {
            $this->deleteImage($path);
        }

        return back()->with('status', 'Fasilitas berhasil dihapus.');
    }

    public function gallery(): View
    {
        $this->authorize('viewAny', SchoolGallery::class);

        return view('admin.school-profile.gallery', [
            'galleries' => SchoolGallery::query()->latest()->paginate(20),
        ]);
    }

    public function storeGallery(SchoolGalleryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['photo_path'] = $this->storeImage($data['photo'], 'school-gallery');
        unset($data['photo']);
        DB::transaction(function () use ($data): void {
            $gallery = SchoolGallery::query()->create($data);
            $this->audit('created', $gallery, 'Mengunggah foto galeri sekolah');
        });

        return back()->with('status', 'Foto galeri berhasil diunggah.');
    }

    public function destroyGallery(SchoolGallery $gallery): RedirectResponse
    {
        $this->authorize('delete', $gallery);
        $path = $gallery->photo_path;
        DB::transaction(function () use ($gallery): void {
            $this->audit('deleted', $gallery, 'Menghapus foto galeri sekolah');
            $gallery->delete();
        });
        $this->deleteImage($path);

        return back()->with('status', 'Foto galeri berhasil dihapus.');
    }

    private function storeImage(UploadedFile $image, string $directory): string
    {
        $path = $image->storePublicly($directory, 'public');
        if (! is_string($path)) {
            throw new RuntimeException('Gagal menyimpan berkas gambar sekolah.');
        }

        return $path;
    }

    private function deleteImage(string $path): void
    {
        $disk = Storage::disk('public');
        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Gagal menghapus berkas gambar sekolah.');
        }
    }

    private function audit(string $action, SchoolProfileSetting|SchoolFacility|SchoolGallery $record, string $description, ?array $metadata = null): void
    {
        AuditLog::query()->create([
            'user_id' => request()->user()->id,
            'action' => $action,
            'record_type' => class_basename($record),
            'record_id' => $record->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }
}
