<?php
/**
 * @project Y.A.M.L
 * @author  yannoff
 * @created 2019-03-10 16:32
 */

namespace Yannoff\Component\YAML;

/**
 * Class Line
 * Super class for YAML lines: DataLine & Comment
 *
 * @package Yannoff\Component\YAML
 *
 * @method int    getNo()
 * @method string getKey()
 * @method string getValue()
 * @method string getRaw()
 * @method Line   setNo(int $no)
 * @method Line   setKey(string $key)
 * @method Line   setValue(string $value)
 * @method Line   setRaw(string $raw)
 */
class Line
{
    /** @var int The line number in the whole YAML rowset */
    protected $no;

    /** @var string The key-part of the line */
    protected $key;

    /** @var string The value-part of the line */
    protected $value;

    /** @var string The RAW line contents*/
    protected $raw;

    /** @var YPath The YPath to the line in the YAML Document  */
    protected $ypath;

    /**
     * String representation of the Line
     * Useful for implicit type-casting
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf("%s", $this->getRaw());
    }

    /**
     * Implementation of magic getters/setters
     *
     * @param string $name The method called
     * @param array  $args The invoking args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        $type = substr($name, 0, 3);
        $prop = lcfirst(substr($name, 3));
        switch ($type) {
            case 'set':
                $argc = count($args);
                if ($argc > 1) {
                    $message = sprintf('Method "%s" expected 1 argument, got %s: %s', $name, $argc, json_encode($args));
                    throw new \RuntimeException($message);
                }
                $this->{$prop} = end($args);
                return $this;
                break;

            case 'get':
                return $this->{$prop};
                break;

            default:
                throw new \RuntimeException(sprintf('Method "%s" not implemented', $name));
                break;
        }
    }

    /**
     * Magic setter: block non-allowed properties
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        throw new \RuntimeException(sprintf('Property "%s" not found or setting it is not allowed', $name));
    }

    /**
     * Magic getter: block non-allowed properties
     *
     * @param string $name
     */
    public function __get($name)
    {
        throw new \RuntimeException(sprintf('Property "%s" not found or getting its value is not allowed', $name));
    }

    /**
     * Getter for the Yaml Path
     *
     * @return YPath
     */
    public function getYPath()
    {
        return $this->ypath;
    }

    /**
     * Setter for the Yaml Path
     *
     * @param YPath $YPath
     *
     * @return self
     */
    public function setYPath(YPath $YPath)
    {
        $this->ypath = $YPath;
        return $this;
    }
}
