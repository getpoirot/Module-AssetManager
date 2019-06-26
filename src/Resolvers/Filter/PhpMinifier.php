<?php
namespace Module\AssetManager\Resolvers\Filter;

if (! class_exists('\marcocesarato\minifier\Minifier') )
    require_once __DIR__ . '/PhpMinifier/Minifier.php';


use marcocesarato\minifier\Minifier;

use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Streamable\STemporary;


class PhpMinifier
    extends aFilter
{
    /**
     * Filter Stream and Seek Pointer On Beginning
     *
     * @param iStreamable $stream
     * @param string $mimetype
     *
     * @return iStreamable
     * @throws \Exception
     */
    function filter(iStreamable $stream, $mimetype)
    {
        $content  = $stream->read();

        $minifier = new Minifier;
        switch ($mimetype) {
            case 'application/javascript':
                $minified = $minifier->minifyJS($content);
                break;
            case 'text/css':
                $minified = $minifier->minifyCSS($content);
                break;
            case 'text/html':
                $minified = $minifier->minifyHTML($content);
                break;
            default:
                $minified = $content;
        }


        return new STemporary($minified);
    }
}
