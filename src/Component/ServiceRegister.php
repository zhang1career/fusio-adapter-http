<?php

namespace Fusio\Adapter\Http\Component;

use Fusio\Adapter\Http\Service\ConfigService;
use Fusio\Engine\Exception\ConfigurationException;
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
     * @throws ConfigurationException
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
     * Parse a comma-separated list of service uris, remove empty entries and return one random uri.
     * Throws ConfigurationException if the resulting list is empty.
     *
     * @param string $serviceUries
     * @return string
     * @throws ConfigurationException
     */
    private function pickServiceUriFromString(string $serviceUries): string
    {
        $serviceUriList = explode(',', $serviceUries);
        // remove empty elements and trim values
        $serviceUriList = array_values(array_filter(array_map('trim', $serviceUriList), function (string $v): bool {
            return $v !== '';
        }));

        if (empty($serviceUriList)) {
            throw new ConfigurationException('Empty from parsing: ' . $serviceUries);
        }

        return $serviceUriList[array_rand($serviceUriList)];
    }
}