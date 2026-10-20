<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

class UpdateProfilePhoto extends Component
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $photo;

    public function updatedPhoto(): void
    {
        $this->validateOnly('photo', [
            'photo' => $this->photoRules(),
        ]);
    }

    public function savePhoto(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        $this->validate([
            'photo' => $this->photoRules(),
        ]);

        $disk = (string) config('nexus.profile_photo.disk', 'public');
        $directory = 'profile-photos/'.$user->id;
        // O crop do cliente exporta JPEG; mantém extensão estável para o ficheiro final.
        $extension = strtolower((string) $this->photo->getClientOriginalExtension()) ?: 'jpg';
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        $filename = 'avatar-'.Str::lower(Str::random(8)).'.'.$extension;

        try {
            Storage::disk($disk)->makeDirectory($directory);

            if ($user->profile_photo_path) {
                Storage::disk($disk)->delete($user->profile_photo_path);
            }

            $path = $this->photo->storeAs($directory, $filename, $disk);

            if (! $path || ! Storage::disk($disk)->exists($path)) {
                throw new \RuntimeException('Falha ao gravar a foto no disco '.$disk.'.');
            }

            $user->forceFill(['profile_photo_path' => $path])->save();
        } catch (Throwable $e) {
            Log::error('profile_photo.upload_failed', [
                'user_id' => $user->id,
                'disk' => $disk,
                'root' => config('filesystems.disks.'.$disk.'.root'),
                'message' => $e->getMessage(),
            ]);

            $this->addError('photo', __('Não foi possível guardar a foto. Verifique permissões de storage em produção.'));

            return false;
        }

        $this->reset('photo');
        $user->refresh();

        $this->dispatch('profile-photo-updated', url: $user->profilePhotoUrl());
        session()->flash('photo_status', __('Foto de perfil actualizada.'));

        return true;
    }

    public function removePhoto(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $disk = (string) config('nexus.profile_photo.disk', 'public');

        if ($user->profile_photo_path) {
            Storage::disk($disk)->delete($user->profile_photo_path);
        }

        $user->forceFill(['profile_photo_path' => null])->save();

        $this->reset('photo');
        $this->dispatch('profile-photo-updated', url: null);
        session()->flash('photo_status', __('Foto de perfil removida.'));
    }

    /**
     * @return array<int, mixed>
     */
    private function photoRules(): array
    {
        $maxKb = (int) config('nexus.profile_photo.max_kb', 2048);

        // Evita rule "image" (depende de extensões) — mimes basta em shared hosting.
        return [
            'required',
            'file',
            'mimes:jpeg,jpg,png,webp',
            'max:'.$maxKb,
        ];
    }

    public function render()
    {
        return view('livewire.profile.update-profile-photo', [
            'user' => Auth::user()?->fresh(),
        ]);
    }
}
