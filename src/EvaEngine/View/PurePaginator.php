<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\View;

/**
 * Pure Paginator without DB
 * @package Eva\EvaEngine\View
 */
class PurePaginator
{
    public $query;

    protected $pagerRange = 3;
    public $total_items = 0;
    public $total_pages = 0;
    public $current = 1;
    public $page_range;
    public $before;
    public $prev_skip;
    public $prev_range;
    public $next_skip;
    public $next_range;
    public $next;
    public $items;
    public $last;
    public $first = 1;

    public function setPagerRange($number)
    {
        $this->pagerRange = $number;

        return $this;
    }

    public function getPagerRange()
    {
        return $this->pagerRange;
    }

    public function setQuery(array $query)
    {
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }
    public function getPaginate()
    {
        return $this;
    }
    /**
     * @param int   $pageSize
     * @param int   $total_items
     * @param array $items
     * @param int   $pageRange
     */
    public function __construct($pageSize, $total_items, $items, $pageRange = 3)
    {
        $this->current = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $this->current = $this->current > 0 ? $this->current : 1;
        $this->query = $_REQUEST;
        unset($this->query['page']);
        unset($this->query['_url']);

        $this->last = $this->total_pages = ceil($total_items / $pageSize);
        $this->items = $items;
        $this->total_items = $total_items;
        $prevPageRange = array();
        $prevPageRangeSkip = false;
        if ($this->current > 1) {
            $this->before = $this->current - 1;
            $i = $this->current - $pageRange;
            $i = $i <= 1 ? 1 : $i;
            for (; $i < $this->current; $i++) {
                $prevPageRange[] = $i;
            }
            if ($prevPageRange && $prevPageRange[0] > 1) {
                $prevPageRangeSkip = true;
            }
        }

        $nextPageRange = array();
        $nextPageRangeSkip = false;
        if ($this->current < $this->total_pages) {
            $this->next = $this->current + 1;
            $limit = $this->current + $pageRange;
            $limit = $limit >= $this->total_pages ? $this->total_pages : $limit;
            $i = $this->current + 1;
            for (; $i <= $limit; $i++) {
                $nextPageRange[] = $i;
            }
            if ($nextPageRange && $nextPageRange[count($nextPageRange) - 1] < $this->total_pages) {
                $nextPageRangeSkip = true;
            }
        }

        $this->page_range = $pageRange;
        $this->prev_skip = $prevPageRangeSkip;
        $this->prev_range = $prevPageRange;
        $this->next_skip = $nextPageRangeSkip;
        $this->next_range = $nextPageRange;
    }
}
