<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Db\Adapter\Driver\Oci8;

use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Exception;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Adapter\Profiler;
use function DeepCopy\deep_copy;

class Statement implements StatementInterface, Profiler\ProfilerAwareInterface
{
    /**
     * @var resource
     */
    protected $oci8 = null;

    /**
     * @var Oci8
     */
    protected $driver = null;

    /**
     * @var Profiler\ProfilerInterface
     */
    protected $profiler = null;

    /**
     * @var string
     */
    protected $sql = '';

    /**
     * Parameter container
     *
     * @var ParameterContainer
     */
    protected $parameterContainer = null;

    /**
     * @var resource
     */
    protected $resource = null;

    /**
     * @var resource
     */
    protected $cursors = null;

    /**
     * Is prepared
     *
     * @var bool
     */
    protected $isPrepared = false;

    /**
     * @var bool
     */
    protected $bufferResults = false;

    /**
     * Set driver
     *
     * @param  Oci8 $driver
     * @return Statement
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * @param Profiler\ProfilerInterface $profiler
     * @return Statement
     */
    public function setProfiler(Profiler\ProfilerInterface $profiler)
    {
        $this->profiler = $profiler;
        return $this;
    }

    /**
     * @return null|Profiler\ProfilerInterface
     */
    public function getProfiler()
    {
        return $this->profiler;
    }

    /**
     * Initialize
     *
     * @param  resource $oci8
     * @return Statement
     */
    public function initialize($oci8)
    {
        $this->oci8 = $oci8;
        return $this;
    }

    /**
     * Set sql
     *
     * @param  string $sql
     * @return Statement
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * Set Parameter container
     *
     * @param ParameterContainer $parameterContainer
     * @return Statement
     */
    public function setParameterContainer(ParameterContainer $parameterContainer)
    {
        $this->parameterContainer = $parameterContainer;
        return $this;
    }

