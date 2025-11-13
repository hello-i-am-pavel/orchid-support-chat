<?php

declare(strict_types=1);

namespace Hiap\OrchidSupportChat;

use Hiap\OrchidSupportChat\Models\SupportTicket;
use Hiap\OrchidSupportChat\Policies\SupportTicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;

/**
 * Class HiapOrchidInfiniteScrollServiceProvider
 * @package Hiap\OrchidInfiniteScroll
 */
class HiapOrchidSupportChatServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);

        $this->app->afterResolving(Dashboard::class, function (Dashboard $dashboard) {
            $dashboard->registerPermissions(
                ItemPermission::group(__('Support'))
                    ->addPermission('support.tickets.manage', __('Manage support tickets'))
            );
        });

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'hiap-orchid-support');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'hiap-orchid-support');
        $this->loadJsonTranslationsFrom(__DIR__ . '/../resources/lang');

        $this->loadMigrationsFrom(__DIR__ . '/../migrations');

        if (file_exists(__DIR__ . '/../routes/platform.php')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/platform.php');
        }

        $this->publishes([
            __DIR__ . '/../config/support_tickets.php' => config_path('support_tickets.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../config/support_tickets.php', 'support_tickets');

        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/orchid-support-chat'),
        ], 'public');
    }
}
