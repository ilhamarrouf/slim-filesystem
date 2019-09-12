<?php
/**
 * Created by PhpStorm.
 * User: ilhamarrouf
 * Date: 12/09/19
 * Time: 17.24
 */

namespace Ilhamarrouf\Filesystem;

use Aws\S3\S3Client;
use Illuminate\Support\Arr;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Slim\Container;

class FilesystemManager
{
    public $container;

    public $disks = [];

    public $customCreators = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function set($name, $disk)
    {
        $this->disks[$name] = $disk;

        return $this;
    }

    public function get($name)
    {
        return $this->disks[$name] ?? $this->resolve($name);
    }

    protected function getConfig($name)
    {
        return $this->container['settings']['filesystem']['disks'][$name];
    }

    public function disk($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->disks[$name] = $this->get($name);
    }

    public function cloud()
    {
        $name = $this->getDefaultCloudDriver();

        return $this->disks[$name] = $this->get($name);
    }

    public function getDefaultDriver()
    {
        return $this->container['settings']['filesystem']['default'];
    }

    public function getDefaultCloudDriver()
    {
        return $this->container['settings']['filesystem']['cloud'];
    }

    public function resolve($name)
    {
        $config = $this->getConfig($name);

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($config);
        } else {
            throw new \InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
        }
    }

    public function createS3Driver(array $config)
    {
        $s3Config = $this->formatS3Config($config);

        $root = $s3Config['root'] ?? null;

        $options = $config['options'] ?? [];

        return $this->adapt($this->createFlysystem(
            new AwsS3Adapter(new S3Client($s3Config), $s3Config['bucket'], $root, $options), $config
        ));
    }

    public function createLocalDriver(array $config)
    {
        $permissions = $config['permissions'] ?? [];

        $links = ($config['links'] ?? null) === 'skip'
            ? LocalAdapter::SKIP_LINKS
            : LocalAdapter::DISALLOW_LINKS;

        return $this->adapt($this->createFlysystem(new LocalAdapter(
            $config['root'], $config['lock'] ?? LOCK_EX, $links, $permissions
        ), $config));
    }

    protected function formatS3Config(array $config)
    {
        $config += ['version' => 'latest'];

        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return $config;
    }

    public function adapt(FilesystemInterface $filesystem)
    {
        return new FilesystemAdapter($filesystem);
    }

    protected function createFlysystem(AdapterInterface $adapter, array $config)
    {
        return new Filesystem($adapter, count($config) > 0 ? $config : null);
    }

    public function __call($method, $parameters)
    {
        return $this->disk()->$method(...$parameters);
    }
}