<?php

declare(strict_types=1);

use Hiap\OrchidSupportChat\Orchid\Screens\Support\SupportTicketChatScreen;
use Hiap\OrchidSupportChat\Orchid\Screens\Support\SupportTicketCreateScreen;
use Hiap\OrchidSupportChat\Orchid\Screens\Support\SupportTicketsAdminScreen;
use Hiap\OrchidSupportChat\Orchid\Screens\Support\SupportTicketsListScreen;
use Illuminate\Support\Facades\Route;

Route::domain((string)config('platform.domain'))
    ->middleware(config('platform.middleware.private'))
    ->prefix(config('platform.prefix'))
    ->group(static function () {
        Route::screen('contact/support-tickets-list-screen', SupportTicketsListScreen::class)
            ->name('platform.hiap.support-tickets-list-screen');

        Route::screen('contact/support-ticket/{ticket}', SupportTicketChatScreen::class)
            ->name('platform.hiap.support-ticket.chat');

        Route::screen('contact/support-ticket-create', SupportTicketCreateScreen::class)
            ->name('platform.hiap.support-ticket.create');

        Route::screen('support/all-tickets', SupportTicketsAdminScreen::class)
            ->name('platform.hiap.support-tickets-admin');
    });
