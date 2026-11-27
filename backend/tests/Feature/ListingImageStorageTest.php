<?php

namespace Tests\Feature;

use App\Services\ListingImageStorage;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Image;
use Cloudinary\Cloudinary;
use GuzzleHttp\Psr7\Uri;
// no database needed
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ListingImageStorageTest extends TestCase
{
    public function test_cloudinary_upload_returns_public_id(): void
    {
        config(['filesystems.listing_images_disk' => 'cloudinary']);

        $uploadApi = Mockery::mock(UploadApi::class);
        $uploadApi->shouldReceive('upload')
            ->once()
            ->andReturn(new ApiResponse(['public_id' => 'listings/1/test-image'], []));

        $cloudinary = Mockery::mock(Cloudinary::class);
        $cloudinary->shouldReceive('uploadApi')->andReturn($uploadApi);

        $service = new ListingImageStorage($cloudinary);

        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $this->assertSame('listings/1/test-image', $service->store($file, 1));
    }

    public function test_cloudinary_url_generation(): void
    {
        config(['filesystems.listing_images_disk' => 'cloudinary']);

        $image = Mockery::mock(Image::class);
        $image->shouldReceive('toUrl')->once()->andReturn(new Uri('https://res.cloudinary.com/demo/image/upload/listings/1/test-image.jpg'));

        $cloudinary = Mockery::mock(Cloudinary::class);
        $cloudinary->shouldReceive('image')->with('listings/1/test-image')->andReturn($image);

        $service = new ListingImageStorage($cloudinary);

        $this->assertSame(
            'https://res.cloudinary.com/demo/image/upload/listings/1/test-image.jpg',
            $service->url('listings/1/test-image')
        );
    }

    public function test_cloudinary_deletion(): void
    {
        config(['filesystems.listing_images_disk' => 'cloudinary']);

        $uploadApi = Mockery::mock(UploadApi::class);
        $uploadApi->shouldReceive('destroy')
            ->once()
            ->with('listings/1/test-image')
            ->andReturn(new ApiResponse(['result' => 'ok'], []));

        $cloudinary = Mockery::mock(Cloudinary::class);
        $cloudinary->shouldReceive('uploadApi')->andReturn($uploadApi);

        $service = new ListingImageStorage($cloudinary);

        $this->assertTrue($service->delete('listings/1/test-image'));
    }

    public function test_local_public_disk_behavior_remains_unchanged(): void
    {
        config(['filesystems.listing_images_disk' => 'public']);

        Storage::fake('public');

        $service = new ListingImageStorage;

        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $path = $service->store($file, 1);

        $this->assertStringStartsWith('listings/1/', $path);
        Storage::disk('public')->assertExists($path);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
