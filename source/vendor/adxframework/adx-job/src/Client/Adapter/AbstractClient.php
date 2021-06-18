<?php

namespace ADXFW\Job\Client\Adapter;

abstract class AbstractClient
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
        if(!empty($options)){
            foreach($options as $name => $value){
                $this->setOption($name, $value);
            }
        }
        // while (list($name, $value) = each($options)) {
            
        // }
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
     * Run background register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    abstract protected function doBackgroundTask($register_function, $array_data, $unique = null);

    /**
     * Run background register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    abstract protected function doHighBackgroundTask($register_function, $array_data, $unique = null);

    /**
     * Run background register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    abstract protected function doLowBackgroundTask($register_function, $array_data, $unique = null);

    /**
     * Run foreground register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    abstract protected function doTask($register_function, $array_data, $unique = null);

    /**
     * Run foreground register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    abstract protected function doHighTask($register_function, $array_data, $unique = null);

    /**
     * Run foreground register task to server job
     * @param string $register_function
     * @param array $array_data
     * @param int $unique
     */
    abstract protected function doLowTask($register_function, $array_data, $unique = null);
}

