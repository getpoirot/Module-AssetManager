<?php
namespace Module\AssetManager\Resolvers;

use Poirot\Std\Glob;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

use function Poirot\Std\flatten;
use Poirot\Http\Interfaces\iHttpRequest;

use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Assets\FilesystemAsset;
use Module\AssetManager\Assets\HttpAsset;
use Module\AssetManager\Interfaces\iAsset;

/*
$glob = new GlobResolver;
$glob->with(['globs' => [
    __DIR__ . '/www/css/*.css',
]]);

$resolved = $glob->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    PhpServer::_( $resolved->toHttpResponse() )->send();
}
*/

class GlobResolver
    implements iAssetsResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    protected $globs;
    protected $_assetsMap;


    /**
     * {@inheritDoc}
     */
    function resolve(iHttpRequest $request)
    {
        $uri = $request->getTarget();
        $uri = trim($uri, '/');

        $map = $this->_creatAssetsMap();
        if (! isset($map[$uri]) )
            return null;

        $file  = $map[$uri];
        return $this->_resolveAssetByUri($file);
    }

    /**
     * @inheritdoc
     */
    function collectAssets()
    {
        foreach ($this->globs as $glob) {
            foreach (Glob::glob($glob) as $path) {
                yield $this->_resolveAssetByUri($path);
            }
        }

        return false;
    }


    // Options;

    /**
     * Set Glob Pattern
     *
     * @param string|array $globs A single glob path or array of paths
     *
     * @return GlobResolver
     */
    function setGlobs($globs)
    {
        $this->_assetsMap = null;

        $this->globs = (array) $globs;
        return $this;
    }

    /**
     * Globs Pattern
     *
     * @return array
     */
    function getGlobs()
    {
        return $this->globs;
    }


    // ..

    /**
     * Create asset name to asset uri map for resolve method
     *
     * @return array
     */
    protected function _creatAssetsMap()
    {
        if ($this->_assetsMap)
            return $this->_assetsMap;


        /** @var iAsset $asset */
        foreach ($this->collectAssets() as $asset) {
            $this->_assetsMap[$asset->getFilename()] = $asset->getSourceUri();
        }

        return $this->_assetsMap;
    }

    /**
     * Resolve To Asset By Given Path Uri
     *
     * @param string $uriPath
     *
     * @return iAsset
     */
    protected function _resolveAssetByUri($uriPath)
    {
        $asset = null;
        if ( filter_var($uriPath, FILTER_VALIDATE_URL) )
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
