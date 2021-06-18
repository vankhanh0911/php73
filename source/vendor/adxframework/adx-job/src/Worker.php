<?php

namespace ADXFW\Job;

use ADXFW\Job\Worker\Adapter;

class Worker
{
    public static function factory($adapter, array $config)
    {
        $adapterName = 'ADXFW\Job\Worker\Adapter\\';
        $adapterName .= ucwords(strtolower($adapter));

        /*
         * Create an instance of the adapter class.
         * Pass the config to the adapter class constructor.
         */
        $cacheAdapter = new $adapterName($config);

        /*
         * Verify that the object created is a descendent of the abstract adapter type.
         */
        if (!$cacheAdapter instanceof Adapter\AbstractWorker) {
            throw new \Exception("Adapter class '$adapterName' does not extend ADXFW_Job_Worker_Adapter_Abstract");
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

