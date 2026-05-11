<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NoteStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_note_with_post_json(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/note', [
                'title' => 'Ideia de produto',
                'body' => 'Descrição',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_note_with_raw_json_and_form_content_type(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $payload = json_encode([
            'title' => 'From raw body',
            'body' => 'B',
        ]);

        $server = $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'CONTENT_LENGTH' => (string) mb_strlen($payload, '8bit'),
        ]);

        $response = $this->call('POST', '/api/note', [], [], [], $server, $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_note_with_utf8_bom_prefixed_json(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $payload = "\xEF\xBB\xBF".json_encode([
            'title' => 'Com BOM',
            'body' => 'x',
        ]);

        $server = $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'CONTENT_LENGTH' => (string) mb_strlen($payload, '8bit'),
        ]);

        $response = $this->call('POST', '/api/note', [], [], [], $server, $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
