<?php

return [

    /*
    | The proxies allowed to report the visitor's address and protocol through
    | X-Forwarded-* headers: "*" trusts the direct caller, or give a comma
    | separated list of addresses. Set it only when a proxy stands in front.
    */
    'proxies' => env('TRUSTED_PROXIES') ?: null,

];
