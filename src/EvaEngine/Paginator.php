<?php

namespace Eva\EvaEngine;

class Paginator extends \Phalcon\Paginator\Adapter\QueryBuilder
{
    protected $query;

    protected $pagerRange = 3;

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
        $paginate = parent::getPaginate();
        $paginate->offset_start = 0;
        $paginate->offset_end = 0;
        if ($paginate->total_items > 0) {
            $paginate->offset_start = ($paginate->current - 1) * ceil($paginate->total_items / $paginate->total_pages) + 1;
            $paginate->offset_end = $paginate->offset_start + count($paginate->items) - 1;
        }

        $i = 0;
        $pageRange = $this->getPagerRange();
        $prevPageRange = array();
        $prevPageRangeSkip = false;
        if ($paginate->current > 1) {
            $i = $paginate->current - $pageRange;
            $i = $i <= 1 ? 1 : $i;
            for (; $i < $paginate->current; $i++) {
                $prevPageRange[] = $i;
            }
            if ($prevPageRange && $prevPageRange[0] > 1) {
                $prevPageRangeSkip = true;
            }
        }

        $nextPageRange = array();
        $nextPageRangeSkip = false;
        if ($paginate->current < $paginate->total_pages) {
            $limit = $paginate->current + $pageRange;
            $limit = $limit >= $paginate->total_pages ? $paginate->total_pages : $limit;
            $i = $paginate->current + 1;
            for (; $i <= $limit; $i++) {
                $nextPageRange[] = $i;
            }
            if ($nextPageRange && $nextPageRange[count($nextPageRange) - 1] < $paginate->total_pages) {
                $nextPageRangeSkip = true;
            }
        }

        $paginate->page_range = $pageRange;
        $paginate->prev_skip = $prevPageRangeSkip;
        $paginate->prev_range = $prevPageRange;
        $paginate->next_skip = $nextPageRangeSkip;
        $paginate->next_range = $nextPageRange;
        $paginate->query = $this->getQuery();

        return $paginate;
    }
}
