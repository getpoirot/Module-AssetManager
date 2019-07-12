<?php
namespace Module\AssetManager\Resolvers;

use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

use Poirot\Http\Interfaces\iHttpRequest;

use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Assets\FilesystemAsset;
use Module\AssetManager\Interfaces\iAsset;
use Poirot\Std\Type\StdString;


class DirectoryResolver
    implements iAssetsResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    protected $dirPath;
    protected $excludePaths;
    protected $_assetsMap;


    /**
     * {@inheritDoc}
     */
    function resolve(iHttpRequest $request)
    {
        $uri = $request->getTarget();
        $uri = parse_url($uri);
        $uri = trim(@$uri['path'], '/');

        $assetFile = $this->getDir().'/'.$uri;
        foreach ($this->getExcludePaths() as $excludePath) {
            if ( StdString::of($assetFile)->isStartWith($excludePath) )
                // Inside excluded path
                return;
        }

        if (! is_file($assetFile) )
            return;


        return $this->_resolveAssetByUri($assetFile);
    }

    /**
     * @inheritdoc
     */
    function collectAssets()
    {
        $assetsMap = $this->_creatAssetsMap( $this->getDir() );
        foreach ($assetsMap as $path) {
            yield $this->_resolveAssetByUri($path);
        }

        return false;
    }


    // Options;

    /**
     * Set Directory Path
     *
     * @param string $dirPath
     *
     * @return $this
     */
    function setDir(string $dirPath)
    {
        if (! is_dir($dirPath) )
            throw new \InvalidArgumentException(sprintf(
                '"%s" is not a directory.'
                    , $dirPath
            ));


        $this->dirPath = $dirPath;
        return $this;
    }

    /**
     * Directory Path
     *
     * @return string|null
     */
    function getDir()
    {
        return $this->dirPath;
    }

    /**
     * Set Exclude Paths
     *
     * @param string ...$paths Relative path to directory
     *
     * @return $this
     */
    function setExcludePaths(string ...$paths)
    {
        $this->excludePaths = $paths;
        return $this;
    }

    /**
     * Get Exclude Paths
     *
     * @return string[]
     */
    function getExcludePaths()
    {
       return $this->excludePaths;
    }


    // ..

    /**
     * Create asset name to asset uri map for resolve method
     *
     * @param string $directory
     *
     * @return array
     */
    protected function _creatAssetsMap($directory)
    {
        $assetsMap = [];
        foreach (new \DirectoryIterator($directory) as $fileInfo)
        {
            if ( $fileInfo->isDot() )
                continue;

            if ( $fileInfo->isDir() ) {
                $nestedDir = $this->_creatAssetsMap( $fileInfo->getRealPath() );
                $assetsMap = array_merge($assetsMap, $nestedDir);
                continue;
            }

            $assetsMap[] = $fileInfo->getRealPath();
        }


        return $assetsMap;
    }

    /**
     * Resolve To Asset By Given Path Uri
     *
     * @param string $uriPath
     *
     * @return iAsset|false
     */
    protected function _resolveAssetByUri($uriPath)
    {
        $asset = new FilesystemAsset($uriPath);
        return $asset;
    }
}
