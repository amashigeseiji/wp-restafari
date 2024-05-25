<?php
namespace Wp\Resta;

use Wp\Resta\DI\Container;
use Wp\Resta\OpenApi\ResponseSchema;
use Wp\Resta\OpenApi\Doc;
use Wp\Resta\REST\Route;
use Wp\Resta\Config;

class Resta
{
    public function init(array $restaConfig)
    {
        $config = new Config($restaConfig);
        $container = Container::getInstance();
        $container->bind(Config::class, $config);
        $dependencies = $config->get('dependencies') ?: [];
        foreach ($dependencies as $interface => $dependency) {
            if (is_string($interface)) {
                $container->bind($interface, $dependency);
            } else {
                $container->bind($dependency);
            }
        }

        add_action('rest_api_init', function () use ($container) {
            /** @var Route */
            $routes = $container->get(Route::class);
            $routes->register();
        });

        add_action('init', function() use ($container) {
            $container->get(Doc::class)->init();
            $container->get(ResponseSchema::class)->init();
        });
    }
}