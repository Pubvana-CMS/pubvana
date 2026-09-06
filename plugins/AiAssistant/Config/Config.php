<?php

return [
    'routePrepend'           => 'ai',
    'key_prefix'             => 'pvai1_',
    'max_failed_attempts'    => 3,
    'block_minutes'          => 30,
    'log_limit'              => 200,
    'factcheck_prompt_url'   => 'https://pubvanacms.com/fact-checking/prompt.json',
    // The explainer page lives under /pages/ on the current v2 site; it
    // moves to /page/ when the main website is ported to v3.
    'factcheck_page_url'     => 'https://pubvanacms.com/pages/fact-checking',
    'factcheck_http_timeout' => 5,
];