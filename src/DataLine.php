<?php
/**
 * @project Y.A.M.L
 * @author  yannoff
 * @created 2019-03-10 16:32
 */

namespace Yannoff\Component\YAML;

use function array_shift;
use function explode;
use function implode;
use function preg_match;
use function preg_replace;
use function sprintf;
use function substr_count;
use function trim;

/**
 * Class DataLine
 * Object representing a YAML line
 *
 * @package Yannoff\Component\YAML
 *
 * @method string   getType()
 * @method DataLine setType(string $type)
 */
class DataLine extends Line
{
    /**
     * The atomic indentation string
     *
     * @var string
     */
    const TAB = '    ';

    /**
     * The different line types:
     *
     * - TYPE_LINE_SEQUENCE: When the line is a sequence entry
     * - TYPE_LINE_REGULAR:  Otherwise
     *
     * @var string
     */
    const TYPE_LINE_SEQUENCE = 'sequence';
    const TYPE_LINE_REGULAR  = 'regular';

    /**
     * The line type (sequence/regular)
     *
     * @var string
     */
    protected $type;

    /**
     * DataLine constructor.
     *
     * @param int    $no  The line number inside the whole Yaml contents
     * @param string $raw The line raw content
     */
    public function __construct($no, $raw)
    {
        $this
            ->setNo($no)
            ->setRaw($raw);

        if (preg_match('/:( |$)/', $raw)) {
            $this
                ->setType(self::TYPE_LINE_REGULAR)
                ->setData($raw);
            return;
        }

        if (preg_match('/\s*-\s*/', $raw)) {
            $this
                ->setKey('[n]')
                ->setType(self::TYPE_LINE_SEQUENCE)
                ->setValue(trim(preg_replace('/\s*-\s*/', '', $raw)));
            return;
        }
    }

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
     * Append extra text to the raw contents
     *
     * @param string $extra The text to append
     *
     * @return self
     */
    public function append($extra)
    {
        $this->raw .= $extra;
        return $this;
    }

    /**
     * Calculate the indentation level of the line
     *
     * @return int
     */
    public function getIndent()
    {
        $regexp = sprintf('/^((%s)*)([a-zA-z\-#]*)/', self::TAB);
        preg_match($regexp, $this->raw, $tabs);
        $indent = substr_count($tabs[0], self::TAB);
        return $indent;
    }

    /**
     * Return true if the line is an instance of Comment
     *
     * @return bool
     */
    public function isComment()
    {
        return ($this instanceof Comment);
    }

    /**
     * Return true if the line is a sequence
     *
     * @return bool
     */
    public function isSequence()
    {
        return (self::TYPE_LINE_SEQUENCE === $this->type);
    }

    /**
     * Set the key/value properties from the raw contents
     *
     * @param string $raw
     *
     * @return self
     */
    protected function setData($raw)
    {
        $parts = explode(':', $raw);
        $this->key = trim(array_shift($parts), " \t");
        $this->value = trim(implode(':', $parts), " \t");

        return $this;
    }
}
