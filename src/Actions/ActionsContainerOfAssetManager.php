<?php
namespace Module\AssetManager\Actions;

use Poirot\Ioc\Container\BuildContainer;

use Module\AssetManager\Actions;


class ActionsContainerOfAssetManager
    extends BuildContainer
{
    protected function __init()
    {
        $this->setServices([
            Actions::MakeResponseFromAsset => MakeResponseFromAsset::class,
        ]);
    }
}
