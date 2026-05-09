<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use N3XT0R\LaravelWebdavServerFilament\Notifications\WebDavAccountCreatedNotification;

trait NotifiesAccountCreation
{
    private function notifyLinkedUser(Model $record, string $password): void
    {
        if (! (bool) config('laravel-webdav-server-filament.notifications.enabled', true)) {
            return;
        }

        $notifiable = $record->getAttribute('user') ?? $record->user;

        if (! is_object($notifiable) || ! method_exists($notifiable, 'notify')) {
            return;
        }

        $createdAt = $record->getAttribute('created_at');

        $notifiable->notify(new WebDavAccountCreatedNotification(
            username: (string) $record->getAttribute('username'),
            password: $password,
            createdAt: $createdAt instanceof CarbonInterface ? $createdAt : now(),
            userName: $this->readNotifiableString($notifiable, 'name'),
            userEmail: $this->readNotifiableString($notifiable, 'email'),
        ));
    }

    private function readNotifiableString(object $notifiable, string $key): ?string
    {
        $value = method_exists($notifiable, 'getAttribute')
            ? $notifiable->getAttribute($key)
            : ($notifiable->{$key} ?? null);

        return is_scalar($value) ? (string) $value : null;
    }
}
