<?php

namespace ADXFW\Job;

use ADXFW\Job\Client\Adapter;

class Client
{

    public static function factory($adapter, array $config)
    {
        $adapterName = 'ADXFW\Job\Client\Adapter\\';
        $adapterName .= ucwords(strtolower($adapter));
        /*
         * Create an instance of the adapter class.
         * Pass the config to the adapter class constructor.
         */
        $cacheAdapter = new $adapterName($config);

        /*
         * Verify that the object created is a descendent of the abstract adapter type.
         */
        if (!$cacheAdapter instanceof Adapter\AbstractClient) {
            throw new \Exception("Adapter class '$adapterName' does not extend ADXFW_Job_Client_Adapter_Abstract");
        }

        return $cacheAdapter;
    }

    /**
     * Clone function
     *
     */
    private final function __clone()
    {

    }

}

