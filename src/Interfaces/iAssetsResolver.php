<?php
namespace Module\AssetManager\Interfaces;

use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Std\Interfaces\Pact\ipConfigurable;


interface iAssetsResolver
    extends ipConfigurable
{
    /**
     * Resolve To Asset By Request
     *
     * @param iHttpRequest $request
     *
     * @return iAsset|null
     */
    function resolve(iHttpRequest $request);

    /**
     * Collect All Assets That This Resolver Provide
     *
     * @return \Traversable
     */
    function collectAssets();
}
