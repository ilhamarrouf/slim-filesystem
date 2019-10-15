Slim Filesystem
===============

A simple filesystem for PHP slim framework like [Laravel Storage](https://laravel.com/docs/6.x/filesystem).

Getting Started
------------------
Slim Filesystem can be installed with Composer.

### With Composer

If you're already using Composer, just add `ilhamarrouf/slim-filesystem` to your `composer.json` file.
Slim Filesystem works with Composer's autoloader out of the bat.
```js
{
	"require": {
		"ilhamarrouf/slim-filesystem": "0.1.0"
	}
}
```
Or

`composer require ilhamarrouf/slim-filesystem`

### Usage
Basic example usage package on Slim 3.x
```php
$settings = [
    'settings' => [
        'filesystem' => [
            'default' => 'cloud',
            'cloud' => 'minio',
            'disks' => [
                'public' => [
                    'driver' => 'local',
                    'root' => __DIR__.'/storage/',
                    'url' => $_SERVER['HTTP_HOST'].'/storage',
                    'visibility' => 'public',
                ],
                's3' => [
                    'driver' => 's3',
                    'key' => 'superkey',
                    'secret' => 'supersecret',
                    'region' => 'us-east-1',
                    'bucket' => 'test',
                    'url' => 'http://host-to-aws-s3',
                ],
                'minio' => [
                    'driver' => 's3',
                    'endpoint' => '127.0.0.1:9000',
                    'use_path_style_endpoint' => true,
                    'key' => 'superpersonalket',
                    'secret' => 'superpersonalsecret',
                    'region' => 'us-east-1',
                    'bucket' => 'tms',
                ],
            ]
        ],
    ],
];

$app = new \Slim\App($settings);

$container = $app->getContainer();

$container['storage'] = function ($container) {
    return new \Ilhamarrouf\Filesystem\FilesystemManager($container);
};
```