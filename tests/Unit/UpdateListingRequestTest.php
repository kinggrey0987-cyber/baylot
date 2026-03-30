<?php

namespace Tests\Unit;

use App\Http\Requests\UpdateListingRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpdateListingRequestTest extends TestCase
{
    public function test_delete_images_accepts_uuid_values(): void
    {
        $request = new UpdateListingRequest();
        $uuid = (string) Str::uuid();

        $validator = Validator::make(
            ['delete_images' => [$uuid]],
            [
                'delete_images' => $request->rules()['delete_images'],
                'delete_images.*' => $request->rules()['delete_images.*'],
            ]
        );

        $this->assertFalse($validator->fails(), $validator->errors()->first('delete_images.0'));
    }

    public function test_delete_images_rejects_non_uuid_values(): void
    {
        $request = new UpdateListingRequest();

        $validator = Validator::make(
            ['delete_images' => ['123']],
            [
                'delete_images' => $request->rules()['delete_images'],
                'delete_images.*' => $request->rules()['delete_images.*'],
            ]
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('delete_images.0', $validator->errors()->messages());
    }
}