<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SoftDeleteUndoTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function patch_restore_recover_soft_deleted_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'name' => 'Para desfazer',
        ]);
        $task->delete();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/restore');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Para desfazer');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
    }

    #[Test]
    public function patch_restore_recover_soft_deleted_note(): void
    {
        $user = User::factory()->create();
        $note = Note::query()->create([
            'user_id' => $user->id,
            'title' => 'Nota apagável',
            'body' => 'Corpo',
        ]);
        $note->delete();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/note/'.$note->id.'/restore');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Nota apagável');

        $this->assertDatabaseHas('notes', ['id' => $note->id, 'deleted_at' => null]);
    }

    #[Test]
    public function restore_non_trashed_note_returns_422(): void
    {
        $user = User::factory()->create();
        $note = Note::query()->create([
            'user_id' => $user->id,
            'title' => 'Ativa',
            'body' => 'X',
        ]);
        $token = JWTAuth::fromUser($user);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/note/'.$note->id.'/restore')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                fn (string $msg) => str_contains($msg, 'Erro ao recuperar nota:')
                    && str_contains($msg, 'não está excluída')
            );
    }

    #[Test]
    public function restore_other_users_task_returns_404(): void
    {
        $owner = User::factory()->create();
        $peer = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $owner->id]);
        $task->delete();
        $token = JWTAuth::fromUser($peer);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/task/'.$task->id.'/restore')
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
