<?php

return [
    'resources' => [
        'accounts' => [
            'labels' => [
                'singular' => 'WebDAV-Konto',
                'plural' => 'WebDAV-Konten',
            ],
            'navigation' => [
                'label' => 'WebDAV-Konten',
            ],
            'fields' => [
                'username' => 'Benutzername',
                'display_name' => 'Anzeigename',
                'password' => 'Passwort',
                'password_confirmation' => 'Passwort bestätigen',
                'new_password' => 'Neues Passwort',
                'new_password_confirmation' => 'Neues Passwort bestätigen',
                'user' => 'Benutzer',
                'enabled' => 'Aktiviert',
                'meta' => 'Metadaten',
                'meta_key' => 'Schlüssel',
                'meta_value' => 'Wert',
                'created_at' => 'Erstellt',
                'webdav_url' => 'WebDAV-URL',
            ],
            'placeholders' => [
                'display_name' => 'Verwendet den Benutzernamen, wenn leer gelassen',
            ],
            'empty' => [
                'display_name' => '-',
            ],
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'inactive' => 'Inaktiv',
            ],
            'actions' => [
                'generate_password' => 'Passwort generieren',
                'reset_password' => 'Passwort zurücksetzen',
                'enable_selected' => 'Ausgewählte aktivieren',
                'disable_selected' => 'Ausgewählte deaktivieren',
            ],
            'notifications' => [
                'password_reset' => 'Passwort erfolgreich zurückgesetzt',
                'webdav_url_copied' => 'WebDAV-URL kopiert',
            ],
        ],
    ],
];
