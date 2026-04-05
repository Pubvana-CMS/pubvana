<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Social extends BaseConfig
{
    public string $twitterApiKey      = '';
    public string $twitterApiSecret   = '';
    public string $twitterAccessToken = '';
    public string $twitterAccessSecret = '';
    public string $facebookPageId     = '';
    public string $facebookPageToken  = '';
}
