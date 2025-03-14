<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\TrainingSession;
use App\Models\AttendanceRecord;
use App\Policies\TrainingSessionPolicy;
use App\Policies\AttendanceRecordPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        TrainingSession::class => TrainingSessionPolicy::class,
        AttendanceRecord::class => AttendanceRecordPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
} 