<?php

use App\Providers\AppServiceProvider;
use App\Providers\RepositoryServiceProvider;

return [
    AppServiceProvider::class,
    \App\Providers\LoggingServiceProvider::class,
    RepositoryServiceProvider::class,
];
