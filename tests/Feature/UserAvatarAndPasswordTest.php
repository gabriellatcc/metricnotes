<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserAvatarAndPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_avatar_stores_file_and_returns_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $file = UploadedFile::fake()->image('photo.jpg', 500, 500);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/user/'.$user->id.'/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.avatar_url', fn ($url) => is_string($url) && str_contains($url, 'avatars/'.$user->id));

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_upload_avatar_accepts_png_within_max_dimensions(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $file = UploadedFile::fake()->image('photo.png', 400, 300);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/user/'.$user->id.'/avatar', [
                'avatar' => $file,
            ]);

        $response->assertOk()->assertJsonPath('success', true);
    }

    public function test_upload_avatar_rejects_dimensions_above_500(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $file = UploadedFile::fake()->image('huge.jpg', 501, 400);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/user/'.$user->id.'/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_upload_avatar_rejects_non_jpeg_png_mime(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $file = UploadedFile::fake()->create('doc.gif', 100, 'image/gif');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->post('/api/user/'.$user->id.'/avatar', [
                'avatar' => $file,
            ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_change_password_with_valid_current_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-secret-1'),
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/user/'.$user->id.'/password', [
                'current_password' => 'old-secret-1',
                'password' => 'new-secret-2',
                'password_confirmation' => 'new-secret-2',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email);

        $this->assertTrue(Hash::check('new-secret-2', $user->refresh()->password));
    }

    public function test_change_password_with_wrong_current_returns_422(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('right-pass'),
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/user/'.$user->id.'/password', [
                'current_password' => 'wrong-pass',
                'password' => 'new-secret-2',
                'password_confirmation' => 'new-secret-2',
            ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }
}
