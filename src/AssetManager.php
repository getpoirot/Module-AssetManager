<?php
namespace Module\AssetManager;

use Poirot\Application\Sapi\Event\EventHeapOfSapi;
use Poirot\Events\Interfaces\iCorrelatedEvent;
use Poirot\Events\Interfaces\iEvent;
use Poirot\Events\Interfaces\iEventHeap;

use Module\AssetManager\Events\Listener\ListenerMatchAsset;


class AssetManager
    implements iCorrelatedEvent
{
    /**
     * @inheritdoc
     */
    function attachToEvent(iEvent $event)
    {
        /** @var iEventHeap $events */
        $events = $event;

        $events->on(
            EventHeapOfSapi::EVENT_APP_MATCH_REQUEST
            , new ListenerMatchAsset
            , ListenerMatchAsset::WEIGHT // after match request
        );
    }
}
