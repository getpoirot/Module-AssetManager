<?php
namespace Module\AssetManager\Interfaces;


interface iWrapperResolver
    extends iAssetsResolver
{
    /**
     * iWrapperResolver Wrap a Resolver To Add Extra Functionality To That
     *
     * @param iAssetsResolver $resolver
     */
    function __construct(iAssetsResolver $resolver);
}
