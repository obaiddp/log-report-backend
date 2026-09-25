<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\Department;
use App\Models\Inspection;
use App\Models\IssueType;
use App\Models\ItemType;
use App\Models\SupportLog;
use App\Models\TechnicalPersonnel;
use App\Models\User;
use App\Policies\AssetPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\InspectionPolicy;
use App\Policies\IssueTypePolicy;
use App\Policies\ItemTypePolicy;
use App\Policies\SupportLogPolicy;
use App\Policies\TechnicalPersonnelPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(IssueType::class, IssueTypePolicy::class);
        Gate::policy(ItemType::class, ItemTypePolicy::class);
        Gate::policy(Asset::class, AssetPolicy::class);
        Gate::policy(Inspection::class, InspectionPolicy::class);
        Gate::policy(TechnicalPersonnel::class, TechnicalPersonnelPolicy::class);
        Gate::policy(SupportLog::class, SupportLogPolicy::class);

        Gate::define('viewAnalytics', static fn (User $user): bool => $user->isAdmin());
        Gate::define('manageDirectory', static fn (User $user): bool => $user->isAdmin());
        Gate::define('manageConfig', static fn (User $user): bool => $user->isAdmin());

        RateLimiter::for('login', static function (Request $request): Limit {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });
    }
}
