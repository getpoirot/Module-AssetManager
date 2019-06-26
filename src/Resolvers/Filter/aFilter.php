<?php
namespace Module\AssetManager\Resolvers\Filter;

use Poirot\Stream\Interfaces\iStreamable;


abstract class aFilter
{
    /**
     * Filter Stream and Seek Pointer On Beginning
     *
     * @param iStreamable $stream
     * @param string      $mimetype
     *
     * @return iStreamable
     */
    abstract function filter(iStreamable $stream, $mimetype);
}
