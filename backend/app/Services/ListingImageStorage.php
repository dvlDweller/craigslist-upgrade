<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ListingImageStorage
{
    public function __construct(private ?Cloudinary $cloudinary = null) {}

    public function disk(): string
    {
        return (string) config('filesystems.listing_images_disk', 'public');
    }

    public function isCloudinary(): bool
    {
        return $this->disk() === 'cloudinary';
    }

    public function store(UploadedFile $file, int $listingId): string
    {
        $disk = $this->disk();

        if ($disk === 'cloudinary') {
            $result = $this->cloudinary()->uploadApi()->upload($file->getRealPath(), [
                'folder' => "listings/{$listingId}",
                'resource_type' => 'image',
            ]);

            $publicId = $result['public_id'] ?? null;

            if (! is_string($publicId) || $publicId === '') {
                throw new \RuntimeException('Unable to store image.');
            }

            return $publicId;
        }

        $path = $file->store("listings/{$listingId}", $disk);

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Unable to store image.');
        }

        return $path;
    }

    public function url(string $path, ?string $disk = null): string
    {
        $disk = $disk ?? $this->disk();

        if ($disk === 'cloudinary') {
            return (string) $this->cloudinary()->image($path)->toUrl();
        }

        return Storage::disk($disk)->url($path);
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        $disk = $disk ?? $this->disk();

        if ($disk === 'cloudinary') {
            $result = $this->cloudinary()->uploadApi()->destroy($path);

            return in_array($result['result'] ?? null, ['ok', 'not found'], true);
        }

        return Storage::disk($disk)->delete($path);
    }

    private function cloudinary(): Cloudinary
    {
        if ($this->cloudinary !== null) {
            return $this->cloudinary;
        }

        $config = config('filesystems.disks.cloudinary', []);

        return new Cloudinary([
            'cloud_name' => $config['cloud_name'] ?? env('CLOUDINARY_CLOUD_NAME'),
            'api_key' => $config['api_key'] ?? env('CLOUDINARY_API_KEY'),
            'api_secret' => $config['api_secret'] ?? env('CLOUDINARY_API_SECRET'),
        ]);
    }
}
