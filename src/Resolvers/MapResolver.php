<?php
namespace Module\AssetManager\Resolvers;

use function Poirot\Std\flatten;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

use Module\AssetManager\Interfaces\iAsset;
use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Assets\FilesystemAsset;
use Module\AssetManager\Assets\HttpAsset;


/*
$map = new MapResolver();
$map->with(['resources' => [
    '/licence/readme.txt' => __DIR__ . '/LICENSE',
]]);

$resolved = $map->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    PhpServer::_( $resolved->toHttpResponse() )->send();
}
*/

class MapResolver
    implements iAssetsResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    protected $assetMaps = [];


    /**
     * {@inheritDoc}
     */
    function resolve(iHttpRequest $request)
    {
        $uri = $request->getTarget();

        if (! isset($this->assetMaps[$uri]) )
            return null;


        $file  = $this->assetMaps[$uri];
        return $this->_resolveAssetByUri($file);
    }

    /**
     * Collect All Resolved Assets That This Resolver Can Provide
     *
     * @return \Traversable
     */
    function collectAssets()
    {
        foreach ($this->assetMaps as $assetPath) {
            yield $this->_resolveAssetByUri($assetPath);
        }

        return false;
    }


    // Options:

    /**
     * Set Assets Map To Absolute Address
     *
     * @param array $map
     *
     * @return MapResolver
     */
    function setResources(array $map)
    {
        $this->assetMaps = $map;
        return $this;
    }

    /**
     * Get Resources Map
     *
     * @return array
     */
    function getResources()
    {
        return $this->assetMaps;
    }


    // ..

    /**
     * Resolve Asset By Given Uri Address
     *
     * @param string $uriPath
     *
     * @return iAsset
     */
    function _resolveAssetByUri($uriPath)
    {
        $asset = null;
        if ( class_exists($uriPath) )
            $asset = new $uriPath;

        else if ( filter_var($uriPath, FILTER_VALIDATE_URL) )
            $asset = new HttpAsset($uriPath);

        elseif ( is_string($uriPath) )
            $asset = new FilesystemAsset($uriPath);


        if (! $asset instanceof iAsset)
            throw new \InvalidArgumentException(sprintf(
                '"%s" not recognized as asset.'
                    , flatten($uriPath)
            ));

        return $asset;
    }
}
