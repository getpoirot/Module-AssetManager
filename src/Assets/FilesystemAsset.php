<?php
namespace Module\AssetManager\Assets;

use Poirot\Http\MimeResolver;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Resource\AccessMode;
use Poirot\Stream\Streamable;
use Poirot\Stream\StreamWrapperClient;

use Module\AssetManager\Exceptions\AssetNotReadableError;
use Module\AssetManager\Interfaces\iAsset;


class FilesystemAsset
    implements iAsset
{
    /** @var string */
    protected $assetUri;
    /** @var iStreamable */
    protected $stream;
    /** @var MimeResolver */
    protected $_mimeResolver;


    /**
     * FilesystemAsset
     *
     * @param string $assetUri
     */
    function __construct(string $assetUri)
    {
        $this->assetUri = $assetUri;
    }


    /**
     * @inheritdoc
     */
    function getStream()
    {
        if ($this->stream)
            return $this->stream;


        if (! is_readable($this->assetUri) )
            throw new AssetNotReadableError(sprintf(
                'Asset "%s" is not readable or not found.'
                , $this->assetUri
            ));

        $streamClient = new StreamWrapperClient($this->assetUri, new AccessMode('bRB'));
        return $this->stream = new Streamable($streamClient->getConnect());
    }

    /**
     * @inheritdoc
     */
    function getFilename()
    {
        return basename($this->assetUri);
    }

    /**
     * @inheritdoc
     */
    function getSourceUri()
    {
        return $this->assetUri;
    }

    /**
     * @inheritdoc
     */
    function getMimetype()
    {
        $this->getStream();

        return $this->_mimeResolver()
            ->getMimeType( $this->assetUri );
    }

    /**
     * @inheritdoc
     */
    function getSize()
    {
        return $this->getStream()->getSize();
    }

    /**
     * @inheritdoc
     */
    function getLastModifiedTime()
    {
        $this->getStream();
        return filemtime($this->assetUri);
    }


    // ..

    /**
     * Get MimeResolver
     *
     * @return MimeResolver
     */
    function _mimeResolver()
    {
        if ($this->_mimeResolver)
            return $this->_mimeResolver;


        return $this->_mimeResolver = new MimeResolver;
    }
}
