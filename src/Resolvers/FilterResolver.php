<?php
namespace Module\AssetManager\Resolvers;


use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Struct\PriorityObjectCollection;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

use Module\AssetManager\Assets\WrapperAsset;
use Module\AssetManager\Resolvers\Filter\aFilter;
use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Interfaces\iWrapperResolver;

/*
$glob = new GlobResolver;
$glob->with(['globs' => [
    __DIR__ . '/www/css/*.css',
]]);

$resolver = (new CollectionResolver)
    ->setResolvers($glob)
    ->setFilename('main.css')
;

$resolver = (new FilterResolver($resolver))
    ->addFilter(new PhpMinifier());

$resolved = $resolver->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    echo $resolved->getStream()->read();
}
*/

class FilterResolver
    implements iWrapperResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    /** @var iAssetsResolver */
    protected $originResolver;

    /** @var PriorityObjectCollection */
    protected $queue;

    protected $_attached = [];
    protected $_c__normalized = [];


    /**
     * WebRootResolver
     *
     * @param iAssetsResolver $resolver
     */
    function __construct(iAssetsResolver $resolver)
    {
        $this->originResolver = $resolver;
    }


    /**
     * {@inheritDoc}
     */
    function resolve(iHttpRequest $request)
    {
        if (! $asset = $this->originResolver->resolve($request) )
            return null;

        if ( 0 == count($this->listFilters()) )
            return $asset;


        $stream = $asset->getStream();
        foreach ($this->_getPriorityQueue() as $filter)
            $stream = $filter->filter($stream, $asset->getMimetype());

        if ( $stream->isEOF() )
            throw new \InvalidArgumentException('Stream is on EOF Position.');

        return ( new WrapperAsset($asset) )
            ->setStream($stream);
    }

    /**
     * @inheritdoc
     */
    function collectAssets()
    {
        return $this->originResolver->collectAssets();
    }


    // Implement Aggregator:

    /**
     * Add Filter
     *
     * @param aFilter $filter
     * @param int             $priority
     *
     * @return $this
     */
    function addFilter(aFilter $filter, $priority = null)
    {
        $this->_getPriorityQueue()->insert($filter, [], $priority);

        $resolverName = $this->_normalizeLoaderName(get_class($filter));
        $this->_attached[$resolverName] = $filter;
        return $this;
    }

    /**
     * Remove Filter
     *
     * @param aFilter $filter
     *
     * @return $this
     */
    function removeFilter(aFilter $filter)
    {
        $this->_getPriorityQueue()->del($filter);
        return $this;
    }

    /**
     * Detach Whole Filters
     *
     * @return $this
     */
    function clearAllFilters()
    {
        foreach($this->_getPriorityQueue() as $detector)
            $this->removeFilter($detector);

        return $this;
    }

    /**
     * Get Filter By Name
     *
     * [code:]
     *  $filters->withAttached(FilterClassName::class)
     *     ->with([..options])
     * [code]
     *
     * @param string $filterName Filter Name, default is class name
     *
     * @throws \Exception Loader class not found
     * @return iAssetsResolver
     */
    function withFilter($filterName)
    {
        $filterName = $this->_normalizeLoaderName($filterName);

        if (! $this->isFilterAttached($filterName) )
            throw new \Exception(sprintf(
                'Loader with name (%s) has not attached.'
                , $filterName
            ));

        return $this->_attached[$filterName];
    }

    /**
     * Has Filter With This Name Attached?
     *
     * [code:]
     *  $aggregateLoader->isFilterAttached(FilterClassName::class)
     * [code]
     *
     * @param string|aFilter $filterName Loader Name, default is class name
     *
     * @return bool
     */
    function isFilterAttached($filterName)
    {
        if (is_object($filterName))
            $filterName = get_class($filterName);

        $filterName = $this->_normalizeLoaderName($filterName);
        return in_array($filterName, $this->listFilters());
    }

    /**
     * Get Attached filters List
     *
     * @return array Array Of Names
     */
    function listFilters()
    {
        return array_keys($this->_attached);
    }


    // Options:

    /**
     * Set Filters
     *
     * [
     *  Filter::class => [
     *  '_priority' => 1000,
     *   ..options
     *  ],
     * ],
     *
     * @param array $filters
     *
     * @return $this
     * @throws \Exception
     */
    function setAttachedResolvers(array $filters)
    {
        foreach ($filters as $filter => $settings)
        {
            if ($settings instanceof aFilter) {
                // [new AssetResolver(), ...]
                $filter = $settings;
                $settings = [];
            }

            if ($this->isFilterAttached($filter)) {
                $filter = $this->withFilter($filter);
                $filter->with($settings);

                continue;
            }


            if (is_string($filter) && class_exists($filter))
                $filter = new $filter;


            // Attach Resolver
            //
            if (! $filter instanceof aFilter)
                throw new \Exception(sprintf(
                    'Filter %s is not instance of %s'
                    , $filter, aFilter::class
                ));

            $filter->with($settings);

            $priority = \Poirot\Std\arrayKPop($settings, '_priority');
            $this->addFilter(
                $filter
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
