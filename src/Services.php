<?php
namespace Module\AssetManager
{
    use Module\AssetManager\Resolver\AggregateResolver;


    /**
     * @method static AssetManager AssetManager()
     * @method static AggregateResolver AssetResolver()
     */
    class Services extends \IOC
    {
        const AssetManager  = 'AssetManager';
        const AssetResolver = 'AssetResolver';
    }
}
