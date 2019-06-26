<?php
namespace Module\AssetManager\Resolvers;

use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;
use Poirot\Std\Struct\PriorityObjectCollection;

use Module\AssetManager\Interfaces\iAsset;
use Module\AssetManager\Interfaces\iAssetsResolver;


/*
$glob = new GlobResolver;
$glob->with(['globs' => [
    __DIR__ . '/www/css/*.css',
]]);

$prefixGlob = (new MatchPathPrefix($glob))
    ->setPathPrefix('/css/');


$map = new MapResolver();
$map->with(['resources' => [
    '/licence/readme.txt' => __DIR__ . '/LICENSE',
]]);


$resolver = (new AggregateResolver)
    ->setAttachedResolvers([
        $prefixGlob,
        $map
    ]);

$resolved = $resolver->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    PhpServer::_( $resolved->toHttpResponse() )->send();
}
*/

class AggregateResolver
    implements iAssetsResolver
{
    use tConfigurable;
    use tConfigurableSetter;


    /** @var PriorityObjectCollection */
    protected $queue;

    protected $_attached = [];
    protected $_c__normalized = [];


    /**
     * {@inheritDoc}
     */
    function resolve(iHttpRequest $request)
    {
        /** @var $resolver iAssetsResolver */
        foreach ($this->_getPriorityQueue() as $resolver) {
            if (! $result = $resolver->resolve($request) )
                continue;

            return $result;
        }

        return null;
    }

    /**
     * Collect All Assets That This Resolver Provide
     *
     * @return \Traversable
     */
    function collectAssets()
    {
        /** @var iAssetsResolver $assetResolver */
        foreach ($this->_getPriorityQueue() as $assetResolver) {
            /** @var iAsset $asset */
            foreach ($assetResolver->collectAssets() as $asset )
                yield $asset;
        }

        return false;
    }


    // Aggregator:

    /**
     * Attach a resolver strategy
     *
     * @param iAssetsResolver $resolver
     * @param int             $priority
     *
     * @return AggregateResolver
     */
    function attach(iAssetsResolver $resolver, $priority = null)
    {
        $this->_getPriorityQueue()->insert($resolver, [], $priority);

        $resolverName = $this->_normalizeLoaderName(get_class($resolver));
        $this->_attached[$resolverName] = $resolver;
        return $this;
    }

    /**
     * Detach Resolver From Aggregate
     *
     * @param iAssetsResolver $detector
     *
     * @return $this
     */
    function detach(iAssetsResolver $detector)
    {
        $this->_getPriorityQueue()->del($detector);
        return $this;
    }

    /**
     * Detach Whole Strategies
     *
     * @return $this
     */
    function detachAll()
    {
        foreach($this->_getPriorityQueue() as $detector)
            $this->detach($detector);

        return $this;
    }

    /**
     * Get Resolver By Name
     *
     * [code:]
     *  $aggregateLoader->loader(ResolverClassName::class)
     *     ->with([..options])
     * [code]
     *
     * @param string $loaderName Loader Name, default is class name
     *
     * @throws \Exception Loader class not found
     * @return iAssetsResolver
     */
    function withAttached($loaderName)
    {
        $loaderName = $this->_normalizeLoaderName($loaderName);

        if (! $this->isAttached($loaderName) )
            throw new \Exception(sprintf(
                'Loader with name (%s) has not attached.'
                , $loaderName
            ));

        return $this->_attached[$loaderName];
    }

    /**
     * Has Resolver With This Name Attached?
     *
     * [code:]
     *  $aggregateLoader->isAttached(ResolverClassName::class)
     * [code]
     *
     * @param string|iAssetsResolver $loaderName Loader Name, default is class name
     *
     * @return bool
     */
    function isAttached($loaderName)
    {
        if (is_object($loaderName))
            $loaderName = get_class($loaderName);

        $loaderName = $this->_normalizeLoaderName($loaderName);
        return in_array($loaderName, $this->listAttached());
    }

    /**
     * Get Attached loader List
     *
     * @return array Array Of Names
     */
    function listAttached()
    {
        return array_keys($this->_attached);
    }


    // Options:

    /**
     * Set Resolvers
     *
     * [
     *  MapResolver::class => [
     *  '_priority' => 1000,
     *  'assets/manager/AssetManager600x450.png' => __DIR__ . '/../www/AssetManager600x450.png',
     *  ],
     * ],
     *
     * @param array $resolvers
     *
     * @return AggregateResolver
     * @throws \Exception
     */
    function setAttachedResolvers(array $resolvers)
    {
        foreach ($resolvers as $resolver => $settings)
        {
            if ($settings instanceof iAssetsResolver) {
                // [new AssetResolver(), ...]
                $resolver = $settings;
                $settings = [];
            }

            if ($this->isAttached($resolver)) {
                $resolver = $this->withAttached($resolver);
                $resolver->with($settings);

                continue;
            }


            if (is_string($resolver) && class_exists($resolver))
                $resolver = new $resolver;


            // Attach Resolver
            //
            if (! $resolver instanceof iAssetsResolver)
                throw new \Exception(sprintf(
                    'Resolver %s is not instance of %s'
                    , $resolver, iAssetsResolver::class
                ));

            $resolver->with($settings);

            $priority = \Poirot\Std\arrayKPop($settings, '_priority');
            $this->attach(
                $resolver
                , $priority
            );
        }

        return $this;
    }


    // ..

    /**
     * internal priority queue
     *
     * @return PriorityObjectCollection
     */
    protected function _getPriorityQueue()
    {
        if (!$this->queue)
            $this->queue = new PriorityObjectCollection;

        return $this->queue;
    }

    protected function _normalizeLoaderName($loaderName)
    {
        $loaderName = (string) $loaderName;
        if (isset($this->_c__normalized[$loaderName]))
            return $this->_c__normalized[$loaderName];

        $normalized = ltrim($loaderName, '\\');
        return $this->_c__normalized[$loaderName] = $normalized;
    }
}
