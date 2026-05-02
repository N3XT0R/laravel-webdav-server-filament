<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WebDavAccountCreatedNotification extends Notification
{
    /**
     * Create the notification sent after a WebDAV account is created.
     *
     * @param  string  $username  WebDAV account username.
     * @param  string|null  $password  Plain-text WebDAV password when it is available.
     * @param  CarbonInterface  $createdAt  Timestamp at which the WebDAV account was created.
     * @param  string|null  $userName  Linked user's display name, if available.
     * @param  string|null  $userEmail  Linked user's email address, if available.
     */
    public function __construct(
        private readonly string $username,
        private readonly ?string $password,
        private readonly CarbonInterface $createdAt,
        private readonly ?string $userName,
        private readonly ?string $userEmail,
    ) {
    }

    /**
     * Return the delivery channels for the account created notification.
     *
     * @param  object  $notifiable  Authenticatable notifiable receiving the account details.
     * @return list<string> Notification channels that should receive the message.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation for the account created notification.
     *
     * @param  object  $notifiable  Authenticatable notifiable receiving the account details.
     * @return MailMessage Mail channel message containing the created WebDAV account details.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject(__('webdav-server-filament::webdav-server-filament.notifications.account_created.subject'))
            ->greeting(__('webdav-server-filament::webdav-server-filament.notifications.account_created.greeting'))
            ->line(__('webdav-server-filament::webdav-server-filament.notifications.account_created.account', [
                'username' => $this->username,
            ]))
            ->line(__('webdav-server-filament::webdav-server-filament.notifications.account_created.user', [
                'user' => $this->formatLinkedUser(),
            ]))
            ->line(__('webdav-server-filament::webdav-server-filament.notifications.account_created.created_at', [
                'created_at' => $this->createdAt->toDateTimeString(),
            ]));

        if ($this->password !== null && $this->password !== '') {
            $message->line(__('webdav-server-filament::webdav-server-filament.notifications.account_created.password', [
                'password' => $this->password,
            ]));
        }

        return $message->line(__('webdav-server-filament::webdav-server-filament.notifications.account_created.security'));
    }

    /**
     * Return the WebDAV username included in the notification.
     *
     * @return string WebDAV account username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Return the new WebDAV password included in the notification.
     *
     * @return string|null Plain-text WebDAV password, or `null` when unavailable.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Return the timestamp included in the notification.
     *
     * @return CarbonInterface Account creation timestamp.
     */
    public function getCreatedAt(): CarbonInterface
    {
        return $this->createdAt;
    }

    private function formatLinkedUser(): string
    {
        if ($this->userName !== null && $this->userEmail !== null) {
            return "{$this->userName} <{$this->userEmail}>";
        }

        return $this->userName ?? $this->userEmail ?? '-';
    }
}
