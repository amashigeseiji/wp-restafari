<?php
namespace Wp\Resta;

class Config
{
    private readonly array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function get(string $key)
    {
        return $this->config[$key] ?? null;
    }

    public function hasKey(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }
}