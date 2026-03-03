<?php
// index.php
declare(strict_types=1);

require_once __DIR__ . '/app/config/app.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/helpers/flash.php';
require_once __DIR__ . '/app/helpers/auth.php';
require_once __DIR__ . '/app/config/db.php';
require_once __DIR__ . '/app/repositories/movieRepository.php';

ensure_session();

$repo = new MovieRepository($pdo);

// // ── Hero: best movie this month ──────────────────────────────────────────────
// $currentMonth = (int) date('n');
// $currentYear  = (int) date('Y');
// $heroMovie    = $repo->getBestMovieByMonth($currentMonth, $currentYear);
// $monthName    = strtoupper(date('F', mktime(0, 0, 0, $currentMonth, 1)));

// ── Hero: best 5 movies this month ───────────────────────────────────────────
$currentMonth = (int) date('n');
$currentYear  = (int) date('Y');
$monthName    = strtoupper(date('F', mktime(0, 0, 0, $currentMonth, 1)));

// Ambil top 5 film terbaik
$topMovies = $repo->getBestMoviesOfMonth($currentMonth, $currentYear, 5);

// Beri ranking (1 = terbaik), lalu balik urutannya (reverse) agar tampil dari ranking 5 ke 1
foreach ($topMovies as $idx => $m) {
    $topMovies[$idx]['rank'] = $idx + 1; 
}
$heroMovies = $topMovies;

// ── Movie list ───────────────────────────────────────────────────────────────
$q            = trim($_GET['q'] ?? '');
$moviesResult = $repo->getMovies($q, 12, 0);
$movies       = $moviesResult['data'];

// ── Watchlist (logged-in only) ───────────────────────────────────────────────
$currentUser = current_user();
$watchlist   = [];
if ($currentUser) {
    $watchlist = $repo->getUserWatchlist($currentUser['id']);
}

// ── Most liked reviews ───────────────────────────────────────────────────────
$reviews = $repo->getMostLikedReviews(5);

// ── Page title & header ──────────────────────────────────────────────────────
$pageTitle = $q ? 'Search: ' . $q : 'ELITISRIPIW';
require_once __DIR__ . '/app/views/partials/header.php';
require_once __DIR__ . '/app/views/partials/navbar.php';
?>

