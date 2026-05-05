<?php

// config for N3XT0R/LaravelWebdavServerFilament
return [
    'notifications' => [
        'enabled' => true,
    ],

    'password' => [
        'min_length'         => 16,   // minimum password length; also used for generated passwords
        'require_mixed_case' => true, // require both upper- and lowercase letters
        'require_numbers'    => true, // require at least one number
        'require_symbols'    => true, // require at least one symbol
    ],
];
