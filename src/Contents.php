<?php
/**
 * @project Y.A.M.L
 * @author  yannoff
 * @created 2019-03-10 16:32
 */

namespace Yannoff\Component\YAML;

/**
 * Class Contents
 *
 * @package Yannoff\Component\YAML
 */
class Contents
{
    /**
     * The YAML contents, stored line by line
     *
     * @var array
     */
    protected $rows = [];

    /**
     * The { line number => YPath } mapping
     *
     * @var array
     */
    protected $index = [];

    /**
     * Contents constructor.
     * Load contents either from the given filepath or the raw content
     *
     * @param string|null  $file  The file to load contents from
     * @param array        $lines The raw contents, line-by-line
     */
    public function __construct($file = null, $lines = [])
    {
        if ($file) {
            $contents = file_get_contents($file);
            // Fix: avoid creation of an empty line at the end of the row stack
            // TODO Detect if a new line is found at the end of the file
            // if YES, then restore it at some point
            $contents = trim($contents, " \n\t");
            $lines = explode("\n", $contents);
        }

        $this->swapRows($lines);
    }

    /**
     * Return the whole contents as a YAML stream
     *
     * @return string
     */
    public function __toString()
    {
        $contents = implode("\n", $this->getRows());
        return $contents;
    }

    /**
     * Getter for the YAML contents rowset
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * Build an array consisting of the comments found in the YAML contents
     *
     * @return Comment[]
     */
    public function collectComments()
    {
        $comments = [];

        $paths = $this->index;

        foreach ($this->rows as $no => $line) {
            // 2 types of comments:
            // FULL   => the line begins with hashtag
            // INLINE => 1 space between line contents and the hashtag
            if (! preg_match('/(^#| #)/', $line)) {
                continue;
            }

            $comment = new Comment($no, $line);
            switch ($comment->getType()) {
                case Comment::TYPE_COMMENT_FULL:
                    $comment->setContext($paths[$no+1]);
                    break;

                case Comment::TYPE_COMMENT_INLINE:
                    $comment->setContext($paths[$no]);
                    break;
            }
            $comments[$no] = $comment;
        }

        return $comments;
    }

    /**
     * Inject the given array of comments into the YAML contents
     *
     * @param Comment[] $comments
     *
     * @return self
     */
    public function injectComments($comments)
    {
        // First, process inline comments, as line numbers are preserved
        foreach ($comments as $no => $comment) {
            if (! $comment->isInline()) {
                continue;
            }

            $path = $comment->getContext();
            $line = $this->getLineByYPath($path);
            if ($line) {
                $line->append(' #' . $comment->getComment());
                $this->replaceRow($line->getNo(), (string) $line);
            }
            unset($comments[$no]);
        }

        // Then insert each full-line comments
        // Iterate DOWN over the comments list: prepare multi-line comments block handling
        foreach (array_reverse($comments, true) as $comment) {
            $path = $comment->getContext();
            $no = $this->getLineNoByYPath($path);
            $this->insertRow($no, (string) $comment);
        }

        return $this;
    }

    /**
     * Replace YAML contents rowset and re-index the new contents
     *
     * @param array $lines
     */
    protected function swapRows($lines)
    {
        $this->rows = $lines;
        $this->index = $this->parse();
    }

    /**
     * Insert a new row at the given position in the YAML contents
     *
     * @param int    $no  The line number of the insertion
     * @param string $row The new row to be inserted
     */
    protected function insertRow($no, $row)
    {
        $newYaml = [];
        // Insert new line in the yaml rows
        for ($i = 0; $i < $no; $i++) {
            $newYaml[$i] = $this->rows[$i];
        }
        $newYaml[] = $row;
        for ($i = $no; $i < count($this->rows); $i++) {
            $newYaml[$i + 1] = $this->rows[$i];
        }

        $this->swapRows($newYaml);
    }

    /**
     * Replace a row at the given position in the YAML contents
     *
     * @param int    $no  The line number of the replacement
     * @param string $row The row used as a replacement
     */
    protected function replaceRow($no, $row)
    {
        $this->rows[$no] = $row;
        $this->index = $this->parse();
    }

