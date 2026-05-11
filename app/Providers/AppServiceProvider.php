<?php

namespace App\Providers;

use App\Models\Note;
use App\Models\Task;
use App\Models\Tip;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\NotePolicy;
use App\Policies\TaskPolicy;
use App\Policies\TipPolicy;
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
        Gate::policy(Tip::class, TipPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
        User::observe(UserObserver::class);
    }
}
