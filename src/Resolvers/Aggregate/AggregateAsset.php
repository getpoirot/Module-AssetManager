<?php
namespace Module\AssetManager\Resolvers\Aggregate;

use Poirot\Http\MimeResolver;
use Poirot\Stream\Streamable;

use Module\AssetManager\Interfaces\iAsset;


class AggregateAsset
    implements iAsset
{
    protected $stream;
    /** @var MimeResolver */
    protected $_mimeResolver;
    protected $filename;
    protected $mimetype;
    protected $mtime;


    /**
     * AggregateAsset
     *
     * @param Streamable\SAggregateStreams $stream
     * @param string $filename
     * @param string|null $mimetype
     * @param int $lastModifiedTime
     */
    function __construct(
        Streamable\SAggregateStreams $stream,
        string $filename,
        string $mimetype = null,
        int $lastModifiedTime = null
    ) {
        $this->stream = $stream;
        $this->filename = $filename;
        $this->mimetype = $mimetype;
        $this->mtime = $lastModifiedTime ?? time();
    }


    /**
     * @inheritdoc
     */
    function getStream()
    {
        return $this->stream;
    }

    /**
     * @inheritdoc
     */
    function getFilename()
    {
        return $this->filename;
    }

    /**
     * @inheritdoc
     */
    function getSourceUri()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    function getMimetype()
    {
        if (null === $this->mimetype)
            $this->mimetype = $this->_mimeResolver()
                ->getMimeType($this->getFilename());

        return $this->mimetype;
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
        return $this->mtime;
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
