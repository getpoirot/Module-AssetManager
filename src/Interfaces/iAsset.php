<?php
namespace Module\AssetManager\Interfaces;

use Poirot\Stream\Interfaces\iStreamable;

use Module\AssetManager\Exceptions\AssetNotReadableError;


interface iAsset
{
    /**
     * Get Streamed Object Of Uploaded File
     *
     * @return iStreamable
     * @throws AssetNotReadableError
     */
    function getStream();

    /**
     * Retrieve the full filename include extension
     *
     * @return string
     */
    function getFilename();

    /**
     * Returns the relative path for the source asset.
     *
     * @return string|null
     */
    function getSourceUri();

    /**
     * Get MimeType Of Asset
     *
     * @return string
     * @throws AssetNotReadableError
     */
    function getMimetype();

    /**
     * Retrieve the file size in bytes
     *
     * @return int|null
     * @throws AssetNotReadableError
     */
    function getSize();

    /**
     * Get Last Modified Datetime
     *
     * @return int|null Last modified datetime in unix time if available
     * @throws AssetNotReadableError
     */
    function getLastModifiedTime();
}
