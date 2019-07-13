<?php
namespace Module\AssetManager
{
    use Poirot\Application\Interfaces\Sapi;
    use Poirot\Application\Interfaces\Sapi\iSapiModule;
    use Poirot\Application\Sapi\Event\EventHeapOfSapi;
    use Poirot\Ioc\Container;
    use Poirot\Ioc\Container\BuildContainer;
    use Poirot\Ioc\instance;
    use Poirot\Loader\Autoloader\LoaderAutoloadAggregate;
    use Poirot\Loader\Autoloader\LoaderAutoloadNamespace;
    use Poirot\Std\Interfaces\Struct\iDataEntity;

    use Module\AssetManager\RenderStrategy\AssetRenderStrategy;
    use Module\AssetManager\Actions\ActionsContainerOfAssetManager;
    use Module\HttpRenderer\Services\RenderStrategies\PluginsOfRenderStrategy;


    class Module implements iSapiModule
        , Sapi\Module\Feature\iFeatureModuleInitSapi
        , Sapi\Module\Feature\iFeatureModuleAutoload
        , Sapi\Module\Feature\iFeatureModuleMergeConfig
        , Sapi\Module\Feature\iFeatureModuleNestServices
        , Sapi\Module\Feature\iFeatureModuleInitSapiEvents
        , Sapi\Module\Feature\iFeatureModuleNestActions
        , Sapi\Module\Feature\iFeatureOnPostLoadModulesGrabServices
    {
        /**
         * @inheritdoc
         */
        function initialize($sapi)
        {
            if ( \Poirot\isCommandLine( $sapi->getSapiName() ) )
                // Sapi Is Not HTTP. SKIP Module Load!!
                return false;
        }

        /**
         * @inheritdoc
         */
        function initAutoload(LoaderAutoloadAggregate $baseAutoloader)
        {
            $nameSpaceLoader = \Poirot\Loader\Autoloader\LoaderAutoloadNamespace::class;
            /** @var LoaderAutoloadNamespace $nameSpaceLoader */
            $nameSpaceLoader = $baseAutoloader->loader($nameSpaceLoader);
            $nameSpaceLoader->addResource(__NAMESPACE__, __DIR__);

            require_once __DIR__ . '/functions.php';
        }

        /**
         * @inheritdoc
         */
        function initConfig(iDataEntity $config)
        {
            return \Poirot\Config\load(__DIR__ . '/../config/cor-asset_manager');
        }

        /**
         * @inheritdoc
         */
        function getServices(Container $moduleContainer = null)
        {
            $conf    = include __DIR__ . '/../config/cor-asset_manager.services.conf.php';

            $builder = new BuildContainer;
            $builder->with($builder::parseWith($conf));
            return $builder;
        }

        /**
         * @inheritdoc
         */
        function initSapiEvents(EventHeapOfSapi $events)
        {
            Services::AssetManager()->attachToEvent($events);
        }

        /**
         * @inheritdoc
         */
        function getActions()
        {
            return new ActionsContainerOfAssetManager;
        }

        /**
         * @inheritdoc
         *
         * @param PluginsOfRenderStrategy $renderStrategies @IoC /module/HttpRenderer/services/RenderStrategies
         *
         * @throws \Exception
         */
        function resolveRegisteredServices(
            $renderStrategies = null
        ) {
            if ( $renderStrategies ) {
                $renderStrategies->set(new Container\Service\ServiceInstance(
                    'asset-renderer'
                    , new instance(AssetRenderStrategy::class)
                ));
            }
        }
    }
}
