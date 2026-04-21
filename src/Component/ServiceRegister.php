<?php

namespace Fusio\Adapter\Http\Component;

use Fusio\Adapter\Http\Service\ConfigService;
use Fusio\Engine\Exception\ConfigurationException;
use Paganini\ServiceDiscovery\ServiceUriList;
use Predis\Client as PredisClient;

class ServiceRegister
{
    private static ?ServiceRegister $instance = null;

    public static function getInstance(): ServiceRegister
    {
        if (!isset(self::$instance)) {
            self::$instance = new ServiceRegister();
        }

        return self::$instance;
    }

    private PredisClient $cache;

    /**
     */
    private function __construct()
    {
        $this->cache = new PredisClient([
            'scheme' => ConfigService::enval('REDIS_SCHEME', 'tcp'),
            'host' => ConfigService::enval('REDIS_HOST', 'localhost'),
            'port' => ConfigService::enval('REDIS_PORT', 6379),
        ], [
            'prefix' => ConfigService::enval('REDIS_PREFIX_REGISTER_SERVICE', ''),
        ]);
    }

    /**
     * Query the service-uri by service-name
     * @param string $serviceName
     * @return string
     * @throws ConfigurationException
     */
    public function queryServiceUri(string $serviceName): string
    {
        $serviceUries = $this->cache->get($serviceName);
        // if cached
        if (empty($serviceUries)) {
            throw new ConfigurationException('No data found for service: ' . $serviceName);
        }
        return $this->pickServiceUriFromString($serviceUries);
    }

    /**
     * Parse a comma-separated list of service uris (Fusio-compatible) and return one random uri.
     * Delegates to {@see ServiceUriList}.
     *
     * @param string $serviceUries
     * @return string
     * @throws ConfigurationException
     */
    private function pickServiceUriFromString(string $serviceUries): string
    {
        $serviceUriList = ServiceUriList::parseCommaSeparated($serviceUries);
        if ($serviceUriList === []) {
            throw new ConfigurationException('Empty from parsing: ' . $serviceUries);
        }

        return ServiceUriList::pickRandom($serviceUriList);
    }
}