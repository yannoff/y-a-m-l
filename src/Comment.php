<?php
/**
 * @project Y.A.M.L
 * @author  yannoff
 * @created 2019-03-10 16:32
 */

namespace Yannoff\Component\YAML;

/**
 * Class Comment
 * Object representing a YAML comment line
 *
 * @package Yannoff\Component\YAML
 *
 * @method string  getComment()
 * @method Comment setComment(string $comment)
 * @method DataLine getContextLine()
 * @method Comment  setContextLine(DataLine $line)
 * @method string  getType()
 * @method Comment setType(string $type)
 */
class Comment extends DataLine
{
    /**
     * The different comment line types:
     *
     * - TYPE_COMMENT_INLINE: When the comment is on the same line as the key/value
     * - TYPE_COMMENT_FULL  : When the comment is on its own line
     * - TYPE_COMMENT_EMPTY : Empty lines are also considered as comments
     *
     * @var string
     */
    const TYPE_COMMENT_INLINE = 'inline';
    const TYPE_COMMENT_FULL = 'full';
    const TYPE_COMMENT_EMPTY = 'empty';

    /**
     * The contents of the comment - without the leading hashtag (#)
     *
     * @var string
     */
    protected $comment;

    /**
     * The comment context: sibling (for full-line comments) or current Line (for inline comments)
     *
     * @var DataLine
     */
    protected $contextLine;

    /**
     * The comment line type (inline/full/empty)
     *
     * @var string
     */
    protected $type;

    /**
     * Comment constructor.
     *
     * @param int    $no  The line number inside the whole Yaml contents
     * @param string $raw The comment raw contents
     */
    public function __construct($no, $raw)
    {
        $this
            ->setNo($no)
            ->setRaw($raw);

        $parts = explode('#', $raw);

        $data = trim(array_shift($parts));
        // Support weird comment lines, eg. lines beginning with ###
        $comment = implode('#', $parts);
        $this->setComment($comment);


        if (empty($data)) {
            $type = preg_match('/^\s*$/', $raw) ? self::TYPE_COMMENT_EMPTY : self::TYPE_COMMENT_FULL;
            $this->setType($type);
            return;
        }

        $this
            ->setType(self::TYPE_COMMENT_INLINE)
            ->setData($data);
    }

    /**
     * Return true if the comment is inline
     *
     * @return bool
     */
    public function isInline()
    {
        return (self::TYPE_COMMENT_INLINE === $this->type);
    }

    /**
     * Return true if the comment is on its own line
     *
     * @return bool
     */
    public function isFull()
    {
        return (self::TYPE_COMMENT_FULL === $this->type);
    }
}
