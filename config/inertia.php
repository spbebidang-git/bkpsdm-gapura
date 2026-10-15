<?php

return [
    // Match the existing capitalized Pages directory on Linux deployments.
    'pages' => [
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['js', 'jsx', 'ts', 'tsx', 'vue', 'svelte'],
    ],
];
