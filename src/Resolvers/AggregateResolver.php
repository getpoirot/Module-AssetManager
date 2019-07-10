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
    ->setPath('/css/');


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
     *
     * @param iAssetsResolver[] $resolvers
     *
     * @return AggregateResolver
     * @throws \Exception
     */
    function setResolvers(iAssetsResolver ...$resolvers)
    {
        foreach ($resolvers as $resolver)
            $this->attach($resolver);


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
}
