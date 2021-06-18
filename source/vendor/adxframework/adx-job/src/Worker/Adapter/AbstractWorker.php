<?php
namespace ADXFW\Job\Worker\Adapter;

abstract class AbstractWorker
{

    /**
     * Available options
     *
     * @var array available options
     */
    protected $_options = array();

    /**
     * Constructor
     *
     * @param  array $options Associative array of options
     * @throws ADXFW_Job_Client_Exception
     * @return void
     */
    public function __construct(array $options = array())
    {
        while (list($name, $value) = each($options)) {
            $this->setOption($name, $value);
        }
    }

    /**
     * Set an option
     *
     * @param  string $name
     * @param  mixed $value
     * @throws ADXFW_Job_Client_Exception
     * @return void
     */
    public function setOption($name, $value)
    {

        if (array_key_exists($name, $this->_options)) {
            $this->_options[$name] = $value;
        }
    }

    /**
     * Add function to worker
     * @param string $register_function
     * @param string $callback_function
     * @param var $args
     */
    abstract protected function addFunction($register_function, $callback_function, $args = null);

    /**
     * Get Notify Data in worker
     * @param GearmanJob $job Or memcacheq key
     */
    abstract protected function getNotifyData($job);

    /**
     * Run worker
     */
    abstract protected function run();
}

