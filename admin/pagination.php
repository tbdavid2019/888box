<?php
/**
 * @param int $current_page 当前页码
 * @param int $total_pages 总页数
 * @param bool $show_edges 是否显示首页和末页（默认true）
 * @return string HTML字符串
 */
function renderPagination($current_page, $total_pages, $show_edges = true, $urlPattern = '?page=%d') {
    if ($total_pages <= 1) return '';
    
    $html = '';
    
    // 上一頁
    if ($current_page > 1) {
        $prev = $current_page - 1;
        $html .= sprintf('<a class="page-link prev-page" data-page="%d" href="%s" aria-label="上一頁">&laquo;</a>', $prev, sprintf($urlPattern, $prev));
    }
    
    if ($show_edges) {
        // 首頁
        if ($current_page > 2) {
            $html .= sprintf('<a class="page-link" data-page="1" href="%s">1</a>', sprintf($urlPattern, 1));
            if ($current_page > 3) {
                $html .= '<span class="ellipsis page-link">...</span>';
            }
        }
        
        // 前一頁
        if ($current_page > 1) {
            $prev = $current_page - 1;
            $html .= sprintf('<a class="page-link" data-page="%d" href="%s">%d</a>', $prev, sprintf($urlPattern, $prev), $prev);
        }
    }
    
    // 當前頁
    $html .= sprintf('<a class="page-link active" data-page="%d" href="%s" aria-current="page">%d</a>', $current_page, sprintf($urlPattern, $current_page), $current_page);
    
    if ($show_edges) {
        // 後一頁
        if ($current_page < $total_pages) {
            $next = $current_page + 1;
            $html .= sprintf('<a class="page-link" data-page="%d" href="%s">%d</a>', $next, sprintf($urlPattern, $next), $next);
        }
        
        // 末頁
        if ($current_page < $total_pages - 1) {
            if ($current_page < $total_pages - 2) {
                $html .= '<span class="ellipsis page-link">...</span>';
            }
            $html .= sprintf('<a class="page-link" data-page="%d" href="%s">%d</a>', $total_pages, sprintf($urlPattern, $total_pages), $total_pages);
        }
    }
    
    // 下一頁
    if ($current_page < $total_pages) {
        $next = $current_page + 1;
        $html .= sprintf('<a class="page-link next-page" data-page="%d" href="%s" aria-label="下一頁">&raquo;</a>', $next, sprintf($urlPattern, $next));
    }
    
    return $html;
}
?>