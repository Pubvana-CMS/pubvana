<?php

return [
    'routePrepend'    => 'marketplace',
    'store_url'       => 'http://plugindev',
    'api_timeout'     => 10,
    'catalog_cache_ttl' => 3600,
    'verify_days'     => 14,
    'revalidate_days' => 90,
    // Response-size caps (bytes): API/JSON fetches vs package zip downloads.
    'max_bytes'       => 1048576,
    'max_zip_bytes'   => 26214400,
];
