<?php
namespace Module\AssetManager\Resolvers;

use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

use Module\AssetManager\Interfaces\iAsset;
use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Interfaces\iWrapperResolver;
use Module\AssetManager\Resolvers\Cache\FilesystemCache;

/*
$glob = new GlobResolver;
$glob->with(['globs' => [
    __DIR__ . '/www/js/*.js',
]]);

$collResolver = (new CollectionResolver)
    ->setResolvers($glob)
    ->setFilename('main.js')
;

$resolver = new CacheResolver($collResolver);

$resolved = $resolver->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    echo $resolved->getStream()->read();
}
*/

class CacheResolver
    implements iWrapperResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    /** @var iAssetsResolver */
    protected $originResolver;
    /** @var FilesystemCache */
    protected $cacheAdapter;


    /**
     * CachedResolver
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
        $cacheKey = $this->_getCacheKey($request);

        // Load From Cache
        if ( $asset = $this->getCachedAdapter()->has($cacheKey) ) {
            $asset = $this->getCachedAdapter()->get($cacheKey);
            if (! $asset instanceof iAsset)
                throw new \RuntimeException(
                    'Asset Object Not Recognized After Retrieving From Cache.'
                );

            return $asset;
        }


        $asset = $this->originResolver->resolve($request);
        // Set Into Cache
        $this->getCachedAdapter()->set($cacheKey, $asset);

        // resolve it again from cache; ensure that we rewind stream
        return $this->resolve($request);
    }

    /**
     * @inheritdoc
     */
    function collectAssets()
    {
        return $this->originResolver->collectAssets();
    }


    // Options:

    /**
     * Set Cache Adapter
     *
     * @param  $cacheAdapter
     *
     * @return $this
     */
    function setCacheAdapter($cacheAdapter)
    {
        $this->cacheAdapter = $cacheAdapter;
        return $this;
    }

    /**
     * Get Cache Adapter
     *
     * @return FilesystemCache
     */
    function getCachedAdapter()
    {
        if (! $this->cacheAdapter )
            $this->setCacheAdapter( new FilesystemCache(PT_DIR_TMP) );


        return $this->cacheAdapter;
    }


    // ..

    protected function _getCacheKey(iHttpRequest $request)
    {
        return str_replace('/\\', '_', $request->getTarget());
    }
}
