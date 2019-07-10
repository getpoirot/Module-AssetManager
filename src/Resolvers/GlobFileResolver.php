<?php
namespace Module\AssetManager\Resolvers;

use Poirot\Std\Glob;
use Poirot\Std\Type\StdString;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

use Poirot\Http\Interfaces\iHttpRequest;

use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Assets\FilesystemAsset;
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

class GlobFileResolver
    implements iAssetsResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    protected $globs;
    protected $baseDir;
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
        foreach ($this->globs as $glob)
        {
            if ('' != $baseDir = $this->getBaseDir()) {
                $glob = (string) StdString::safeJoin(DS, ...[
                    $baseDir,
                    StdString::of($glob)->stripPrefix($baseDir),
                ]);
            }

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
     * @return GlobFileResolver
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

    /**
     * Set Base Directory
     *
     * @param string $dir
     *
     * @return $this
     */
    function setBaseDir($dir)
    {
        $this->baseDir = (string) $dir;
        return $this;
    }

    /**
     * Get Base Directory
     *
     * @return string
     */
    function getBaseDir()
    {
        return (string) $this->baseDir;
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
            $assetFilePath = $asset->getSourceUri();
            if ('' != $baseDir = $this->getBaseDir()) {
                $map = (string) StdString::of($assetFilePath)
                    ->stripPrefix( $this->getBaseDir() );
                $map = ltrim(str_replace('\\', '//', $map), '/');
            } else {
                $map = $asset->getFilename();
            }

            $this->_assetsMap[$map] = $assetFilePath;
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
        if ( is_dir($uriPath) )
            throw new \RuntimeException(sprintf(
                'Glob Asset File Are Not Able To Recognize Assets Recursively on this directory "%s".'
                , $uriPath
            ));


        $asset = new FilesystemAsset($uriPath);
        return $asset;
    }
}
