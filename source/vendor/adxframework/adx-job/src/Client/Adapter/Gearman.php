<?php

namespace ADXFW\Job\Client\Adapter;

use ADXFW\Job;
use Zend\Json;

class Gearman extends AbstractClient
{
    /**
     * Default Values
     */
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 4730;
    const DEFAULT_TIMEOUT = 5000;
    const DEFAULT_ENCODE = 'serialize';

    /**
     * GearmanClient instance
     *
     * @var GearmanClient
     */
    private $_client = null;

    /**
     * Available options
     *
     * =====> (array) servers :
     * an array of job client server ; each job client server is described by an associative array :
     * 'host' => (string) : the name of the job client server
     * 'port' => (int) : the port of the job client server
     * 'timeout' => (int) : value in seconds which will be used for connecting to the daemon. Think twice
     *                      before changing the default value of 1 second - you can lose all the
     *                      advantages of caching if your connection is too slow.
     *
     * @var array available options
     */
    protected $_options = array(
        'servers' => array(array(
            'host' => self::DEFAULT_HOST,
            'port' => self::DEFAULT_PORT
        )),
        'timeout' => self::DEFAULT_TIMEOUT,
        'encode' => self::DEFAULT_ENCODE
    );

    /**
     * Constructor
     *
     * @param array $options associative array of options
     * @return void
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->_client = new \GearmanClient();
        
        foreach ($this->_options['servers'] as $server) {
            if (!array_key_exists('host', $server)) {
                $server['port'] = self::DEFAULT_HOST;
            }
            if (!array_key_exists('port', $server)) {
                $server['port'] = self::DEFAULT_PORT;
            }
            $this->_client->addServer($server['host'], $server['port']);
        }
        $this->_client->setTimeout($this->_options['timeout']);
    }

    /**
     * Make data
     * @param <array> $arrData
     * @return <mixed>
     */
    private function makeWorkLoad($arrData)
    {
        //Check encode type
        if ($this->_options['encode'] == 'serialize') {
            $arrData = serialize($arrData);
        } else {
            $arrData = Json\Json::encode($arrData);
        }

        //Return data
        return $arrData;
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        //Cleanup
        unset($this->_client);
    }

    /**
     * Run background register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    public function doBackgroundTask($register_function, $array_data, $unique = null)
    {
        //Run job background to server
        $job_handle = $this->_client->doBackground($register_function, $this->makeWorkLoad($array_data), $unique);

        //If error
        if ($this->_client->returnCode() != GEARMAN_SUCCESS) {
            throw new \Exception("Add Job unsuccess", $this->_client->returnCode());
        }

        //Return value
        return array('jobhandle' => $job_handle);
    }

    /**
     * Run background register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    public function doHighBackgroundTask($register_function, $array_data, $unique = null)
    {
        //Run job background to server
        $job_handle = $this->_client->doHighBackground($register_function, $this->makeWorkLoad($array_data), $unique);

        //If error
        if ($this->_client->returnCode() != GEARMAN_SUCCESS) {
            throw new \Exception("Add Job unsuccess", $this->_client->returnCode());
        }

        //Return value
        return array('jobhandle' => $job_handle);
    }

    /**
     * Run background register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    public function doLowBackgroundTask($register_function, $array_data, $unique = null)
    {
        //Run job background to server
        $job_handle = $this->_client->doLowBackground($register_function, $this->makeWorkLoad($array_data), $unique);

        //If error
        if ($this->_client->returnCode() != GEARMAN_SUCCESS) {
            throw new Job\Exception("Add Job unsuccess", $this->_client->returnCode());
        }

        //Return value
        return array('jobhandle' => $job_handle);
    }

    /**
     * Run foreground register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    public function doTask($register_function, $array_data, $unique = null)
    {
        do {
            //Run job background to server
            $job_handle = $this->_client->doNormal($register_function, $this->makeWorkLoad($array_data), $unique);

            //Check error
            switch ($this->_client->returnCode()) {
                case GEARMAN_WORK_DATA:
                    break;
                case GEARMAN_SUCCESS:
                    break;
                case GEARMAN_WORK_FAIL:
                    return array('status' => false);
                    break;
                case GEARMAN_WORK_STATUS:
                    return array('status' => $this->_client->doStatus());
                    break;
                default:
                    throw new \Exception("Add Job unsuccess", $this->_client->error());
            }
        } while ($this->_client->returnCode() != GEARMAN_SUCCESS);

        //Return value
        return array('jobhandle' => $job_handle);
    }

    /**
     * Run foreground register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    public function doHighTask($register_function, $array_data, $unique = null)
    {
        do {
            //Run job background to server
            $job_handle = $this->_client->doHigh($register_function, $this->makeWorkLoad($array_data), $unique);

            //Check error
            switch ($this->_client->returnCode()) {
                case GEARMAN_WORK_DATA:
                    break;
                case GEARMAN_SUCCESS:
                    break;
                case GEARMAN_WORK_FAIL:
                    return array('status' => false);
                    break;
                case GEARMAN_WORK_STATUS:
                    return array('status' => $this->_client->doStatus());
                    break;
                default:
                    throw new \Exception("Add Job unsuccess", $this->_client->error());
            }
        } while ($this->_client->returnCode() != GEARMAN_SUCCESS);

        //Return value
        return array('jobhandle' => $job_handle);
    }

    /**
     * Run foreground register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    public function doLowTask($register_function, $array_data, $unique = null)
    {
        do {
            //Run job background to server
            $job_handle = $this->_client->doLow($register_function, $this->makeWorkLoad($array_data), $unique);

            //Check error
            switch ($this->_client->returnCode()) {
                case GEARMAN_WORK_DATA:
                    break;
                case GEARMAN_SUCCESS:
                    break;
                case GEARMAN_WORK_FAIL:
                    return array('status' => false);
                    break;
                case GEARMAN_WORK_STATUS:
                    return array('status' => $this->_client->doStatus());
                    break;
                default:
                    throw new \Exception("Add Job unsuccess", $this->_client->error());
            }
        } while ($this->_client->returnCode() != GEARMAN_SUCCESS);

        //Return value
        return array('jobhandle' => $job_handle);
    }

}

