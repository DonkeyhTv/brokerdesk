<?php

return [

    /*
    | Demo mode exposes the demo accounts publicly and resets ALL data every
    | night. Enable it only on a local machine or a dedicated demo server.
    */
    'enabled' => (bool) env('DEMO_MODE', false),

    'password' => 'password',

    // Labels are translation keys: the API answers in the language of the login page.
    'accounts' => [
        ['label' => 'Brokerage admin', 'app' => 'broker', 'email' => 'admin@example.be'],
        ['label' => 'Broker', 'app' => 'broker', 'email' => 'broker@example.be'],
        ['label' => 'Individual client', 'app' => 'portal', 'email' => 'client@example.be'],
        ['label' => 'Company client', 'app' => 'portal', 'email' => 'company@example.be'],
    ],

];
