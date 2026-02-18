<?php

namespace App\Providers;

use App\Contracts\OnboardingServiceInterface;
use App\Contracts\PartnerInviteServiceInterface;
use App\Contracts\WeddingSettingsServiceInterface;
use App\Contracts\Site\AccessTokenServiceInterface;
use App\Contracts\Media\AlbumManagementServiceInterface;
use App\Contracts\Media\BatchUploadServiceInterface;
use App\Contracts\Media\QuotaTrackingServiceInterface;
use App\Http\Responses\Auth\RegistrationResponse;
use App\Services\OnboardingService;
use App\Services\PartnerInviteService;
use App\Services\WeddingSettingsService;
use App\Services\Media\AlbumManagementService;
use App\Services\Media\BatchUploadService;
use App\Services\Media\QuotaTrackingService;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;
use Illuminate\Auth\Events\Logout;
use App\Contracts\Site\ContentSanitizerServiceInterface;
use App\Contracts\Site\MediaUploadServiceInterface;
use App\Contracts\Site\PlaceholderServiceInterface;
use App\Contracts\Site\SiteBuilderServiceInterface;
use App\Contracts\Site\SiteValidatorServiceInterface;
use App\Contracts\Site\SiteVersionServiceInterface;
use App\Contracts\Site\SlugGeneratorServiceInterface;
use App\Events\SitePublished;
use App\Listeners\ClearOnboardingSession;
use App\Listeners\SendSitePublishedNotification;
use App\Models\SiteLayout;
use App\Models\Task;
use App\Models\TaskBudget;
use App\Models\Wedding;
use App\Observers\WeddingObserver;
use App\Observers\TaskBudgetObserver;
use App\Observers\TaskObserver;
use App\Policies\SiteLayoutPolicy;
use App\Services\PermissionService;
use App\Services\Site\AccessTokenService;
use App\Services\Site\ContentSanitizerService;
use App\Services\Site\MediaUploadService;
use App\Services\Site\PlaceholderService;
use App\Services\Site\SiteBuilderService;
use App\Services\Site\SiteValidatorService;
use App\Services\Site\SiteVersionService;
use App\Services\Site\SlugGeneratorService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PermissionService::class, function () {
            return new PermissionService();
        });

        $this->app->bind(PlaceholderServiceInterface::class, PlaceholderService::class);
        $this->app->bind(MediaUploadServiceInterface::class, MediaUploadService::class);
        $this->app->bind(SiteVersionServiceInterface::class, SiteVersionService::class);
        $this->app->bind(ContentSanitizerServiceInterface::class, ContentSanitizerService::class);
        $this->app->bind(SlugGeneratorServiceInterface::class, SlugGeneratorService::class);
        $this->app->bind(SiteBuilderServiceInterface::class, SiteBuilderService::class);
        $this->app->bind(SiteValidatorServiceInterface::class, SiteValidatorService::class);
        $this->app->bind(AccessTokenServiceInterface::class, AccessTokenService::class);
        
        // Onboarding services
        $this->app->bind(OnboardingServiceInterface::class, OnboardingService::class);
        $this->app->bind(PartnerInviteServiceInterface::class, PartnerInviteService::class);
        $this->app->bind(WeddingSettingsServiceInterface::class, WeddingSettingsService::class);
        
        // Media services
        $this->app->bind(AlbumManagementServiceInterface::class, AlbumManagementService::class);
        $this->app->bind(QuotaTrackingServiceInterface::class, QuotaTrackingService::class);
        $this->app->bind(BatchUploadServiceInterface::class, BatchUploadService::class);
        
        // Custom auth responses
        $this->app->bind(RegistrationResponseContract::class, RegistrationResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('filament-login', function (Request $request): array {
            $email = Str::lower((string) $request->input('email'));
            $identifier = $email !== '' ? $email : 'guest';

            return [
                Limit::perMinute(5)->by($identifier.'|'.$request->ip()),
                Limit::perMinute(20)->by((string) $request->ip()),
            ];
        });

        // Register Livewire components
        \Livewire\Livewire::component('media-gallery-picker', \App\Livewire\MediaGalleryPicker::class);
        
        // Register model observers
        Wedding::observe(WeddingObserver::class);
        Task::observe(TaskObserver::class);
        TaskBudget::observe(TaskBudgetObserver::class);

        // Register policies
        Gate::policy(SiteLayout::class, SiteLayoutPolicy::class);
        Gate::policy(\App\Models\GuestHousehold::class, \App\Policies\GuestHouseholdPolicy::class);
        Gate::policy(\App\Models\Guest::class, \App\Policies\GuestPolicy::class);
        Gate::policy(\App\Models\GuestEvent::class, \App\Policies\GuestEventPolicy::class);
        Gate::policy(\App\Models\GuestRsvp::class, \App\Policies\GuestRsvpPolicy::class);
        Gate::policy(\App\Models\GuestInvite::class, \App\Policies\GuestInvitePolicy::class);
        Gate::policy(\App\Models\GuestCheckin::class, \App\Policies\GuestCheckinPolicy::class);
        Gate::policy(\App\Models\GuestMessage::class, \App\Policies\GuestMessagePolicy::class);
        Gate::policy(\App\Models\GuestAuditLog::class, \App\Policies\GuestAuditLogPolicy::class);

        // Register event listeners
        Event::listen(
            SitePublished::class,
            SendSitePublishedNotification::class
        );
        
        Event::listen(
            Logout::class,
            ClearOnboardingSession::class
        );
    }
}
