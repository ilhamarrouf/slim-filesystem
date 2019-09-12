<?php
/**
 * Created by PhpStorm.
 * User: ilhamarrouf
 * Date: 12/09/19
 * Time: 21.57
 */

// autoload
require __DIR__ . '/../vendor/autoload.php';

// Boot configuration
$settings = [
    'settings' => [
        'displayErrorDetails' => true,
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
    ]
];

// Initialize app
$app = new \Slim\App($settings);

// Get container
$container = $app->getContainer();

// Register filesystem to container
$container['storage'] = function ($container) {
    return new \Ilhamarrouf\Filesystem\FilesystemManager($container);
};

// Sample route
$app->get('/cloud', function (\Slim\Http\Request $request, \Slim\Http\Response $response, array $args) use ($container) {
    return $response->withJson([
       'image' => $container->storage->cloud()->temporaryUrl('path/to/file/test.png', time()+ 60*60),
    ], \Slim\Http\StatusCode::HTTP_OK);
});

$app->post('/cloud', function (\Slim\Http\Request $request, \Slim\Http\Response $response, array $args) use ($container) {
    $files = collect($request->getUploadedFiles())->map(function ($file) use ($container) {
        $container->storage->cloud()->put(
            $fileName = $file->getClientFilename(),
            $file->getStream()
        );

        return $container->storage->disk('minio')->temporaryUrl($fileName, time()+ 60*60);
    });

    return $response->withJson([
        'data' => $files
    ], \Slim\Http\StatusCode::HTTP_OK);
});

$app->post('/public/local', function (\Slim\Http\Request $request, \Slim\Http\Response $response, array $args) use ($container) {
    collect($request->getUploadedFiles())->map(function ($file) use ($container) {
        $test = $container->storage->disk('public')->put(
            $file->getClientFilename(),
            $file->getStream()
        );

        var_dump($test);
    });
});

// Run application
$app->run();