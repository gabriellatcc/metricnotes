<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Creates gabriellatccorrea@gmail.com and 10 completed tasks about basic database study
 * in the previous calendar week (Mon–Fri distribution: 1+1+1+3+4).
 */
class GabriellaDatabaseStudyTaskSeeder extends Seeder
{
    private const USER_EMAIL = 'gabriellatccorrea@gmail.com';

    private const USER_PASSWORD = 'senha123$';

    /** @var list<array{day: int, name: string, description: string, due_hour: int, due_minute: int}> */
    private const TASK_SPECS = [
        ['day' => 0, 'name' => 'Introdução ao banco de dados relacional', 'description' => 'Conceitos de tabela, linha, coluna e SGBD.', 'due_hour' => 14, 'due_minute' => 0],
        ['day' => 1, 'name' => 'Modelo entidade-relacionamento (ER)', 'description' => 'Entidades, atributos, relacionamentos e cardinalidade.', 'due_hour' => 13, 'due_minute' => 30],
        ['day' => 2, 'name' => 'Comandos SELECT e filtros com WHERE', 'description' => 'Consultas simples, operadores de comparação e ordenação.', 'due_hour' => 15, 'due_minute' => 0],
        ['day' => 3, 'name' => 'Chaves primárias e estrangeiras', 'description' => 'Integridade referencial e relacionamentos entre tabelas.', 'due_hour' => 13, 'due_minute' => 0],
        ['day' => 3, 'name' => 'JOINs básicos (INNER e LEFT)', 'description' => 'Combinar dados de múltiplas tabelas em uma consulta.', 'due_hour' => 14, 'due_minute' => 15],
        ['day' => 3, 'name' => 'INSERT, UPDATE e DELETE', 'description' => 'Manipulação de dados com DML.', 'due_hour' => 10, 'due_minute' => 30],
        ['day' => 4, 'name' => 'Funções de agregação e GROUP BY', 'description' => 'COUNT, SUM, AVG, MIN, MAX e agrupamento de resultados.', 'due_hour' => 14, 'due_minute' => 0],
        ['day' => 4, 'name' => 'Normalização até 3FN', 'description' => '1FN, 2FN e 3FN para reduzir redundância.', 'due_hour' => 13, 'due_minute' => 45],
        ['day' => 4, 'name' => 'Índices e performance básica', 'description' => 'Quando criar índices e impacto em consultas.', 'due_hour' => 15, 'due_minute' => 0],
        ['day' => 4, 'name' => 'Revisão geral de SQL básico', 'description' => 'Consolidar SELECT, JOINs, agregações e integridade.', 'due_hour' => 17, 'due_minute' => 0],
    ];

    public function run(): void
    {
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeek();

        $user = User::query()->updateOrCreate(
            ['email' => self::USER_EMAIL],
            [
                'name' => 'Gabriella',
                'password' => self::USER_PASSWORD,
                'is_admin' => false,
            ]
        );

        $estudoTip = $this->estudoTip($user->id);

        foreach (self::TASK_SPECS as $spec) {
            $taskDay = $weekStart->copy()->addDays($spec['day']);

            $dueAt = $taskDay->copy()->setTime($spec['due_hour'], $spec['due_minute'], 0);
            $createdAt = $taskDay->copy()->setTime(8, 30, 0);
            $completedAt = $dueAt->copy()->addMinutes(random_int(0, 25));

            if ($completedAt->lessThanOrEqualTo($createdAt)) {
                $completedAt = $createdAt->copy()->addHours(2);
            }

            $task = Task::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $spec['name'],
                ],
                [
                    'description' => $spec['description'],
                    'status' => 'completed',
                    'priority' => random_int(2, 4),
                    'original_due_date' => $dueAt,
                    'current_due_date' => $dueAt,
                    'postponed_count' => 0,
                    'postponed_date_1' => null,
                    'postponed_date_2' => null,
                    'postponed_date_3' => null,
                    'is_being_viewed' => false,
                    'last_viewed_at' => null,
                    'completed_at' => $completedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $completedAt,
                ]
            );

            $task->tips()->sync([$estudoTip->id]);
        }

        $dogWalkDue = Carbon::create(2026, 6, 4, 18, 0, 0);
        $dogWalk = Task::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Passear com o cachorro',
            ],
            [
                'description' => 'Caminhada no parque com o Thor.',
                'status' => 'pending',
                'priority' => 3,
                'original_due_date' => $dogWalkDue,
                'current_due_date' => $dogWalkDue,
                'postponed_count' => 0,
                'postponed_date_1' => null,
                'postponed_date_2' => null,
                'postponed_date_3' => null,
                'is_being_viewed' => false,
                'last_viewed_at' => null,
                'completed_at' => null,
            ]
        );
        $dogWalk->tips()->sync([$estudoTip->id]);

        $budgetTask = Task::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Criar orçamento — landing page NovaVerde Alimentos',
            ],
            [
                'description' => 'Elaborar proposta comercial para landing page da NovaVerde Alimentos (empresa fictícia de produtos orgânicos): escopo, prazos, investimento em design/desenvolvimento e manutenção.',
                'status' => 'pending',
                'priority' => 4,
                'original_due_date' => null,
                'current_due_date' => null,
                'postponed_count' => 0,
                'postponed_date_1' => null,
                'postponed_date_2' => null,
                'postponed_date_3' => null,
                'is_being_viewed' => false,
                'last_viewed_at' => null,
                'completed_at' => null,
            ]
        );
        $budgetTask->tips()->sync([]);

        if ($this->command !== null) {
            $this->command->info('Usuário garantido: '.self::USER_EMAIL.' (senha: '.self::USER_PASSWORD.')');
            $this->command->info('10 tarefas concluídas (tag Estudo) + passeio 04/06 18h + orçamento landing page.');
        }
    }

    private function estudoTip(string $userId): Tip
    {
        return Tip::query()->firstOrCreate(
            ['user_id' => $userId, 'name' => 'Estudo'],
            ['color' => '#00FF00']
        );
    }
}
