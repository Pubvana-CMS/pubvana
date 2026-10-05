<?php

return [
    'routePrepend'    => 'marketplace',
    'store_url'       => 'https://pubvanacms.com',
    'api_timeout'     => 10,
    'verify_days'     => 14,
    'revalidate_days' => 90,
    // Response-size caps (bytes): API/JSON fetches vs package zip downloads.
    'max_bytes'       => 1048576,
    'max_zip_bytes'   => 26214400,
];
