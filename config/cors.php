<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'v1/*'], // 👈 Tambahkan 'v1/*' jika route kalian tidak diawali /api

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // 👈 GANTI INI JADI SEPERTI INI

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];