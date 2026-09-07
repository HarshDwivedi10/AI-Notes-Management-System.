<?php

namespace App\Providers;

use App\Repositories\EloquentNoteRepository;
use App\Repositories\NoteRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            NoteRepositoryInterface::class,
            EloquentNoteRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
