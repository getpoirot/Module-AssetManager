<?php
use Module\AssetManager\AssetManager;
use Module\AssetManager\Services;
use Module\AssetManager\Resolvers\AggregateResolver;


return [
    'implementations' => [
        Services::AssetManager  => AssetManager::class,
        Services::AssetResolver => AggregateResolver::class,
    ],
    'services' => [
        Services::AssetManager  => AssetManager::class,
        Services::AssetResolver => Services\AggregateResolverService::class,
    ],
];
