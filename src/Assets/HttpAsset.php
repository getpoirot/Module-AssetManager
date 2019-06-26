<?php
namespace Module\AssetManager\Assets;

use Poirot\Http\MimeResolver;
use Poirot\Std\Type\StdString;
use Poirot\Stream\Exception\ConnectionError;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Resource\AccessMode;
use Poirot\Stream\Streamable;
use Poirot\Stream\StreamWrapperClient;

use Module\AssetManager\Exceptions\AssetNotReadableError;
use Module\AssetManager\Interfaces\iAsset;


class HttpAsset
    implements iAsset
{
    protected $assetUri;
    /** @var iStreamable */
    protected $stream;

    /** @var array */
    protected $_httpHeaders;
    /** @var MimeResolver */
    protected $_mimeResolver;


    /**
     * HttpAsset
     *
     * @param string $assetUri
     */
    function __construct(string $assetUri)
    {
        if ( StdString::of($assetUri)->isStartWith('//') )
            $assetUri = 'http:' . $assetUri;

        $this->assetUri = $assetUri;
    }


    /**
     * @inheritdoc
     */
    function getStream()
    {
        if ($this->stream)
            return $this->stream;

        try
        {
            $streamClient = new StreamWrapperClient($this->assetUri, new AccessMode('bRB'));
            return $this->stream = new Streamable($streamClient->getConnect());
        }
        catch (ConnectionError $e)
        {
            throw new AssetNotReadableError(sprintf(
                'Read Error While Trying To Get Connect To "%s".'
                , $this->assetUri
            ), null, $e);
        }
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
        $mimeType = null;
        if ($mimeType = $this->_getHttpHeader('Content-Type')) {
            list($mimeType) = explode(' ', $mimeType);
            $mimeType = trim($mimeType, ';');
        }

        return $mimeType ?? $this->_mimeResolver()
                ->getMimeType( $this->getFilename() );
    }

    /**
     * @inheritdoc
     */
    function getSize()
    {
        $size = null;
        if ($size = $this->_getHttpHeader('Content-Length'))
            $size = (int) $size;

        return $size;
    }

    /**
     * @inheritdoc
     */
    function getLastModifiedTime()
    {
        $mtime = null;
        if ($mtime = $this->_getHttpHeader('Last-Modified'))
            $mtime = strtotime($mtime);


        return $mtime;
    }


    // ..

    /**
     * Get Http Headers By Make Http Request To Asset Uri
     *
     * @param string $headerKey
     *
     * @return string
     */
    function _getHttpHeader($headerKey)
    {
        if (! $this->_httpHeaders ) {
            @file_get_contents(
                $this->assetUri,
                false,
                stream_context_create(['http' => ['method' => 'HEAD']])
            );

            $this->_httpHeaders = $http_response_header; // magic variable contains header response
        }

        foreach ($this->_httpHeaders as $header) {
            if (StdString::of($header)->toLower()
                    ->isStartWith(strtolower($headerKey))
            ) {
                list(, $headerValue) = explode(':', $header, 2);

                return trim($headerValue);
            }
        }
    }

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
