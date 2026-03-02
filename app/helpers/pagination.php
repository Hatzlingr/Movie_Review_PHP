<?php
// app/helpers/pagination.php
declare(strict_types=1);

/**
 * Hitung pagination dari total data.
 *
 * @param int $total         Total jumlah baris
 * @param int $perPage       Jumlah baris per halaman
 * @param int $requestedPage Nomor halaman yang diminta dari $_GET
 * @return array{0: int, 1: int, 2: int}  [$page, $pages, $offset]
 */
function calc_pagination(int $total, int $perPage, int $requestedPage): array
{
    $pages  = max(1, (int)ceil($total / $perPage));
    $page   = min(max(1, $requestedPage), $pages);
    $offset = ($page - 1) * $perPage;
    return [$page, $pages, $offset];
}

/**
 * Render a windowed pagination nav.
 *
 * @param int    $page     Halaman aktif saat ini (1-based)
 * @param int    $pages    Total jumlah halaman
 * @param string $baseUrl  URL dasar tanpa parameter page, diakhiri '&' atau '?'
 *                         Contoh: '/public/search.php?q=batman&genre=Action&'
 *                         Atau:   '/public/search.php?'
 */
function render_pagination(int $page, int $pages, string $baseUrl): void
{
    if ($pages <= 1) return;

    $prev    = $page - 1;
    $next    = $page + 1;
    $safeUrl = htmlspecialchars($baseUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
    <nav class="mt-2" aria-label="Page navigation">
        <ul class="pagination justify-content-center flex-wrap">

            <li class="page-item <?= $prev < 1 ? 'disabled' : '' ?>">
                <?= $prev >= 1
                    ? '<a class="page-link" href="' . $safeUrl . 'page=' . $prev . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>'
                    : '<span class="page-link" aria-disabled="true"><span aria-hidden="true">&laquo;</span></span>' ?>
            </li>

            <?php for ($p = 1; $p <= $pages; $p++):
                $gap = abs($p - $page);
                if ($p === 1 || $p === $pages || $gap <= 2): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <?= $p === $page
                            ? '<span class="page-link" aria-current="page">' . $p . '</span>'
                            : '<a class="page-link" href="' . $safeUrl . 'page=' . $p . '">' . $p . '</a>' ?>
                    </li>
                <?php elseif ($gap === 3): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
            <?php endif;
            endfor; ?>

            <li class="page-item <?= $next > $pages ? 'disabled' : '' ?>">
                <?= $next <= $pages
                    ? '<a class="page-link" href="' . $safeUrl . 'page=' . $next . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>'
                    : '<span class="page-link" aria-disabled="true"><span aria-hidden="true">&raquo;</span></span>' ?>
            </li>

        </ul>
    </nav>
<?php
}
