<?php
namespace Module\AssetManager\Services;

use Poirot\Config\Config;
use Poirot\Ioc\Container\Service\aServiceContainer;

use Module\AssetManager\Module;
use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Resolvers\AggregateResolver;


class AggregateResolverService
    extends aServiceContainer
{
    /**
     * @inheritdoc
     *
     * @return AggregateResolver
     * @throws \Exception
     */
    function newService()
    {
        $agrThemeResolver = new AggregateResolver;

        if ($conf = \Poirot\config(Module::class))
            $this->_build($agrThemeResolver, $conf);

        return $agrThemeResolver;
    }


    // ..

    /**
     * Build Aggregate Resolver
     *
     * @param AggregateResolver $agrResolver
     * @param Config $conf
     *
     * @throws \Exception
     */
    protected function _build(AggregateResolver $agrResolver, Config $conf)
    {
        foreach ($conf['resolvers'] as $resolver)
        {
            $priority = null;
            if ( $resolver instanceof \Traversable )
                // [$webRootResolver, 1000],
                list($resolver, $priority) = $resolver;

            if (! $resolver instanceof iAssetsResolver)
                throw new \Exception(sprintf(
                    'Resolver %s is not instance of %s'
                    , $resolver, iAssetsResolver::class
                ));


            $agrResolver->attach(
                $resolver
                , $priority
            );
        }
    }
}
