<?php
namespace Module\AssetManager\Resolvers\Cache;

use Module\AssetManager\Assets\SerializableStreamAsset;
use Module\AssetManager\Interfaces\iAsset;

/*
$cache = new FilesystemCache(__DIR__ . '/www');
$cacheKey = md5('animate.css');
if ($cache->has($cacheKey)) {
    $downStreamasset = $cache->get($cacheKey);
    echo($downStreamasset->getStream()->read());
    die;
}

$glob = new GlobResolver;
$glob->with(['globs' => [
    __DIR__ . '/www/css/*.css',
]]);

$resolver = (new CollectionResolver)
    ->setResolvers($glob);

$resolved = $resolver->resolve(\IOC::GetIoC()->get('httpRequest'));
if ($resolved) {
    $cache->set($cacheKey, $resolved);
}

die('refresh');
*/

class FilesystemCache
{
    /** @var string */
    protected $cacheDir;


    /**
     * FilesystemCache
     *
     * @param $cacheDir
     */
    function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '\\/');
    }


    /**
     * Get Stored Value in Cache
     *
     * @param string     $key
     * @param mixed|null $default
 * @return SerializableStreamAsset
     * @throws \InvalidArgumentException
     */
    function get($key, $default = null)
    {
        $this->_assertValidateKey($key);

        $path = $this->cacheDir.'/'.$key.'.obj';
        if (! file_exists($path) )
            return $default;

        $assetSerialized = file_get_contents($path);
        return unserialize($assetSerialized);
    }

    /**
     * Set Value To Store In Cache
     *
     * @param string   $key
     * @param iAsset   $asset
     *
     * @return mixed|iAsset
     */
    function set($key, iAsset $asset)
    {
        $this->_assertValidateKey($key);

        if (! is_dir($this->cacheDir) ) {
            $uMask = umask(0);
            if (false === @mkdir($this->cacheDir, 0777, true))
                throw new \RuntimeException('Unable to create directory '.$this->cacheDir);

            umask($uMask);
        }


        $path = $this->cacheDir.'/'.$key.'.obj';

        $downloadStream = $this->_newDownloadStreamByKey($asset, $key);
        $serializedAsset = serialize($downloadStream);
        if (false === @file_put_contents($path, $serializedAsset))
            throw new \RuntimeException('Unable to write file '.$path);

        return $asset;
    }

    /**
     * Removes a value from the cache.
     *
     * @param string $key A unique key
     */
    function delete($key)
    {
        $this->_assertValidateKey($key);

        $path = $this->cacheDir.'/'.$key;
        if ( file_exists($path) && false === @unlink($path) )
            throw new \RuntimeException('Unable to remove file '.$path);
    }

    /**
     * Has Cache Exists For This Key?
     *
     * @param bool $key
     *
     * @return bool
     */
    function has($key)
    {
        $this->_assertValidateKey($key);

        return file_exists($this->cacheDir.'/'.$key);
    }


    // ..

    /**
     * New Download Stream Instance
     *
     * @param iAsset $asset
     * @param string $key
     *
     * @return SerializableStreamAsset
     */
    protected function _newDownloadStreamByKey(iAsset $asset, string $key)
    {
        $path = $this->cacheDir.'/'.$key;
        return new SerializableStreamAsset($asset, $path);
    }

    /**
     * Assert Validate Cache Key
     *
     * @param string $key
     *
     * @throws \InvalidArgumentException
     */
    protected function _assertValidateKey(string $key)
    {
        if (1 !== preg_match('/[a-z_\-0-9]/i', $key) )
            throw new \InvalidArgumentException(sprintf('Invalid Key "%s".', $key));
    }
}
