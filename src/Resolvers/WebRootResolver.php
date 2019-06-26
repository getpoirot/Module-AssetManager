<?php
namespace Module\AssetManager\Resolvers;

use function Poirot\Std\flatten;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;
use Poirot\Stream\Streamable\SUpstream;

use Module\AssetManager\Assets\WrapperAsset;
use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Interfaces\iWrapperResolver;

/*
$map = new MapResolver();
$map->with(['resources' => [
    '/licence/readme.txt' => __DIR__ . '/LICENSE',
]]);

$resolver = (new WebRootResolver($map))
    ->setWebRoot(PT_DIR_ROOT.'/html/');

$resolved = $resolver->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    echo $resolved->getStream()->read();
}
*/

class WebRootResolver
    implements iWrapperResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    /** @var iAssetsResolver */
    protected $originResolver;
    protected $webroot;


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


        $webroot = $this->getWebRoot();
        if (! is_dir($webroot) )
            throw new \RuntimeException(sprintf(
                '"%s" is not a directory or not found.'
                    , flatten($webroot)
            ));


        // Copy Asset To Destination Web Root; Create Directories Based On Request
        //
        $reqUri = rtrim($request->getTarget(), '/');

        $pathInfo  = pathInfo($reqUri);
        $assetDir  = $this->getWebRoot().'/'.$pathInfo['dirname'];
        $assetFile = $pathInfo['basename'];

        $this->_ensureDirectoriesByReqPath($assetDir);

        if (! is_writable($assetDir) )
            throw new \RuntimeException('Unable to write file ' . $reqUri);

        # Use "rename" to achieve atomic writes
        $tmpFilePath = $assetDir.'/'.$assetFile.'.tmp';

        if (! $asset->getStream()->resource()->isSeekable() ) {
            // Set Stream Seekable
            $asset = (new WrapperAsset($asset))
                ->setStream(new SUpstream($asset->getStream()->resource()));
        }

        $stream = $asset->getStream();
        if ( false === @file_put_contents($tmpFilePath, $stream->read(), LOCK_EX) )
            throw new \RuntimeException('Unable to write file ' . $reqUri);

        rename($tmpFilePath, $assetDir.'/'.$assetFile);

        $stream->rewind();
        return $asset;
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
     * Set WebRoot
     *
     * @param string $path
     *
     * @return $this
     */
    function setWebRoot(string $path)
    {
        $this->webroot = rtrim($path, '\\/');
        return $this;
    }

    /**
     * Get WebRoot
     *
     * @return string
     */
    function getWebRoot()
    {
        return $this->webroot;
    }


    // ..

    /**
     * Ensure Directories Exists By Recursively Crete Them If Not
     *
     * @param string $assetDir
     */
    protected function _ensureDirectoriesByReqPath($assetDir)
    {
        if ( is_dir($assetDir) )
            return;


        $umask = umask(0);
        mkdir($assetDir, 0777, true);
        umask($umask);
    }
}
