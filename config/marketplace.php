<?php

/*
|--------------------------------------------------------------------------
| 3D models marketplace settings
|--------------------------------------------------------------------------
|
| Limits and allowed file types for model listings. See MARKETPLACE.md for the design behind them.
| Model files are private (served only to authorised users); preview images are public.
|
*/

return [
    // Where model files and preview images are stored. Use an S3-compatible disk for files in production.
    'files_disk' => env('MARKETPLACE_FILES_DISK', 'local'),
    'images_disk' => env('MARKETPLACE_IMAGES_DISK', 'public'),

    // Upload limits (megabytes where named _mb). The PHP and nginx limits must stay above max_file_mb.
    'max_file_mb' => (int) env('MARKETPLACE_MAX_FILE_MB', 50),
    'max_files' => 20,
    'max_images' => 10,
    'max_image_mb' => 5,
    'max_tags' => 15,

    // Checkout is not built yet: until it is, product pages show "Purchases open soon" instead of a buy button.
    'purchases_enabled' => (bool) env('MARKETPLACE_PURCHASES_ENABLED', false),

    // Money is stored in minor units with its currency; KES only at launch.
    'currency' => 'KES',

    // File types by what they are for. A file's kind comes from its extension.
    'formats' => [
        // Native files of a modelling program
        'native' => ['blend', 'max', 'ma', 'mb', 'c4d', 'skp', 'zpr', 'ztl', 'hip', 'spp', 'sbs', 'sbsar', 'rvt', '3dm', 'lxo', 'mud'],
        // Formats that move between programs
        'exchange' => ['fbx', 'obj', 'glb', 'gltf', 'stl', '3mf', 'dae', '3ds', 'abc', 'usd', 'usda', 'usdc', 'usdz', 'ply', 'x3d', 'dwg', 'dxf', 'step', 'stp', 'iges', 'igs'],
        // Texture and image maps shipped with a model
        'texture' => ['jpg', 'jpeg', 'png', 'tga', 'tif', 'tiff', 'exr', 'hdr', 'psd', 'webp'],
        // Packaging and documentation
        'archive' => ['zip', 'rar', '7z'],
        'document' => ['pdf', 'txt', 'md'],
    ],

    // A seller may rename their store this often (days), and upload a logo up to this size (MB)
    'name_change_days' => 30,
    'max_logo_mb' => 2,

    // Allowed preview image types
    'image_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
];
