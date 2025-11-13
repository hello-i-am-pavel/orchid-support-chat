
# Orchid Support Chat

Чат поддержки для Laravel Orchid. Позволяет пользователям создавать обращения и переписываться с агентами поддержки прямо в админ-панели Orchid. Совместимо с Laravel 12+ и Orchid 14+.

## 📦 Установка

```bash
composer require hello-i-am-pavel/orchid-support-chat

php artisan migrate

php artisan vendor:publish --provider="Hiap\OrchidSupportChat\HiapOrchidSupportChatServiceProvider" --tag=public

```

## 🛠 Подключение в меню Orchid

В `app/Orchid/PlatformProvider.php` добавить пункты меню:

```php
use Hiap\OrchidSupportChat\Models\SupportTicket;
use Orchid\Support\Color;
use Orchid\Screen\Actions\Menu;

Menu::make(__('Чат с поддержкой'))
    ->icon('bs.chat')
    ->title(__('Поддержка'))
    ->route('platform.hiap.support-tickets-list-screen'),

Menu::make(__('Все обращения'))
    ->icon('bs.chat')
    ->permission('support.tickets.manage')
    ->badge(
        fn() => SupportTicket::getActiveCount(),
        Color::DANGER
    )
    ->route('platform.hiap.support-tickets-admin'),
```
## ❓FAQ
- Как назначить права? — В разделе Роли Orchid добавьте право `support.tickets.manage` нужной роли и назначьте её пользователям.
- Где подключить пункты меню? — См. раздел «Подключение в меню Orchid» выше.
- Не отображается иконка агента — выполните публикацию ассетов (см. раздел «Публичные ассеты»).

## 📄 Лицензия
MIT

