<?php

return [
    'name' => 'SatCatalogs',

    // Source of truth for the sync command (phpcfdi/resources-sat-catalogs releases)
    'sync' => [
        'release_api_url' => env(
            'SAT_CATALOGS_RELEASE_API_URL',
            'https://api.github.com/repos/phpcfdi/resources-sat-catalogs/releases/latest'
        ),
        'asset_name' => 'catalogs.db.bz2',
        'batch_size' => 500,
    ],
];
