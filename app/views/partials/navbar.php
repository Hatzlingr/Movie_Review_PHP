<?php
// app/views/partials/navbar.php
// Requires: $_current_user, $_q (set by header.php)
?>
<!-- ===== NAVBAR ===== -->
<nav class="navbar-custom">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="/index.php" class="mb-0 fw-bold text-white text-decoration-none" style="letter-spacing:1px;">ELITISRIPIW</a>
        <div class="d-flex align-items-center gap-3">
            <form method="get" action="/public/search.php" class="mb-0">
                <div class="search-wrapper">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" name="q" class="search-input"
                        placeholder="Search movies…"
                        value="<?= $_q ?>"
                        autocomplete="off" id="search-input">
                    <!-- Search Dropdown -->
                    <div class="search-dropdown" id="search-dropdown">
                        <div class="search-dropdown-content">
                            <div class="dropdown-loading" style="display: none;">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                Searching...
                            </div>
                            <div class="dropdown-empty" style="display: none;">
                                <i class="fa-regular fa-circle-xmark"></i>
                                <p>No movies found</p>
                            </div>
                            <div class="dropdown-results" id="dropdown-results">
                                <!-- Results will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php if ($_current_user): ?>
                <div class="nav-auth d-flex gap-2 align-items-center">
                    <!-- <a href="/me/watchlist.php">Watchlist</a>
                    <a href="/me/reviews.php">My Reviews</a> -->
                    <?php if ($_current_user['role'] === 'admin'): ?>
                        <a href="/admin/dashboard.php">Admin</a>
                    <?php endif; ?>
                    <a href="/auth/logout.php">Logout</a>
                    <a href="/me/profile.php">Profile</a>
                </div>
                <i class="fa-solid fa-circle-user fs-3 text-white"></i>
            <?php else: ?>
                <div class="nav-auth d-flex gap-2">
                    <a href="/auth/login.php">Login</a>
                    <a href="/auth/register.php">Register</a>
                </div>
                <i class="fa-solid fa-circle-user fs-3 text-white"></i>
            <?php endif; ?>
        </div>
    </div>
</nav>
<!-- ===== END NAVBAR ===== -->

<script>
    const searchInput = document.getElementById('search-input');
    const searchDropdown = document.getElementById('search-dropdown');
    const dropdownResults = document.getElementById('dropdown-results');
    const dropdownLoading = document.querySelector('.dropdown-loading');
    const dropdownEmpty = document.querySelector('.dropdown-empty');
    
    let debounceTimeout;
    let lastQuery = '';

    // Show dropdown on focus
    searchInput.addEventListener('focus', function() {
        this.classList.add('focused');
        searchDropdown.classList.add('show');
        if (this.value.trim().length >= 2) {
            fetchDropdownSuggestions(this.value);
        }
    });

    // Hide dropdown on blur (with slight delay to allow clicks)
    searchInput.addEventListener('blur', function() {
        this.classList.remove('focused');
        setTimeout(() => {
            searchDropdown.classList.remove('show');
        }, 200);
    });

    // Handle Enter key - submit form to search page
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (this.value.trim()) {
                this.form.submit();
            }
        }
    });

    // Handle input with debounce
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimeout);
        
        const query = this.value.trim();
        if (query.length < 2) {
            showEmptyState();
            return;
        }

        dropdownLoading.style.display = 'flex';
        dropdownEmpty.style.display = 'none';
        dropdownResults.innerHTML = '';

        debounceTimeout = setTimeout(() => {
            fetchDropdownSuggestions(query);
        }, 300);
    });

    // Fetch suggestions from API endpoint
    function fetchDropdownSuggestions(query) {
        if (!query.trim() || query === lastQuery) return;
        
        lastQuery = query;

        fetch(`/public/api/search_suggest.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(json => {
                if (!json.success || json.count === 0) {
                    showEmptyState();
                    return;
                }

                dropdownLoading.style.display = 'none';
                dropdownEmpty.style.display = 'none';
                dropdownResults.innerHTML = '';

                // Render results from JSON
                json.data.forEach(movie => {
                    const resultItem = document.createElement('a');
                    resultItem.href = `/public/movie.php?id=${movie.id}`;
                    resultItem.className = 'dropdown-result-item';
                    
                    // Gunakan poster_path atau poster dari JSON
                    const posterUrl = movie.poster_path || movie.poster;
                    const posterHtml = posterUrl 
                        ? `<img src="${posterUrl}" alt="${htmlEscape(movie.title)}" class="result-poster">`
                        : '<div class="result-poster-empty"><i class="fa-solid fa-film"></i></div>';
                    
                    // Gunakan release_year dari database atau year
                    const year = movie.release_year || movie.year || '—';
                    
                    // Rating (jika ada)
                    const ratingHtml = movie.rating 
                        ? `<span class="result-rating" style="margin-left: 6px;"><i class="fa-solid fa-star"></i> ${movie.rating}</span>`
                        : '';

                    // Data aktor dummy sementara
                    const castDummy = "Tom Holland • Zendaya • Jacob Batalon";
                    //perbaiki lagi, masih dummy, ambil dari database nanti

                    resultItem.innerHTML = `
                        ${posterHtml}
                        <div class="result-info">
                            <div class="result-title">${htmlEscape(movie.title)}</div>
                            <div class="result-meta">
                                ${year} ${ratingHtml} <span class="meta-dot">•</span> ${castDummy}
                            </div>
                            <div class="result-actions">
                                
                            </div>
                        </div>
                    `;
                    dropdownResults.appendChild(resultItem);
                });
            })
            .catch(error => {
                console.error('Search error:', error);
                showEmptyState();
            });
    }

    function showEmptyState() {
        dropdownLoading.style.display = 'none';
        dropdownEmpty.style.display = 'flex';
        dropdownResults.innerHTML = '';
        lastQuery = '';
    }

    // Utility function to escape HTML
    function htmlEscape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-wrapper')) {
            searchDropdown.classList.remove('show');
        }
    });
</script>