<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'Radio La Poderosa',
        'url' => 'https://live.radiolapoderosatv.com',
        'timezone' => 'America/Lima',
        'debug' => false,
    ],
    'radio' => [
        'stream_url' => 'https://cast2.my-control-panel.com/proxy/radiolax/stream',
        'stats_url' => 'https://cast2.my-control-panel.com/proxy/radiolax/stats?json=1',
        'logo' => 'https://i.postimg.cc/pLmmhCL7/LOGOTIPO-TRANSPARENTE-LA-PODEROSA-Mesa-de-trabajo-1.png',
    ],
    'db' => [
        'host' => 'localhost',
        'name' => 'cpanel_dbname',
        'user' => 'cpanel_dbuser',
        'pass' => 'change-me',
        'charset' => 'utf8mb4',
    ],
    'media' => [
        'driver' => getenv('MEDIA_DRIVER') ?: (getenv('CLOUDINARY_URL') ? 'cloudinary' : 'local'),
        'cloudinary_url' => getenv('CLOUDINARY_URL') ?: '',
        'folder' => getenv('MEDIA_FOLDER') ?: 'radiolpsorteos',
    ],
    'public' => [
        // Solo para desarrollo: permite ver el borrador más reciente en /sorteos.
        'preview_drafts' => getenv('PUBLIC_PREVIEW_DRAFTS') ?: false,
    ],
];
