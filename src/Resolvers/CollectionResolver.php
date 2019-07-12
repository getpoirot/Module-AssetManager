<?php
namespace Module\AssetManager\Resolvers;

use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;
use Poirot\Stream\Streamable\SAggregateStreams;

use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Interfaces\iAsset;
use Module\AssetManager\Resolvers\Aggregate\AggregateAsset;


/*
$glob = new GlobResolver;
$glob->with(['globs' => [
    __DIR__ . '/www/css/*.css',
]]);

$resolver = (new CollectionResolver)
    ->setResolvers($glob);

$resolved = $resolver->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    PhpServer::_( $resolved->toHttpResponse() )->send();
}
*/

class CollectionResolver
    implements iAssetsResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    protected $resolvers;
    protected $mimetype;
    protected $filename;
    protected $mtime;
    /** @var SAggregateStreams */
    protected $_intializedStream;


    /**
     * {@inheritDoc}
     */
    function resolve(iHttpRequest $request)
    {
        $uri = $request->getTarget();
        $uri = $this->_normalizeUri($uri);

        if (null === $this->filename)
            $this->_initAggregatedStream();


        if ( $uri !== $this->getFilename() )
            return null;

        $asset = $this->_resolveAsset();
        return $asset;
    }

    /**
     * @inheritdoc
     */
    function collectAssets()
    {
        $asset = $this->_resolveAsset();
        yield $asset;
    }


    // ..

    /**
     * Set Resolvers
     *
     * @param iAssetsResolver ...$resolvers
     *
     * @return $this
     */
    function setResolvers(iAssetsResolver ...$resolvers)
    {
        $this->_intializedStream = null;

        $this->resolvers = $resolvers;
        return $this;
    }

    /**
     * Set Strict Mimetype To Resolver
     *
     * @param string $mimeType
     *
     * @return $this
     */
    function setMimetype(string $mimeType)
    {
        $this->mimetype = $mimeType;
        return $this;
    }

    /**
     * Get Mimetype
     *
     * @return string|null
     */
    function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * Set Filename that Resolver Collect Files Under That
     *
     * @param $filename
     *
     * @return $this
     */
    function setFilename(string $filename)
    {
        $this->filename = $this->_normalizeUri($filename);
        return $this;
    }

    /**
     * Mered Asset Name
     *
     * @return string|null
     */
    function getFilename()
    {
        return $this->filename;
    }


    // ..

    /**
     * Resolve Asset
     *
     * @return AggregateAsset
     */
    protected function _resolveAsset()
    {
        $stream = $this->_initAggregatedStream();
        return new AggregateAsset($stream, $this->getFilename(), $this->getMimetype(), $this->mtime);
    }

    /**
     * Initialize Resolvers and Gather Assets
     *
     * @return SAggregateStreams
     */
    protected function _initAggregatedStream()
    {
        $aggrStream = new SAggregateStreams;

        $mtime = null;
        /** @var iAssetsResolver $resolver */
        foreach ($this->resolvers as $resolver)
        {
            /** @var iAsset $asset */
            foreach ($resolver->collectAssets() as $asset) {
                $this->_assertValidateMimetype($asset);
                $this->_assertFilename($asset);

                // last modified time
                $assetMtime = $asset->getLastModifiedTime();
                if ($assetMtime > $mtime)
                    $mtime = $assetMtime;


                $aggrStream->addStream( $asset->getStream() );
            }
        }

        $this->mtime = $mtime;

        return $this->_intializedStream = $aggrStream;
    }

    /**
     * Assert Validate Asset Mimetype
     *
     * @param iAsset $asset
     *
     * @throws \InvalidArgumentException
     */
    protected function _assertValidateMimetype(iAsset $asset)
    {
        if ( null === $this->getMimetype() )
            // if no mimetype defined use the first asset resolved mimetype
            $this->setMimetype( $asset->getMimetype() );

        elseif ($this->getMimetype() !== $asset->getMimetype())
            throw new \InvalidArgumentException(sprintf(
                'Asset Mimetype Violation "%s"; Collection Strictly Accept "%s".'
                    ,$asset->getMimetype() ,$this->getMimetype()
            ));
    }

    /**
     * Assert Filename
     *
     * @param $asset
     */
    protected function _assertFilename(iAsset $asset)
    {
        if ( null !== $this->getFilename() )
            return;

        $this->setFilename( $asset->getFilename() );
    }

    private function _normalizeUri(string $uri)
    {
        $uri = parse_url($uri);
        return trim(@$uri['path'], '/');
    }
}
