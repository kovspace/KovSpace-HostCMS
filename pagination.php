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
    public static function showPaginationLi($value, $link = '', $class = '')
    {
        echo '<li class="page-item ' . $class . '">';
        if ($link) {
            echo '<a class="page-link" href="' . $link . '">' . $value . '</a>';
        } else {
            echo '<span class="page-link">' . $value . '</span>';
        }
        echo '</li>';
    }

    public static function show($total, $page)
    {
        if ($total <= 1) {
            return;
        }

        echo '<nav class="overflow-auto">';
        echo '<ul class="pagination justify-content-lg-center" style="min-width:300px">';

        // Previous page link
        if ($page == 1) {
            self::showPaginationLi('&lsaquo;', '', 'disabled');
        } else {
            self::showPaginationLi('&lsaquo;', KovSpace_Function::urlParam('page', $page - 1), '');
        }

        // Less than 10 pages
        if ($total < 10) {
            for ($i = 1; $i <= $total; $i++) {
                $class = $i == $page ? 'active' : '';
                self::showPaginationLi($i, KovSpace_Function::urlParam('page', $i), $class);
            }
        }

        // 10 pages and more
        if ($total >= 10) {
            $x = 5; // visible pages in a row
            $y = 2; // offset one side

            // First & second pages
            if ($page > $x) {
                self::showPaginationLi(1, KovSpace_Function::urlParam('page', 1), '');
                self::showPaginationLi(2, KovSpace_Function::urlParam('page', 2), '');
                self::showPaginationLi('...', '', 'disabled');
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
                self::showPaginationLi($i, KovSpace_Function::urlParam('page', $i), $class);
            }

            // 2 last pages
            if ($page < $total + 1 - $x) {
                self::showPaginationLi('...', '', 'disabled');
                self::showPaginationLi($total - 1, KovSpace_Function::urlParam('page', $total - 1), '');
                self::showPaginationLi($total, KovSpace_Function::urlParam('page', $total), '');
            }
        }

        // Next page link
        if ($total == $page) {
            self::showPaginationLi('&rsaquo;', '', 'disabled');
        } else {
            self::showPaginationLi('&rsaquo;', KovSpace_Function::urlParam('page', $page + 1), '');
        }

        echo '</ul>';
        echo '</nav>';
    }
}
