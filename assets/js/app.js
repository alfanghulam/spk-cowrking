document.addEventListener('DOMContentLoaded', () => {

    // Susun semula navigasi yang sudah ada menjadi tepat delapan menu.
    // Ini hanya mengubah presentasi tautan; semua route dan proses PHP tetap sama.
    const nav = document.querySelector('.sidebar-nav');
    if (nav && nav.dataset.compactNavigation !== 'ready') {
        const originalLinks = Array.from(nav.querySelectorAll(':scope > a.sidebar-link'));
        const findLink = (path) => originalLinks.find(link => {
            try {
                return new URL(link.getAttribute('href'), window.location.href).pathname.split('/').pop() === path;
            } catch (e) {
                return false;
            }
        });

        const berandaLink = findLink('index.php');
        const alternatifLink = findLink('alternatif.php');
        const kriteriaLink = findLink('kriteria.php');
        const inputDataLink = findLink('input_matrix.php');
        const prosesAHPLink = findLink('proses_ahp.php');
        const sawLink = findLink('perhitungan_saw.php');
        const rankingLink = findLink('hasil_ranking.php');
        let profileLink = findLink('profile.php');

        const setLabel = (link, label) => {
            if (!link) return;
            let labelNode = link.querySelector('span');
            if (!labelNode) {
                labelNode = document.createElement('span');
                link.appendChild(labelNode);
            }
            labelNode.textContent = label;
            link.classList.add('nav-text-link');
        };

        setLabel(berandaLink, 'Beranda');
        setLabel(alternatifLink, 'Alternatif lokasi');
        setLabel(kriteriaLink, 'Kriteria');
        setLabel(prosesAHPLink, 'Proses AHP');
        setLabel(inputDataLink, 'Input Data');
        setLabel(sawLink, 'Perhitungan SAW');
        setLabel(rankingLink, 'Hasil ranking');

        // Profil sudah tersedia untuk pengguna login. Untuk tamu, tampilkan tautan
        // yang sama agar kliknya dapat memunculkan popup login di halaman saat ini.
        if (!profileLink) {
            profileLink = document.createElement('a');
            profileLink.href = 'profile.php';
            profileLink.className = 'sidebar-link nav-text-link';
            const labelNode = document.createElement('span');
            labelNode.textContent = 'Profile';
            profileLink.appendChild(labelNode);
        } else {
            setLabel(profileLink, 'Profile');
        }

        const currentPage = window.location.pathname.split('/').pop().toLowerCase();
        if (currentPage === 'profile.php') profileLink.classList.add('active');

        // Input Data hanya menuju halaman input yang memang sudah digunakan proyek.
        // Peta tetap berada di Beranda, bukan sebagai menu tambahan.
        const orderedLinks = [
            berandaLink,
            alternatifLink,
            kriteriaLink,
            prosesAHPLink,
            inputDataLink,
            sawLink,
            rankingLink,
            profileLink
        ].filter(Boolean);

        nav.replaceChildren(...orderedLinks);
        nav.dataset.compactNavigation = 'ready';

        // Pertahankan label aksi yang sebelumnya hanya memakai ikon.
        document.querySelectorAll('.table-action[aria-label]').forEach(el => {
            if (!el.textContent.trim()) el.textContent = 'Lihat';
        });
        document.querySelectorAll('a[aria-label="Hapus riwayat"]').forEach(el => {
            if (!el.textContent.trim()) el.textContent = 'Hapus';
        });
    }

    // Penamaan heading diselaraskan dengan menu; tidak mengubah route atau proses data.
    const currentPageName = window.location.pathname.split('/').pop().toLowerCase();
    const headingLabels = {
        'index.php': 'Beranda',
        'alternatif.php': 'Alternatif lokasi',
        'kriteria.php': 'Kriteria',
        'proses_ahp.php': 'Proses AHP',
        'input_matrix.php': 'Input Data',
        'perhitungan_saw.php': 'Perhitungan SAW',
        'hasil_ranking.php': 'Hasil ranking',
        'profile.php': 'Profile'
    };
    const heading = document.querySelector('.topbar-heading h1');
    if (heading && headingLabels[currentPageName]) heading.textContent = headingLabels[currentPageName];

    // Guest access guard: keep the user on the current page and show the login modal
    // when a protected navigation item is clicked. No PHP or calculation flow is changed.
    const protectedPageNames = new Set([
        'input_matrix.php',
        'nilai_alternatif.php',
        'perhitungan_saw.php',
        'hasil_ranking.php',
        'profile.php'
    ]);

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) return;

        // The guest-only login/register panel is rendered by the existing header.
        const isGuest = Boolean(document.querySelector('.sidebar-auth-actions'));
        if (!isGuest) return;

        let destination;
        try {
            destination = new URL(link.getAttribute('href'), window.location.href);
        } catch (error) {
            return;
        }

        const pageName = destination.pathname.split('/').pop().toLowerCase();
        if (!protectedPageNames.has(pageName)) return;

        event.preventDefault();
        event.stopPropagation();

        // Close the mobile drawer but keep the current page visible behind the modal.
        if (window.innerWidth < 992) {
            document.getElementById('appSidebar')?.classList.remove('sidebar-open');
            document.getElementById('sidebarBackdrop')?.classList.remove('show');
            document.body.classList.remove('sidebar-visible');
        }

        if (typeof window.openAuthModal === 'function') {
            window.openAuthModal();
        } else {
            const modalElement = document.getElementById('modalAuthWarning');
            if (modalElement && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        }
    }, true);

    // Theme preference: the toggle is shown on Profile, while the selected theme applies app-wide.
    const themeButton = document.getElementById('themeToggleButton');
    const themeIcon = document.getElementById('themeToggleIcon');
    const themeLabel = document.getElementById('themeToggleLabel');
    const themeStatus = document.getElementById('themeStatusTag');
    const root = document.documentElement;

    const applyTheme = (theme) => {
        const isDark = theme === 'dark';
        root.setAttribute('data-theme', isDark ? 'dark' : 'light');
        try { localStorage.setItem('spk-theme', isDark ? 'dark' : 'light'); } catch (e) {}
        if (themeButton) themeButton.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        if (themeIcon) themeIcon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        if (themeLabel) themeLabel.textContent = isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap';
        if (themeStatus) themeStatus.textContent = isDark ? 'Mode gelap aktif' : 'Mode terang';
    };

    let initialTheme = root.getAttribute('data-theme') || 'light';
    try { initialTheme = localStorage.getItem('spk-theme') || initialTheme; } catch (e) {}
    applyTheme(initialTheme);
    themeButton?.addEventListener('click', () => {
        const nextTheme = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
    });

    // 1. Sidebar Toggle (Buka / Tutup Sidebar Left)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const wrapper = document.getElementById('wrapper');

    if (sidebarToggle && wrapper) {
        sidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            wrapper.classList.toggle('toggled');
            
            // Simpan state sidebar di localStorage browser agar pilihan user bertahan saat pindah halaman
            if (wrapper.classList.contains('toggled')) {
                localStorage.setItem('sidebar_state', 'collapsed');
            } else {
                localStorage.setItem('sidebar_state', 'expanded');
            }
        });

        // Cek preferensi buka/tutup terakhir dari user
        if (localStorage.getItem('sidebar_state') === 'collapsed') {
            wrapper.classList.add('toggled');
        }
    }

    // 2. Live Search untuk Tabel Master Data
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const activeTabPane = document.querySelector('.tab-pane.active');
            if (!activeTabPane) return;

            const rows = activeTabPane.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // 3. Highlighting pada Input Matrix yang Diubah Nilainya
    const matrixInputs = document.querySelectorAll('.matrix-input');
    matrixInputs.forEach(input => {
        input.addEventListener('change', function () {
            this.classList.add('input-changed');
            showToast('Nilai diubah (Jangan lupa klik Simpan)', 'warning');
        });
    });

});

// Helper Function: Toast Notification
function showToast(message, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }

    const bgClass = type === 'success' ? 'bg-success' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-danger');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white ${bgClass} border-0 show mb-2 shadow`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3500);
}