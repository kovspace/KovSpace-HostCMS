<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Pagination
 *
 * @author KovSpace
 * @version 2020-04-24
 * @copyright © 2018 https://kovspace.com/
 */
class KovSpace_Pagination
{
    public $total;
    public $page;

    public function __construct($total, $page)
    {
        $this->total = $total;
        $this->page = $page;
    }

    public function showPaginationLi($value, $link = '', $class = '')
    {
        echo '<li class="page-item ' . $class . '">';
        if ($link) {
            echo '<a class="page-link" href="' . $link . '">' . $value . '</a>';
        } else {
            echo '<span class="page-link">' . $value . '</span>';
        }
        echo '</li>';
    }

    public function show()
    {
        if ($this->total <= 1) return;

        $page = $this->page;
        $total = $this->total;

        echo '<nav class="overflow-auto">';
        echo '<ul class="pagination justify-content-lg-center" style="min-width:300px">';

        // Previous page link
        if ($page == 1) {
            $this->showPaginationLi('&lsaquo;', '', 'disabled');
        } else {
            $this->showPaginationLi('&lsaquo;', KovSpace_Function::urlParam('page', $page - 1), '');
        }

        // Less than 10 pages
        if ($total < 10) {
            for ($i = 1; $i <= $total; $i++) {
                $class = $i == $page ? 'active' : '';
                $this->showPaginationLi($i, KovSpace_Function::urlParam('page', $i), $class);
            }
        }

        // 10 pages and more
        if ($total >= 10) {

            $x = 5; // visible pages in a row
            $y = 2; // offset one side

            // First & second pages
            if ($page > $x) {
                $this->showPaginationLi(1, KovSpace_Function::urlParam('page', 1), '');
                $this->showPaginationLi(2, KovSpace_Function::urlParam('page', 2), '');
                $this->showPaginationLi('...', '', 'disabled');
            }

            $start = $page - $y;
            $finish = $page + $y;

            if ($page <= $x) {
                $start = 1;
                $finish = $x + $y;
            }

            if ($page >= $total + 1 - $x) {
                $start = $total + 1 - $x - $y;
                $finish = $total;
            }

            for ($i = $start; $i <= $finish; $i++) {
                $class = $i == $page ? 'active' : '';
                $this->showPaginationLi($i, KovSpace_Function::urlParam('page', $i), $class);
            }

            // 2 last pages
            if ($page < $total + 1 - $x) {
                $this->showPaginationLi('...', '', 'disabled');
                $this->showPaginationLi($total - 1, KovSpace_Function::urlParam('page', $total - 1), '');
                $this->showPaginationLi($total, KovSpace_Function::urlParam('page', $total), '');
            }
        }

        // Next page link
        if ($total == $page) {
            $this->showPaginationLi('&rsaquo;', '', 'disabled');
        } else {
            $this->showPaginationLi('&rsaquo;', KovSpace_Function::urlParam('page', $page + 1), '');
        }

        echo '</ul>';
        echo '</nav>';
    }
}
