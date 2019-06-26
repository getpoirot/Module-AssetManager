<?php
namespace Module\AssetManager\Events\Listener;

use Poirot\Application\aSapi;
use Poirot\Events\Event;
use Poirot\Events\Listener\aListener;
use Poirot\Http\Interfaces\iHttpRequest;
use Poirot\Psr7\HttpRequest;
use Poirot\Router\Interfaces\iRouterStack;

use Module\AssetManager\Services;
use Module\HttpFoundation\Events\Listener\ListenerDispatch;
use Module\HttpFoundation\Events\Listener\ListenerMatchRequest as DefaultListenerMatchRequest;


class ListenerMatchAsset
    extends aListener
{
    const WEIGHT = DefaultListenerMatchRequest::WEIGHT - 100; // after match request


    /**
     * Match Request
     *
     * @param aSapi $sapi
     * @param iRouterStack|mixed $route_match
     * @param Event $e
     *
     * @return array
     * @throws \Exception
     */
    function __invoke($sapi = null, $route_match = null, $e = null)
    {
        if ( $route_match )
            // route matched; nothing to do
            return null;


        $services = $sapi->services();


        ## Match Http Request Against Router
        #
        /** @var HttpRequest $request */
        $request  = $services->fresh(iHttpRequest::class);

        if ($asset = Services::AssetResolver()->resolve($request))
        {
            $e->stopPropagation();

            // Pass Result as a Param To Event
            return [
                ListenerDispatch::RESULT_DISPATCH => $asset
            ];
        }
    }
}
