<?php
namespace Module\AssetManager\Assets;

use Poirot\Stream\Interfaces\iStreamable;

use Module\AssetManager\Interfaces\iAsset;


class WrapperAsset
    implements iAsset
{
    /** @var iAsset|null */
    protected $originAsset;

    protected $stream;
    protected $filename;
    protected $sourceuri;
    protected $mimetype;
    protected $size;
    protected $lastmodified;


    /**
     * WrapperAsset
     *
     * @param iAsset $asset
     */
    function __construct(iAsset $asset)
    {
        $this->originAsset = $asset;
    }


    /**
     * @inheritdoc
     */
    function getStream()
    {
        if ($this->stream)
            return $this->stream;

        return $this->originAsset->getStream();
    }

    /**
     * @inheritdoc
     */
    function getFilename()
    {
        if ($this->filename)
            return $this->filename;

        return $this->originAsset->getFilename();
    }

    /**
     * @inheritdoc
     */
    function getSourceUri()
    {
        if ($this->sourceuri)
            return $this->sourceuri;

        return $this->originAsset->getSourceUri();
    }

    /**
     * @inheritdoc
     */
    function getMimetype()
    {
        return $this->originAsset->getMimetype();
    }

    /**
     * @inheritdoc
     */
    function getSize()
    {
        if ($this->size)
            return $this->size;

        return $this->originAsset->getSize();
    }

    /**
     * @inheritdoc
     */
    function getLastModifiedTime()
    {
        if ($this->lastmodified)
            return $this->lastmodified;

        return $this->originAsset->getLastModifiedTime();
    }


    // Options:

    /**
     * Last Modified Datetime
     *
     * @param int $timestamp
     *
     * @return $this
     */
    function setLastModifiedTime(int $timestamp)
    {
        $this->lastmodified = $timestamp;
        return $this;
    }

    /**
     * file size in bytes
     *
     * @param int $size
     *
     * @return $this
     */
    function setSize(int $size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * MimeType Of Asset
     *
     * @param string $mime
     *
     * @return WrapperAsset
     */
    function setMimetype(string $mime)
    {
        $this->mimetype = $mime;
        return $this;
    }

    /**
     * The relative path for the source asset.
     *
     * @param string $uri
     *
     * @return $this
     */
    function setSourceUri(string $uri)
    {
        $this->sourceuri = $uri;
        return $this;
    }

    /**
     * Retrieve the full filename include extension
     *
     * @param string $filename
     *
     * @return $this
     */
    function setFilename(string $filename)
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * Set Stream
     *
     * @param iStreamable $stream
     *
     * @return $this
     */
    function setStream(iStreamable $stream)
    {
        $this->stream = $stream;
        return $this;
    }


    // ..

    function __call($name, $arguments)
    {
        if ( method_exists($this->originAsset, $name) )
            return call_user_func_array([$this->originAsset, $name], $arguments);


        trigger_error(
            'Call to undefined method '.__CLASS__.'::'.$name.'()'
            , E_USER_ERROR
        );
    }
}
