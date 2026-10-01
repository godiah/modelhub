<?php

/*
|--------------------------------------------------------------------------
| Avatars
|--------------------------------------------------------------------------
|
| Members and stores pick a picture from a fixed catalogue instead of uploading one. The catalogue (its styles,
| seeds and background colours) lives in resources/avatars/catalogue.json, which scripts/generate-avatars.mjs
| turns into the SVG files under public/images/avatars. "people" is for members, "stores" for stores. A chosen
| avatar is stored as "style/seed", for example "notionists/amara".
|
*/

$catalogue = json_decode((string) file_get_contents(resource_path('avatars/catalogue.json')), true) ?? [];

return [
    'path' => 'images/avatars',

    'people' => [
        'styles' => array_map(fn (array $style) => ['key' => $style['key'], 'label' => $style['label']], $catalogue['people']['styles'] ?? []),
        'seeds' => $catalogue['people']['seeds'] ?? [],
    ],

    'stores' => [
        'styles' => array_map(fn (array $style) => ['key' => $style['key'], 'label' => $style['label']], $catalogue['stores']['styles'] ?? []),
        'seeds' => $catalogue['stores']['seeds'] ?? [],
    ],
];