    /**
     * Get resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set resource
     *
     * @param  resource $oci8Statement
     * @return Statement
     */
    public function setResource($oci8Statement)
    {
        $type = oci_statement_type($oci8Statement);
        if (false === $type || 'UNKNOWN' == $type) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid statement provided to %s',
                __METHOD__
            ));
        }
        $this->resource = $oci8Statement;
        $this->isPrepared = true;
        return $this;
    }

    /**
     * Get sql
     *
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return ParameterContainer
     */
    public function getParameterContainer()
    {
        return $this->parameterContainer;
    }

    /**
     * @return bool
     */
    public function isPrepared()
    {
        return $this->isPrepared;
    }

    /**
     * @param string $sql
     * @return Statement
     */
    public function prepare($sql = null)
    {
        if ($this->isPrepared) {
            throw new Exception\RuntimeException('This statement has already been prepared');
        }

        $sql = ($sql) ?: $this->sql;

        // get oci8 statement resource
        $this->resource = oci_parse($this->oci8, $sql);

        if (!$this->resource) {
            $e = oci_error($this->oci8);
            throw new Exception\InvalidQueryException(
                'Statement couldn\'t be produced with sql: ' . $sql,
                null,
                new Exception\ErrorException($e['message'], $e['code'])
            );
        }

        $this->isPrepared = true;
        return $this;
    }

    /**
     * Execute
     *
     * @param null|array|ParameterContainer $parameters
     * @return mixed
     */
    public function execute($parameters = null)
    {
        if (!$this->isPrepared) {
            $this->prepare();
        }

        /** START Standard ParameterContainer Merging Block */
        if (!$this->parameterContainer instanceof ParameterContainer) {
            if ($parameters instanceof ParameterContainer) {
                $this->parameterContainer = $parameters;
                $parameters = null;
            } else {
                $this->parameterContainer = new ParameterContainer();
            }
        }

        if (is_array($parameters)) {
            $this->parameterContainer->setFromArray($parameters);
        }

        $parameterContainers = [];
        if ($this->parameterContainer->count() > 0) {
            $parameterContainers = $this->bindParametersFromContainer();
        }
        /** END Standard ParameterContainer Merging Block */

        if ($this->profiler) {
            $this->profiler->profilerStart($this);
        }

        if ($this->driver->getConnection()->inTransaction()) {
            $ret = @oci_execute($this->resource, OCI_NO_AUTO_COMMIT);
        } else {
            $ret = @oci_execute($this->resource, OCI_COMMIT_ON_SUCCESS);
        }
        
        $this->parameterContainer->setFromArray($parameterContainers);

        if ($this->profiler) {
            $this->profiler->profilerFinish();
        }

        if ($ret === false) {
            $e = oci_error($this->resource);
            throw new Exception\RuntimeException($e['message'], $e['code']);
        }

        /*
        $current_cursor = current($this->cursors);
        oci_execute($current_cursor);
        $data = array();
        while (($row = oci_fetch_array($current_cursor, OCI_ASSOC + OCI_RETURN_NULLS)) != false) {
            $data[] = $row;
        }
        */
        /*
        $data = array();
        while (($row = oci_fetch_array($this->resource, OCI_BOTH)) != false) {
            // Use the uppercase column names for the associative array indices
            $data[] = $row;
        }
        */
        /*
        $keys = Array();
        if ($field_num = oci_num_fields($this->resource)) {
            for ($i = 1; $i <= $field_num; $i++) {
                $name = oci_field_name($this->resource, $i);
                $keys[] = $name;
            }
        }
        */

        $result = $this->driver->createResult($this->resource, $this);
        
        return $result;
    }

	/**
     * Bind parameters from container
     *
     * @param ParameterContainer $pContainer
     */
    protected function bindParametersFromContainer()
    {
        $parameters = $this->parameterContainer->getNamedArray();
       
		$is_debug = false;
        foreach ($parameters as $name => &$value) {
			if($name == 'p_DEBUG'){
				$is_debug = true;
				continue;
			}
            if ($this->parameterContainer->offsetHasErrata($name)) {
                switch ($this->parameterContainer->offsetGetErrata($name)) {
					case ParameterContainer::TYPE_ARRAY_NUM:
                        $tdo = 'T_ARRNUM';
                        //
                        $collection = oci_new_collection($this->driver->getConnection()->getResource(), $tdo);
						if(!empty($value)){
							foreach ($value as $k => $v) {
								$collection->append($v);
							}
						}   
                        //
                        $type = SQLT_NTY;
                        $value = $collection;
                        break;
                    case ParameterContainer::TYPE_ARRAY_CHAR:
                        $tdo = 'T_ARRCHAR';
                        //
                        $collection = oci_new_collection($this->driver->getConnection()->getResource(), $tdo);
						//
						if(!empty($value)){
							foreach ($value as $k => $v) {
								$collection->append($v);
							}
						}                        
                        //
                        $type = SQLT_NTY;
                        $value = $collection;
                        break;
                    case ParameterContainer::TYPE_CURSOR:
                        $type = SQLT_RSET;
                        $this->cursors[$name] = oci_new_cursor($this->driver->getConnection()->getResource());
                        $value = $this->cursors[$name];
                        break;
                    case ParameterContainer::TYPE_NULL:
                        $type = null;
                        $value = null;
                        break;
                    case ParameterContainer::TYPE_DOUBLE:
                    case ParameterContainer::TYPE_INTEGER:
                        $type = SQLT_INT;
                        if (is_string($value)) {
                            $value = (int)$value;
                        }
                        break;
                    case ParameterContainer::TYPE_BINARY:
                        $type = SQLT_BIN;
                        break;
                    case ParameterContainer::TYPE_LOB:
						if(!empty($value)){
							$type = OCI_B_CLOB;
							$clob = oci_new_descriptor($this->driver->getConnection()->getResource(), OCI_DTYPE_LOB);
							$clob->writetemporary($value, OCI_TEMP_CLOB);
							$value = $clob;
						}else{
							$type = SQLT_CHR;
						}
                        break;
                    case ParameterContainer::TYPE_STRING:
                    default:
                        $type = SQLT_CHR;
                        break;
                }
            } else {
                $type = SQLT_CHR;
            }

            $maxLength = -1;
            if ($this->parameterContainer->offsetHasMaxLength($name)) {
                $maxLength = $this->parameterContainer->offsetGetMaxLength($name);
            }
			if($is_debug){
				echo '<pre>';
				print_r(array($this->resource, $name, $value, $maxLength, $type, $this->cursors));
			}
			oci_bind_by_name($this->resource, $name, $value, $maxLength, $type);
        }

        return $parameters;
    }

    public function getCursor($cursor = null, $debug = 0, $khanhhv = 0)
    {
		if($debug){
			echo '<pre>';
			print_r(array($this->cursors));
		}

        $curs = $this->resource;
        //
        $count = count($this->cursors);
        //
        if ($count) {
            if ($cursor === null) {
                $key = array_keys($this->cursors);
                $cursor = $key[0];
            }
			if($debug){
				echo '<pre>';
				print_r(array($key, $cursor));
			}
            //
            if (is_resource($this->cursors[$cursor])) {
				//
				if($debug){
					echo '<pre>';
					print_r($this->cursors[$cursor]);
				}
                //

                oci_execute($this->cursors[$cursor]);

                //
                $curs = deep_copy($this->cursors[$cursor]); 
                
                //
                //oci_free_statement($this->cursors[$cursor]);
                // if($khanhhv){
                //     var_dump($curs);die;
                // }
                //
                unset($this->cursors[$cursor]);
            } else {
                throw new Exception\InvalidQueryException(
                    'Cursor not found',
                    null,
                    new Exception\ErrorException('Cursor ' . $cursor . 'do not exist', 'HYC00')
                );
            }
        }
    
        return $curs;
    }

    public function closeCursor()
    {
        if (!$this->resource) {
            return false;
        }
		if(!empty($this->cursors)){
			foreach ($this->cursors as $cursor) {
				oci_free_statement($cursor);
			}
		}        
        oci_free_statement($this->resource);
        $this->cursors = null;
        return true;
    }

    public function fetch($style = null, $cursor = null, $offset = null)
    {
        if (!$this->resource) {
            return false;
        }
        if ($style === null) {
            $style = ParameterContainer::FETCH_ASSOC;
        }
        //
        $curs = $this->getCursor($cursor);
       
        //
        switch ($style) {
            case ParameterContainer::FETCH_NUM:
                $row = oci_fetch_array($curs, OCI_NUM | OCI_RETURN_NULLS);
                break;
            case ParameterContainer::FETCH_ASSOC:
                $row = oci_fetch_array($curs, OCI_ASSOC | OCI_RETURN_NULLS);
                break;
            case ParameterContainer::FETCH_BOTH:
                $row = oci_fetch_array($curs, OCI_BOTH | OCI_RETURN_NULLS);
                break;
            case ParameterContainer::FETCH_OBJ:
                $row = oci_fetch_object($curs);
                break;
            default:
                throw new Exception\InvalidQueryException('Invalid fetch mode specified');
                break;
        }
        //
        if (!$row && $error = oci_error($this->resource)) {
            throw new Exception\InvalidQueryException($error);
        }

        return $row;
    }

    public function fetchAll($style = null, $col = 0, $cursor = null, $debug = 0)
    {
        //Get resource cursor
        $curs = $this->getCursor($cursor, $debug);
        // make sure we have a fetch mode
        if ($style === null) {
            $style = ParameterContainer::FETCH_ASSOC;
        }
        $flags = OCI_FETCHSTATEMENT_BY_ROW;
        switch ($style) {
            case ParameterContainer::FETCH_BOTH:
                throw new Exception\InvalidQueryException('OCI8 driver does not support');
                $flags |= OCI_NUM;
                $flags |= OCI_ASSOC;
                break;
            case ParameterContainer::FETCH_NUM:
                $flags |= OCI_NUM;
                break;
            case ParameterContainer::FETCH_ASSOC:
                $flags |= OCI_ASSOC;
                break;
            case ParameterContainer::FETCH_OBJ:
                break;
            case ParameterContainer::FETCH_COLUMN:
                $flags = $flags & ~OCI_FETCHSTATEMENT_BY_ROW;
                $flags |= OCI_FETCHSTATEMENT_BY_COLUMN;
                $flags |= OCI_NUM;
                break;
            default:
                throw new Exception\InvalidQueryException('Invalid fetch mode specified');
                break;
        }
        
        $result = Array();
        if ($flags != OCI_FETCHSTATEMENT_BY_ROW) {
            if (!($rows = oci_fetch_all($curs, $result, 0, -1, $flags))) {
                if ($error = oci_error($this->resource)) {
                    throw new Exception\InvalidQueryException($error);
                }
                if (!$rows) {
                    return array();
                }
            }
            //
            if ($style == ParameterContainer::FETCH_COLUMN) {
                $result = $result[$col];
            }
        } else {
            while (($row = oci_fetch_object($curs)) !== false) {
                $result[] = $row;
            }
            if ($error = oci_error($this->resource)) {
                throw new Exception\InvalidQueryException($error);
            }
        }

        return $result;
    }

    public function fetchColumn($col = 0, $cursor = null)
    {
        if (!$this->resource) {
            return false;
        }
        $curs = $this->getCursor($cursor);
        $data = oci_result($curs, $col + 1);
        if ($data === false) {
            throw new Exception\InvalidQueryException(oci_error($this->resource));
        }

        return $data;
    }

    public function fetchIntegervalue($style = null, $cursor = null, $offset = null){
        if (!$this->resource) {
            return false;
        }
        if ($style === null) {
            $style = ParameterContainer::FETCH_ASSOC;
        }
        //
        
    }

    /**
     * Perform a deep clone
     */
    public function __clone()
    {
        $this->isPrepared = false;
        $this->parametersBound = false;
        $this->resource = null;
        if ($this->parameterContainer) {
            $this->parameterContainer = clone $this->parameterContainer;
        }
    }
}
