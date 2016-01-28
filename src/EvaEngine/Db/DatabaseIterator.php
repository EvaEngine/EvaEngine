<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Db;

use Eva\EvaEngine\Mvc\Model;

class DatabaseIterator implements \Iterator, \Countable
{
    const UNLIMITED = -1;

    protected $offset = 0;
    protected $count = -1;
    protected $max = self::UNLIMITED;
    protected $condition = [];
    protected $entity;

    public function __construct(Model $entity, array $condition = [], $max = -1)
    {
        $this->entity = $entity;
        $this->condition = $condition;
        $this->max = $max;
    }

    /**
     * Return the current element
     *
     * @link  http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this->entity->findFirst(array_merge($this->condition, [
            'offset' => $this->offset,
        ]));
    }

    /**
     * Move forward to next element
     *
     * @link  http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->offset++;
    }

    /**
     * Return the key of the current element
     *
     * @link  http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->offset;
    }

    /**
     * Checks if current position is valid
     *
     * @link  http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *        Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        $count = $this->count();
        $max = $this->max === self::UNLIMITED ? $count : min($this->max, $count);
        return $this->offset < $max;
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @link  http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->offset = 0;
        $this->count = -1;
    }

    /**
     * Count elements of an object
     *
     * @link  http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     *        </p>
     *        <p>
     *        The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->count < 0 ? $this->count = $this->entity->count($this->condition) : $this->count;
    }
}
