<?php

namespace App\Providers;

use App\Models\Note;
use App\Models\NoteType;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\NotePolicy;
use App\Policies\NoteTypePolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskTypePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(TaskType::class, TaskTypePolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        Gate::policy(NoteType::class, NoteTypePolicy::class);

        User::observe(UserObserver::class);
    }
}
