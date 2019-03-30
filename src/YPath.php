<?php
/**
 * @project Y.A.M.L
 * @author  yannoff
 * @created 2018-03-23 18:44
 */

namespace Yannoff\Component\YAML;

/**
 * Class YPath
 * YPath object representation
 * YPath is intended as the YAML equivalent of XML's XPath
 *
 * @package Yannoff\Component\YAML
 */
class YPath
{
    /**
     * Separator used when rendering YAML path
     * We cannot use a slash (/) character here, as some elements may contain slashes
     *
     * @var string
     */
    const SEPARATOR = '.';

    /**
     * The elements composing the YAML path
     *
     * @var string[]
     */
    protected $elements = [];

    /**
     * YPath constructor.
     *
     * @param string[] $elements
     */
    public function __construct($elements = [])
    {
        $this->elements = $elements;
    }

    /**
     * Renderer for the YAML path
     *
     * @return string
     */
    public function __toString()
    {
        return implode(self::SEPARATOR, $this->elements);
    }

    /**
     * Add an element to the YAML path
     *
     * @param string $element
     */
    public function push($element)
    {
        $this->elements[] = $element;
    }
}
