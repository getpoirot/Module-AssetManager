<?php
namespace Module\AssetManager\Assets;

use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Resource\AccessMode;
use Poirot\Stream\Streamable;
use Poirot\Stream\StreamWrapperClient;

use Module\AssetManager\Interfaces\iAsset;
use Module\AssetManager\Exceptions\AssetNotReadableError;


class SerializableStreamAsset
    implements iAsset
    , \Serializable
{
    /** @var iAsset|null */
    protected $asset;

    /** @var iStreamable */
    protected $upStream;
    /** @var iStreamable */
    protected $downStream;
    /** @var string */
    protected $downstreamUri;

    protected $filename;
    protected $sourceuri;
    protected $mimetype;
    protected $size;
    protected $lastmodified;


    /**
     * DownloadStreamAsset
     *
     * @param iAsset $asset
     * @param string $copyStreamUri
     */
    function __construct(iAsset $asset, string $copyStreamUri)
    {
        $this->asset = $asset;
        $this->upStream = $asset->getStream();
        $this->downstreamUri = $copyStreamUri;
    }


    /**
     * @inheritdoc
     */
    function getStream()
    {
        if ($this->upStream)
            return $this->upStream;

        elseif ($this->downStream)
            return $this->downStream;


        // create for read
        return $this->_createDownStream();
    }

    /**
     * Retrieve the full filename include extension
     *
     * @return string
     */
    function getFilename()
    {
        if ($this->filename)
            return $this->filename;

        return $this->asset->getFilename();
    }

    /**
     * Returns the relative path for the source asset.
     *
     * @return string|null
     */
    function getSourceUri()
    {
        if ($this->sourceuri)
            return $this->sourceuri;

        return $this->asset->getSourceUri();
    }

    /**
     * Get MimeType Of Asset
     *
     * @return string
     * @throws AssetNotReadableError
     */
    function getMimetype()
    {
        if ($this->mimetype)
            return $this->mimetype;

        return $this->asset->getMimetype();
    }

    /**
     * Retrieve the file size in bytes
     *
     * @return int|null
     * @throws AssetNotReadableError
     */
    function getSize()
    {
        if ($this->size)
            return $this->size;

        return $this->asset->getSize();
    }

    /**
     * Get Last Modified Datetime
     *
     * @return int|null Last modified datetime in unix time if available
     * @throws AssetNotReadableError
     */
    function getLastModifiedTime()
    {
        if ($this->lastmodified)
            return $this->lastmodified;

        return $this->asset->getLastModifiedTime();
    }


    // Implement Serializable:

    /**
     * @inheritdoc
     */
    function serialize()
    {
        // copy stream to downstream; otherwise it's loaded from downstream
        //
        if ($this->upStream) {
            if ( $this->upStream->resource()->isSeekable() )
                $this->upStream->rewind();

            // create for write
            $downStream = $this->_createDownStream(AccessMode::MODE_RWBCT);
            $this->upStream->pipeTo($downStream);
        }


        return serialize([
            'downStreamUri' => $this->downstreamUri,
            'filename' => $this->getFilename(),
            'sourceuri' => $this->getSourceUri(),
            'mimetype' => $this->getMimetype(),
            'size' => $this->getSize(),
            'lastmodified' => $this->getLastModifiedTime(),
        ]);
    }

    /**
     * @inheritdoc
     */
    function unserialize($serialized)
    {
        $parsed = unserialize($serialized);

        $this->downstreamUri = $parsed['downStreamUri'];
        $this->filename = $parsed['filename'];
        $this->sourceuri = $parsed['sourceuri'];
        $this->mimetype = $parsed['mimetype'];
        $this->size = $parsed['size'];
        $this->lastmodified = $parsed['lastmodified'];
    }


    // ..

    protected function _createDownStream($accessMode = AccessMode::MODE_RB)
    {
        if ($this->downStream)
            return $this->downStream;


        $streamClient = new StreamWrapperClient($this->downstreamUri, new AccessMode($accessMode));
        return $this->downStream = new Streamable($streamClient->getConnect());
    }
}
