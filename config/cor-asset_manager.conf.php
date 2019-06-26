<?php
use Module\AssetManager\Resolvers\WebRootResolver;
use Module\AssetManager\Resolvers\MapResolver;
use Module\AssetManager\Resolvers\PathPrefixResolver;


$assetImagesMap = (new PathPrefixResolver(
    (new MapResolver())->setResources([
        '/AssetManager600x450.png' => __DIR__ . '/../www/AssetManager600x450.png',
    ])
))->setPathPrefix('/asset/manager');


$webRootResolver = (new WebRootResolver($assetImagesMap))
    ->setWebRoot(PT_DIR_ROOT . '/html');

return [
    'resolvers' => [
        // define with priority
        #[$webRootResolver, 1000],
        // no priority, web root cache
        # $webRootResolver,
        // asset images map
        $assetImagesMap,
    ],
];
