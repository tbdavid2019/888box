/**
 * 888 BOX - Admin Pagination & Page Jumper Helper
 * 与 admin/index.php 交互规范完全一致
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. 回到頂部按鈕監聽
    const scrollTopBtn = document.querySelector('#scroll-to-top');
    const rightside = document.querySelector('.rightside');
    if (scrollTopBtn) {
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            window.requestAnimationFrame(() => {
                const shouldShow = window.scrollY > 100;
                scrollTopBtn.classList.toggle('visible', shouldShow);
                rightside?.classList.toggle('shifted', shouldShow);
                ticking = false;
            });
        }, { passive: true });

        scrollTopBtn.addEventListener('click', (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // 2. 頁碼點擊跳轉 (Click to Jump)
    const pageDisplay = document.getElementById('current-total-pages');
    if (pageDisplay && pageDisplay.parentNode) {
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '1';
        input.className = 'page-input';
        input.style.display = 'none';

        pageDisplay.parentNode.appendChild(input);

        const toggle = (show) => {
            pageDisplay.style.display = show ? 'none' : '';
            input.style.display = show ? 'inline-block' : 'none';
            if (show) {
                input.value = '';
                input.focus();
            }
        };

        pageDisplay.addEventListener('click', (e) => {
            e.stopPropagation();
            toggle(true);
        });

        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const page = parseInt(input.value, 10);
                const parts = (pageDisplay.textContent || '').trim().split('/');
                const totalPages = parseInt(parts[1], 10) || 1;

                if (!isNaN(page) && page >= 1 && page <= totalPages) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    window.location.href = url.toString();
                } else {
                    if (window.UI && typeof window.UI.showNotification === 'function') {
                        window.UI.showNotification('請輸入有效頁碼 (1 - ' + totalPages + ')', 'error');
                    } else {
                        alert('請輸入有效頁碼 (1 - ' + totalPages + ')');
                    }
                    input.value = '';
                }
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                toggle(false);
            }
        });

        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && e.target !== pageDisplay) {
                toggle(false);
            }
        });
    }
});
