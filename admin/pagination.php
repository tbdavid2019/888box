<?php
/**
 * 渲染後台分頁導航列 (與 admin/index.php 保持完全一致)
 *
 * @param int $current_page 當前頁碼
 * @param int $total_pages 總頁數
 * @param bool $show_edges 是否顯示首頁和末頁（預設 true）
 * @param string $urlPattern 網址格式
 * @return string HTML字串
 */
function renderPagination($current_page, $total_pages, $show_edges = true, $urlPattern = '?page=%d') {
    if ($total_pages <= 1) return '';

    $buildUrl = function($page) use ($urlPattern) {
        if ($urlPattern === '?page=%d' && !empty($_GET)) {
            $params = $_GET;
            $params['page'] = $page;
            return '?' . http_build_query($params);
        }
        return sprintf($urlPattern, $page);
    };

    $html = '';

    // 上一頁
    if ($current_page > 1) {
        $prev = $current_page - 1;
        $html .= sprintf('<a class="page-link prev-page" data-page="%d" href="%s" aria-label="上一頁" title="上一頁">&laquo;</a>', $prev, htmlspecialchars($buildUrl($prev), ENT_QUOTES, 'UTF-8'));
    }

    if ($show_edges) {
        // 首頁
        if ($current_page > 2) {
            $html .= sprintf('<a class="page-link" data-page="1" href="%s">1</a>', htmlspecialchars($buildUrl(1), ENT_QUOTES, 'UTF-8'));
            if ($current_page > 3) {
                $html .= '<span class="ellipsis page-link">...</span>';
            }
        }

        // 前一頁
        if ($current_page > 1) {
            $prev = $current_page - 1;
            $html .= sprintf('<a class="page-link" data-page="%d" href="%s">%d</a>', $prev, htmlspecialchars($buildUrl($prev), ENT_QUOTES, 'UTF-8'), $prev);
        }
    }

    // 當前頁
    $html .= sprintf('<a class="page-link active" data-page="%d" href="%s" aria-current="page">%d</a>', $current_page, htmlspecialchars($buildUrl($current_page), ENT_QUOTES, 'UTF-8'), $current_page);

    if ($show_edges) {
        // 後一頁
        if ($current_page < $total_pages) {
            $next = $current_page + 1;
            $html .= sprintf('<a class="page-link" data-page="%d" href="%s">%d</a>', $next, htmlspecialchars($buildUrl($next), ENT_QUOTES, 'UTF-8'), $next);
        }

        // 末頁
        if ($current_page < $total_pages - 1) {
            if ($current_page < $total_pages - 2) {
                $html .= '<span class="ellipsis page-link">...</span>';
            }
            $html .= sprintf('<a class="page-link" data-page="%d" href="%s">%d</a>', $total_pages, htmlspecialchars($buildUrl($total_pages), ENT_QUOTES, 'UTF-8'), $total_pages);
        }
    }

    // 下一頁
    if ($current_page < $total_pages) {
        $next = $current_page + 1;
        $html .= sprintf('<a class="page-link next-page" data-page="%d" href="%s" aria-label="下一頁" title="下一頁">&raquo;</a>', $next, htmlspecialchars($buildUrl($next), ENT_QUOTES, 'UTF-8'));
    }

    return $html;
}

/**
 * 相容舊調用，頁碼跳轉整合於右側 #current-total-pages
 */
function renderPageJumper($current_page = 1, $total_pages = 1) {
    return '';
}
?>