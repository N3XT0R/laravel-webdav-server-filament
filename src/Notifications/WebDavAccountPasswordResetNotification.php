<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServerFilament\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class WebDavAccountPasswordResetNotification extends Notification
{
    /**
     * Create the notification sent after a WebDAV account password reset.
     *
     * @param  string  $username  WebDAV account username whose password was reset.
     * @param  string  $password  New plain-text WebDAV password to deliver to the account owner.
     */
    public function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {
    }

    /**
     * Return the delivery channels for the password reset notification.
     *
     * @param  object  $notifiable  Authenticatable notifiable receiving the reset password.
     * @return list<string> Notification channels that should receive the message.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation for the password reset notification.
     *
     * @param  object  $notifiable  Authenticatable notifiable receiving the reset password.
     * @return MailMessage Mail channel message containing the new WebDAV password.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('webdav-server-filament::webdav-server-filament.notifications.password_reset.subject'))
            ->greeting(__('webdav-server-filament::webdav-server-filament.notifications.password_reset.greeting'))
            ->line(__('webdav-server-filament::webdav-server-filament.notifications.password_reset.account', [
                'username' => $this->username,
            ]))
            ->line(__('webdav-server-filament::webdav-server-filament.notifications.password_reset.password', [
                'password' => $this->password,
            ]))
            ->line(__('webdav-server-filament::webdav-server-filament.notifications.password_reset.security'));
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
     * @return string New plain-text WebDAV password.
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}
