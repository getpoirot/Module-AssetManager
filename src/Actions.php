<?php
namespace Module\AssetManager
{
    use Poirot\Http\Interfaces\iHttpResponse;

    use Module\AssetManager\Interfaces\iAsset;


    /**
     * @method static iHttpResponse makeResponseFromAsset(iAsset $asset)
     */
    class Actions extends \IOC
    {
        const MakeResponseFromAsset = 'makeResponseFromAsset';
    }
}
