<?php
namespace ADXFW\Job\Worker\Adapter;

use Zend\Json;

class Gearman extends AbstractWorker
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
    private $_worker = null;

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
        'encode' => self::DEFAULT_ENCODE,
        'debug' => false
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
        //Return object class
        $this->_worker = new \GearmanWorker();
        //Add options
        $this->_worker->addOptions(GEARMAN_WORKER_NON_BLOCKING);
        //Set timeout
        $this->_worker->setTimeout($this->_options['timeout']);
        //Set server
        foreach ($this->_options['servers'] as $server) {
            if (!array_key_exists('host', $server)) {
                $server['port'] = self::DEFAULT_HOST;
            }
            if (!array_key_exists('port', $server)) {
                $server['port'] = self::DEFAULT_PORT;
            }
            $this->_worker->addServer($server['host'], $server['port']);
        }
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        //Cleanup
        unset($this->_worker);
    }

    /**
     * Add function to worker
     * @param string $register_function
     * @param string $callback_function
     * @param var $args
     */
    public function addFunction($register_function, $callback_function, $args = null)
    {
        $this->_worker->addFunction($register_function, $callback_function, $args);
    }

    /**
     * Run worker
     */
    public function run()
    {
        //Start worker
        echo " === Waiting for job...\n";

        //Loop to detect jobs
        while (@$this->_worker->work() || ($this->_worker->returnCode() == GEARMAN_IO_WAIT) || ($this->_worker->returnCode() == GEARMAN_NO_JOBS)) {
            //Debug information
            if ($this->_options['debug']) {
                echo " === (" . time() . ") Status code: " . $this->_worker->returnCode() . "\n";
            }

            //Work OK
            if ($this->_worker->returnCode() == GEARMAN_SUCCESS) {
                continue;
            }

            //Wait sometime
            if (!@$this->_worker->wait()) {
                if ($this->_worker->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
                    //Debug information
                    if ($this->_options['debug']) {
                        echo " === (" . time() . ") Sleep 10 seconds: " . GEARMAN_NO_ACTIVE_FDS . "\n";
                    }

                    //Sleep sometime second to free resource
                    sleep(10);
                }
            }
        }

        //Unregister all function
        $this->_worker->unregisterAll();
    }

    /**
     * Get Notify Data in worker
     * @param GearmanJob $job
     */
    public function getNotifyData($job)
    {
        //Check encode type
        if ($this->_options['encode'] == 'serialize') {
            $arrData = unserialize($job->workload());
        } else {
            $arrData = Json\Json::decode($job->workload(), true);
        }

        //Return data
        return $arrData;
    }

    /**
     * Get Notify handler in worker
     * @param GearmanJob $job
     */
    public function getNotifyHandler($job)
    {
        return $job->handle();
    }

}