    /**
     * Create an index of the YAML contents: associate line number to the corresponding Ypath for each key
     *
     * @return array
     */
    protected function parse()
    {
        $paths = [];
        foreach ($this->rows as $no => $row) {
            $line = $this->getLine($no);
            $paths[$no] = $this->getYPath($line);
        }

        return $paths;
    }

    /**
     * Build an array consisting of all the parent Line's of the current Line
     *
     * @param DataLine $line
     *
     * @return DataLine[]
     */
    protected function getParents(DataLine $line)
    {
        $parents = [];
        // FIXME: Should consider the 1st parent line indent, NOT the current one
        while ($line->getIndent() > 0) {
            $line = $this->getParentLine($line);
            $parents[] = $line;
        }

        return $parents;
    }

    /**
     * Find the first parent Line above the given Line
     *
     * @param DataLine $line
     *
     * @return DataLine
     */
    protected function getParentLine(DataLine $line)
    {
        $no = $line->getNo();

        $pos = $line->getIndent();
        $i = $no - 1;
        while ($pos > 0) {
            $upperLine = $this->getLine($i);
            $upperIndent = $upperLine->getIndent();
            $i--;
            if ($upperIndent < $pos) {
                $line = $upperLine;
                break;
            }
        }

        return $line;
    }

    /**
     * Calculate the parent YPath to the given line
     *
     * @param DataLine $line
     *
     * @return YPath
     */
    protected function getParentYPath(DataLine $line)
    {
        $path = new YPath();
        /** @var DataLine[] $parents */
        $parents = $this->getParents($line);
        foreach (array_reverse($parents) as $parent) {
            $key = $parent->isSequence() ? $this->getIndex($parent) : $parent->getKey();
            $path->push($key);
        }

        return $path;
    }

    /**
     * Calculate the full YPath to the given line
     *
     * @param DataLine $line
     *
     * @return YPath
     */
    protected function getYPath(DataLine $line)
    {
        $key = $line->isSequence() ? $this->getIndex($line) : $line->getKey();
        $ypath = $this->getParentYPath($line);
        $ypath->push($key);
        return $ypath;
    }

    /**
     * Build a Line object from the given row
     *
     * @param int $no The row line number
     *
     * @return DataLine
     */
    protected function getLine($no)
    {
        $line = new DataLine($no, $this->rows[$no]);
        //echo "--- getLine($no)\n";
        return $line;
    }

    /**
     * Get the Line corresponding to the given YPath
     *
     * @param Ypath $ypath
     *
     * @return DataLine|null
     */
    protected function getLineByYPath(YPath $ypath)
    {
        $no = $this->getLineNoByYPath($ypath);

        if (!$no) {
            return null;
        }

        return $this->getLine($no);
    }


    /**
     * Get the line number corresponding to the given YPath
     *
     * @param YPath $ypath
     *
     * @return int|bool The line number if found, false otherwise
     */
    protected function getLineNoByYPath(YPath $ypath)
    {
        return array_search($ypath, $this->index);
    }

    /**
     * Get the line number corresponding to the given raw contents
     * Used for full comment lines, which by definition don't have an YPath
     *
     * @param string $raw
     *
     * @return int|bool The line number if found, false otherwise
     */
    protected function getLineNoByRawContents($raw)
    {
        return array_search($raw, $this->rows);
    }

    /**
     * When row is part of a sequence, find the index of the given row
     *
     * @param DataLine $line
     *
     * @return int
     */
    protected function getIndex(DataLine $line)
    {
        $parentNo = $this->getParentLine($line)->getNo();
        $indent = $line->getIndent();
        $no = $line->getNo();
        $start = sprintf('%s-', str_repeat(DataLine::TAB, $indent));
        $index = 0;
        for ($n = $parentNo; $n < $no; $n++) {
            if (0 === strpos($this->rows[$n], $start)) {
                $index++;
            }
        }

        return $index;
    }
}
