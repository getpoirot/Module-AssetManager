<?php
namespace Module\AssetManager\Resolvers;

use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Type\StdString;
use Poirot\Std\Traits\tConfigurable;
use Poirot\Std\Traits\tConfigurableSetter;

use Module\AssetManager\Interfaces\iAssetsResolver;
use Module\AssetManager\Interfaces\iWrapperResolver;


class PathPrefixResolver
    implements iWrapperResolver
{
    use tConfigurable;
    use tConfigurableSetter;

    /** @var iAssetsResolver */
    protected $originResolver;
    /** @var string */
    protected $pathPrefix;


    /**
     * iWrapperResolver Wrap a Resolver To Add Extra Functionality To That
     *
     * @param iAssetsResolver $resolver
     */
    function __construct(iAssetsResolver $resolver)
    {
        $this->originResolver = $resolver;
    }


    /**
     * {@inheritDoc}
     */
    function resolve(iHttpRequest $request)
    {
        $uri      = $request->getTarget();
        $uriStrip = (string) StdString::of($uri)
            ->stripPrefix( $this->getPathPrefix() );

        if ($uriStrip == $uri)
            // dose'nt contains the prefix
            return null;

        $request = clone $request;
        $request->setTarget($uriStrip);
        return $this->originResolver->resolve($request);
    }

    /**
     * Collect All Resolved Assets That This Resolver Can Provide
     *
     * @return \Traversable
     */
    function collectAssets()
    {
        return $this->originResolver->collectAssets();
    }


    // Options:

    /**
     * Path Prefix To Match With and Strip For Origin Resolver
     *
     * @param string $prefixPath
     *
     * @return $this
     */
    function setPathPrefix(string $prefixPath)
    {
        $this->pathPrefix = '/' . trim($prefixPath, '/');

        return $this;
    }

    /**
     * Path Prefix
     *
     * @return string
     */
    function getPathPrefix()
    {
        return $this->pathPrefix;
    }
}
