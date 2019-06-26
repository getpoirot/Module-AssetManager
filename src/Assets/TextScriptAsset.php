<?php
namespace Module\AssetManager\Assets;

use Poirot\Http\MimeResolver;
use Poirot\Stream\Interfaces\iStreamable;

use Module\AssetManager\Interfaces\iAsset;


class TextScriptAsset
    implements iAsset
{
    /** @var iStreamable */
    protected $stream;
    protected $filename;
    protected $mimetype;
    protected $lastmodified;

    protected $_mimeResolver;


    /**
     * TextScriptAsset
     *
     * @param iStreamable $stream
     * @param string      $filename
     * @param string      $mimeType
     * @param null|int    $lastModified Timestamp
     */
    function __construct(
        iStreamable $stream,
        string $filename,
        string $mimeType = null,
        int $lastModified = null
    ) {
        $this->stream   = $stream;
        $this->filename = $filename;
        $this->mimetype = $mimeType;
        $this->lastmodified = $lastModified ?? time();
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
        // TODO get uri from stream meta if has
        return null;
    }

    /**
     * @inheritdoc
     */
    function getMimetype()
    {
        if (null === $this->mimetype)
            return $this->_mimeResolver()
                ->getMimeType( $this->getFilename() );


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
        return $this->lastmodified;
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
