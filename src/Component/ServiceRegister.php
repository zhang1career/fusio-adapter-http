<?php

namespace Fusio\Adapter\Http\Component;

use Fusio\Adapter\Http\Service\ConfigService;
use Fusio\Engine\Exception\ConfigurationException;
use Fusio\Engine\Exception\NotFoundException;
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

    private FileDb $db;

    /**
     * @throws NotFoundException
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

        $this->db = new FileDb(ConfigService::enval('REGISTER_SERVICE_DB', ''));
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
        if (!empty($serviceUries)) {
            $serviceUriList = explode(",", $serviceUries);
            // remove empty elements
            foreach ($serviceUriList as $_k => $_v) {
                if (!strlen($_v)) {
                    unset($serviceUriList[$_k]);
                }
            }
            // if empty after removing empty elements
            if (empty($serviceUriList)) {
                throw new ConfigurationException('Empty parsed from service register: ' . $serviceUries);
            }
            return $serviceUriList[array_rand($serviceUriList)];
        }
        // if not cached, query database
        $serviceUries = $this->db->query($serviceName);
        if (empty($serviceUries)) {
            throw new ConfigurationException('No data found from database: ' . $serviceName);
        }
        $serviceUriList = explode(",", $serviceUries);
        // remove empty elements
        foreach ($serviceUriList as $_k => $_v) {
            if (!strlen($_v)) {
                unset($serviceUriList[$_k]);
            }
        }
        // if empty after removing empty elements
        if (empty($serviceUriList)) {
            throw new ConfigurationException('Empty parsed from database: ' . $serviceUries);
        }
        return $serviceUriList[array_rand($serviceUriList)];
    }
}