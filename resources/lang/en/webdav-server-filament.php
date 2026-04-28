<?php

return [
    'resources' => [
        'accounts' => [
            'labels' => [
                'singular' => 'WebDAV account',
                'plural' => 'WebDAV accounts',
            ],
            'navigation' => [
                'label' => 'WebDAV accounts',
            ],
            'fields' => [
                'username' => 'Username',
                'display_name' => 'Display name',
                'password' => 'Password',
                'password_confirmation' => 'Confirm password',
                'new_password' => 'New password',
                'new_password_confirmation' => 'Confirm new password',
                'user' => 'User',
                'enabled' => 'Enabled',
                'meta' => 'Meta',
                'meta_key' => 'Key',
                'meta_value' => 'Value',
                'created_at' => 'Created',
            ],
            'placeholders' => [
                'display_name' => 'Falls back to username when left empty',
            ],
            'empty' => [
                'display_name' => '-',
            ],
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'actions' => [
                'generate_password' => 'Generate password',
                'reset_password' => 'Reset password',
                'enable_selected' => 'Enable selected',
                'disable_selected' => 'Disable selected',
            ],
            'notifications' => [
                'password_reset' => 'Password reset successfully',
            ],
        ],
    ],
];