<section class="hero" id="heroCarousel">
    <img class="hero-bg" id="heroBackground" src="<?= !empty($heroMovies) ? e(imageUrl($heroMovies[0]['banner_path'] ?? null, 'banner')) : '' ?>" alt="Banner">
    <div class="hero-overlay"></div>
    <div class="container" style="position: relative; z-index: 2;">
        <h5 class="text-center hero-title-top">TOP 5 MOVIES OF <?= $monthName ?></h5>

        <?php if (!empty($heroMovies)): ?>
            <div style="position: relative; margin-top: 2rem; overflow: hidden; border-radius: 8px;">
                <div id="movieCarouselTrack" style="display: flex; transition: transform 0.6s ease-in-out;">
                    <?php foreach ($heroMovies as $movie): ?>
                        <div class="carousel-slide" style="min-width: 100%; display: flex; align-items: center; justify-content: center; padding: 1rem 0;">
                            <div class="row align-items-center w-100 g-3">
                                <div class="col-6 col-md-3 text-center text-md-end">
                                    <img src="<?= e(imageUrl($movie['poster_path'], 'poster')) ?>" alt="<?= e($movie['title']) ?>" class="hero-poster" style="max-height: 350px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                                </div>
                                <div class="col-12 col-md-5 ps-md-4 text-center text-md-start">
                                    <h2 class="fw-bold mb-2"><?= e(strtoupper($movie['title'])) ?></h2>
                                    <p class="hero-desc mb-3"><?= e(substr($movie['description'] ?? '', 0, 150)) ?>...</p>
                                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-4">
                                        <?php if ($movie['avg_rating']): ?>
                                            <span class="display-5 fw-bold me-3"><?= e($movie['avg_rating']) ?></span>
                                            <div style="color:#f1c40f; font-size:1.5rem; letter-spacing:2px;">
                                                <?= renderStars((float)$movie['avg_rating']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted fs-5">No ratings yet</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="/public/movie.php?id=<?= (int)$movie['id'] ?>" class="btn btn-warning fw-bold px-4 py-2" style="background:#f1c40f; color:#000;">
                                        <i class="fa-solid fa-play me-2"></i> View Details
                                    </a>
                                </div>
                                <div class="col-3 col-md-2 text-center d-none d-md-block">
                                    <h1 class="fw-bold" style="font-size:6rem; color: rgba(255,255,255,0.7);">#<?= $movie['rank'] ?></h1>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex justify-content-center align-items-center mt-4 gap-4">
                <button class="btn btn-outline-light rounded-circle" onclick="moveCarousel(-1)" style="width:45px; height:45px; border-color: rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <div id="carouselDots" class="d-flex gap-2">
                    <?php foreach ($heroMovies as $index => $movie): ?>
                        <button class="carousel-dot" onclick="goToSlide(<?= $index ?>)" style="width:12px; height:12px; border-radius:50%; background: <?= $index === 0 ? '#f1c40f' : 'rgba(255,255,255,0.4)' ?>; border:none; transition: all 0.3s;"></button>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-outline-light rounded-circle" onclick="moveCarousel(1)" style="width:45px; height:45px; border-color: rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        <?php else: ?>
            <div class="row justify-content-center align-items-center mt-4">
                <div class="col-md-6 text-center">
                    <p class="text-muted">No ratings recorded this month yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- <section class="hero">
    <img class="hero-bg" src="<?= e(imageUrl($heroMovie['banner_path'] ?? null, 'banner')) ?>" alt="Banner">
    <div class="hero-overlay"></div>
    <div class="container">
        <h5 class="text-center hero-title-top">BEST REVIEW ON <?= $monthName ?></h5>

        <?php if ($heroMovie): ?>
            <div class="row justify-content-center align-items-center mt-4 g-3">
                <div class="col-6 col-md-3 text-center text-md-end">
                    <img src="<?= e(imageUrl($heroMovie['poster_path'], 'poster')) ?>"
                        alt="<?= e($heroMovie['title']) ?>"
                        class="hero-poster">
                </div>
                <div class="col-12 col-md-5 ps-md-4 text-center text-md-start">
                    <h2 class="fw-bold mb-2"><?= e(strtoupper($heroMovie['title'])) ?></h2>
                    <p class="hero-desc mb-3"><?= e($heroMovie['description'] ?? '') ?></p>
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <?php if ($heroMovie['avg_rating']): ?>
                            <span class="display-5 fw-bold me-3"><?= e($heroMovie['avg_rating']) ?></span>
                            <div style="color:#f1c40f; font-size:1.5rem; letter-spacing:2px;">
                                <?= renderStars((float)$heroMovie['avg_rating']) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted fs-5">No ratings yet</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-3 col-md-1 text-center d-none d-md-block">
                    <h1 class="fw-bold" style="font-size:5rem;">1</h1>
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center align-items-center mt-4">
                <div class="col-md-6 text-center">
                    <p class="text-muted">No ratings recorded this month yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section> -->

<!-- ===== MAIN CONTENT ===== -->
<div class="container mt-5 pb-5">
    <?php flash_render(); ?>
    <!-- Movie List -->
    <h5 class="section-header">Movie List<?= $q ? ' — <em>' . e($q) . '</em>' : '' ?></h5>
    <div class="scroll-wrapper mb-5">
        <button class="nav-arrow-list" onclick="scrollSection(this, 1)"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php if (empty($movies)): ?>
                <p class="text-muted">No movies found.</p>
            <?php else: ?>
                <?php foreach ($movies as $m): ?>
                    <div class="movie-card">
                        <img src="<?= e(imageUrl($m['poster_path'], 'poster')) ?>"
                            alt="<?= e($m['title']) ?>">
                        <div class="movie-title"><?= e(strtoupper($m['title'])) ?> (<?= e($m['release_year'] ?? '?') ?>)</div>
                        <?php if ($m['duration_minutes']): ?>
                            <div class="movie-duration" style="font-size:.75rem;color:var(--text-muted);margin-bottom:4px">
                                <i class="fa-regular fa-clock"></i> <?= formatDuration((int)$m['duration_minutes']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="movie-rating">
                            <?php if ($m['avg_rating']): ?>
                                <?= renderStars((float)$m['avg_rating']) ?>
                                <span class="ms-1"><?= e($m['avg_rating']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                        <a href="/public/movie.php?id=<?= (int)$m['id'] ?>" class="btn-card text-center text-decoration-none d-block mb-1">See Details</a>
                        <?php if ($currentUser): ?>
                            <form method="post" action="/action/watchlist_save.php">
                                <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">
                                <input type="hidden" name="status" value="plan_to_watch">
                                <button type="submit" class="btn-card">Add To Watch List</button>
                            </form>
                        <?php else: ?>
                            <a href="/auth/login.php" class="btn-card text-center text-decoration-none d-block">Add To Watch List</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- My Watch List -->
    <?php if ($currentUser): ?>
        <h5 class="section-header">My Watch List</h5>
        <div class="scroll-wrapper mb-5">
            <div class="horizontal-scroll">
                <?php foreach ($watchlist as $w): ?>
                    <div class="movie-card">
                        <img src="<?= e(imageUrl($w['poster_path'], 'poster')) ?>"
                            alt="<?= e($w['title']) ?>">
                        <div class="movie-title"><?= e(strtoupper($w['title'])) ?> (<?= e($w['release_year'] ?? '?') ?>)</div>
                        <div class="movie-rating">
                            <?php if ($w['avg_rating']): ?>
                                <?= renderStars((float)$w['avg_rating']) ?>
                                <span class="ms-1"><?= e($w['avg_rating']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                        <a href="/public/movie.php?id=<?= (int)$w['id'] ?>" class="btn-card text-center text-decoration-none d-block mb-1">See Details</a>
                        <?= watchlistStatusBtn($w['status']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Other Reviews -->
    <h5 class="section-header" style="text-transform:lowercase;">other reviews</h5>
    <div class="scroll-wrapper">
        <button class="nav-arrow-list" onclick="scrollSection(this, 1)"><i class="fa-solid fa-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $rv): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-row">
                                <div class="reviewer-avatar-circle">
                                    <?php if ($rv['profile_photo']): ?>
                                        <img src="<?= e(imageUrl($rv['profile_photo'], 'avatar')) ?>" alt="<?= e($rv['username']) ?>">
                                    <?php else: ?>
                                        <?= e(mb_strtoupper(mb_substr($rv['username'], 0, 1))) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="reviewer-meta">
                                    <div class="rev-name"><?= e($rv['username']) ?></div>
                                    <div class="rev-date"><?= e(date('n/j/Y', strtotime($rv['created_at']))) ?></div>
                                </div>
                            </div>
                            <div class="review-score">
                                <?php if ($rv['user_score']): ?>
                                    <?= renderStars((float)$rv['user_score']) ?>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="review-text-body"><?= e($rv['review_text']) ?></p>
                        <div class="review-actions">
                            <span><i class="fa-regular fa-thumbs-up me-1"></i><?= (int)$rv['like_count'] ?></span>
                            <span><i class="fa-regular fa-comment me-1"></i><a href="/public/movie.php?id=<?= (int)$rv['movie_id'] ?>"><?= e($rv['title']) ?></a></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
<?php require_once __DIR__ . '/app/views/partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function scrollSection(btn, dir) {
        const wrapper = btn.closest('.scroll-wrapper').querySelector('.horizontal-scroll');
        wrapper.scrollBy({ left: dir * 500, behavior: 'smooth' });
    }

    // --- CAROUSEL LOGIC ---
    let currentSlide = 0;
    let autoPlayInterval;
    const totalSlides = <?= !empty($heroMovies) ? count($heroMovies) : 0 ?>;
    
    // Siapkan URL background dengan aman dari PHP
    const heroMoviesData = <?php echo json_encode(array_map(function($m) {
        return ['banner_url' => imageUrl($m['banner_path'] ?? null, 'banner')];
    }, $heroMovies)); ?>;

    function updateCarousel() {
        if (totalSlides === 0) return;
        
        // 1. Animasi Geser Slide (Konten Utama)
        const track = document.getElementById('movieCarouselTrack');
        track.style.transform = `translateX(-${currentSlide * 100}%)`;
        
        // 2. Animasi Fade Background (Banner)
        const bgElement = document.getElementById('heroBackground');
        
        // Turunkan opacity agar memudar sejenak
        bgElement.style.opacity = '0.2'; 
        
        // Tunggu 300ms (0.3 detik), ganti gambar, lalu munculkan lagi
        setTimeout(() => {
            bgElement.src = heroMoviesData[currentSlide]['banner_url'];
            bgElement.style.opacity = '1';
        }, 300);
        
        // 3. Update Indikator Titik (Dots)
        document.querySelectorAll('.carousel-dot').forEach((dot, index) => {
            dot.style.background = index === currentSlide ? '#f1c40f' : 'rgba(255,255,255,0.4)';
        });
    }

    function moveCarousel(direction) {
        currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
        updateCarousel();
        resetAutoPlay();
    }

    function goToSlide(index) {
        currentSlide = index;
        updateCarousel();
        resetAutoPlay();
    }

    function autoPlay() {
        if (totalSlides > 1) moveCarousel(1);
    }

    function resetAutoPlay() {
        clearInterval(autoPlayInterval);
        if (totalSlides > 1) {
            autoPlayInterval = setInterval(autoPlay, 3000); // Otomatis geser setiap 3 detik
        }
    }

    if (totalSlides > 1) {
        document.addEventListener('DOMContentLoaded', resetAutoPlay);
        
        // Berhenti otomatis geser saat user menyorotkan mouse (hover)
        const heroSection = document.getElementById('heroCarousel');
        if(heroSection) {
            heroSection.addEventListener('mouseenter', () => clearInterval(autoPlayInterval));
            heroSection.addEventListener('mouseleave', resetAutoPlay);
        }
    }
</script>

<!-- <script>
    function scrollSection(btn, dir) {
        const wrapper = btn.closest('.scroll-wrapper').querySelector('.horizontal-scroll');
        wrapper.scrollBy({
            left: dir * 500,
            behavior: 'smooth'
        });
    }
</script> -->
</body>

</html>