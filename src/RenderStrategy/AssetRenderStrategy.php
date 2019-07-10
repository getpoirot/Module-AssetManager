<?php
namespace Module\AssetManager\RenderStrategy;

use Poirot\Application\Sapi\Event\EventHeapOfSapi;
use Poirot\Events\Interfaces\iEvent;

use Module\HttpRenderer\Interfaces\iRenderStrategy;
use Module\HttpFoundation\Events\Listener\ListenerDispatchResult;

use Module\AssetManager\Actions;
use Module\AssetManager\Interfaces\iAsset;


class AssetRenderStrategy
    implements iRenderStrategy
{
    const PRIORITY_RENDER = 500;


    /**
     * Initialize To Events
     *
     * - usually bind listener(s) to events
     *
     * @param EventHeapOfSapi|iEvent $events
     *
     * @return $this
     * @throws \Exception
     */
    function attachToEvent(iEvent $events)
    {
        $events
            ->on(
                EventHeapOfSapi::EVENT_APP_RENDER
                , function ($result = null) {
                    if (! $this->isRenderable($result) )
                        return false;

                    // change the "result" param value inside event
                    return [
                        ListenerDispatchResult::RESULT_DISPATCH => $this->makeResponse($result)
                    ];
                }, self::PRIORITY_RENDER
            )
        ;

        return $this;
    }

    /**
     * @inheritdoc
     */
    function makeResponse($result, $_ = null)
    {
        /** @var iAsset $asset */
        $asset = $result;

        if (
            $asset->getStream()
                ->resource()->isSeekable()
        )
            $asset->getStream()->rewind();

        return Actions::makeResponseFromAsset($asset);
    }

    /**
     * @inheritdoc
     */
    function makeErrorResponse(\Exception $exception, $_ = null)
    {
        throw new \RuntimeException('Method should not Implemented.');
    }

    /**
     * @inheritdoc
     */
    function getContentType()
    {
        throw new \RuntimeException('Method not Implemented.');
    }

    /**
     * @inheritdoc
     */
    function isRenderable($result)
    {
        return $result instanceof iAsset;
    }
}
