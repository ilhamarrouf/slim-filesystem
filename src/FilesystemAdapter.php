<?php
/**
 * Created by PhpStorm.
 * User: ilhamarrouf
 * Date: 12/09/19
 * Time: 18.21
 */

namespace Ilhamarrouf\Filesystem;

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FilesystemInterface;

class FilesystemAdapter
{
    public $driver;

    public function __construct(FilesystemInterface $driver)
    {
        $this->driver = $driver;
    }

    public function temporaryUrl($path, $expiration, array $options = [])
    {
        $adapter = $this->driver->getAdapter();

        if (method_exists($adapter, 'getTemporaryUrl')) {
            return $adapter->getTemporaryUrl($path, $expiration, $options);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsTemporaryUrl($adapter, $path, $expiration, $options);
        } else {
            throw new \RuntimeException('This driver does not support creating temporary URLs.');
        }
    }

    public function getAwsTemporaryUrl($adapter, $path, $expiration, $options) : string
    {
        $client = $adapter->getClient();

        $command = $client->getCommand('GetObject', array_merge([
            'Bucket' => $adapter->getBucket(),
            'Key' => $adapter->getPathPrefix().$path,
        ], $options));

        return (string) $client->createPresignedRequest(
            $command, $expiration
        )->getUri();
    }

    public function __call($method, array $parameters)
    {
        return call_user_func_array([$this->driver, $method], $parameters);
    }
}