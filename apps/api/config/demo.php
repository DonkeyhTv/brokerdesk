<?php

return [

    /*
    | Demo mode exposes the demo accounts publicly and resets ALL data every
    | night. Enable it only on a local machine or a dedicated demo server.
    */
    'enabled' => (bool) env('DEMO_MODE', false),

    'password' => 'password',

    'accounts' => [
        ['label' => 'Administrateur du cabinet', 'app' => 'broker', 'email' => 'admin@example.be'],
        ['label' => 'Courtier', 'app' => 'broker', 'email' => 'broker@example.be'],
        ['label' => 'Client particulier', 'app' => 'portal', 'email' => 'client@example.be'],
        ['label' => 'Client société', 'app' => 'portal', 'email' => 'company@example.be'],
    ],

];
