<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TaskIndexTrashedQueryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function task_index_excludes_trashed_by_default(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['name' => 'Ativa']);
        $removed = Task::factory()->for($user)->create(['name' => 'Apagada']);
        $removed->delete();

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/task?per_page=50')
            ->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($response->json('data.items'))->pluck('name');

        $this->assertTrue($names->contains('Ativa'));
        $this->assertFalse($names->contains('Apagada'));
    }

    #[Test]
    public function task_index_only_trashed_lists_soft_deleted_tasks(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['name' => 'Ativa']);

        $first = Task::factory()->for($user)->create(['name' => 'Del A']);
        $first->delete();
        $second = Task::factory()->for($user)->create(['name' => 'Del B']);
        $second->delete();

        $token = JWTAuth::fromUser($user);

        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/task?only_trashed=1&per_page=50')
            ->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($res->json('data.items'))->pluck('name');
        $this->assertSame(2, $names->count());
        $this->assertFalse($names->contains('Ativa'));
        $this->assertTrue($names->contains('Del A'));
        $this->assertTrue($names->contains('Del B'));
    }
}
