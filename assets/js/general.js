


<!-- ============================================================================= -->
<!-- GENEL JAVASCRIPT FONKSİYONLARI
<!-- ============================================================================= -->

document.addEventListener("DOMContentLoaded", function () {
    // Collapse okları
    setupCollapseArrows();
    
    // TC Kimlik No giriş kısıtlamaları
    setupTCInput();
    
    // Menü öğesi tıklama
    setupMenuItems();
    
    // Aktif menüyü ayarla
    setActiveMenu();
    
    // Sayfa yüklendiğinde SearchManager'ı başlat
    if (typeof SearchManager !== 'undefined') {
        new SearchManager();
    }
});

// Collapse oklarını ayarla
function setupCollapseArrows() {
    const toggles = document.querySelectorAll('[data-bs-toggle="collapse"]');
    toggles.forEach(toggle => {
        const icon = toggle.querySelector(".bi-caret-down");
        const target = document.querySelector(toggle.getAttribute("href"));
        if (icon && target) {
            target.addEventListener("show.bs.collapse", () => { 
                if (icon.classList.contains("bi-caret-down")) {
                    icon.classList.replace("bi-caret-down","bi-caret-up");
                }
            });
            target.addEventListener("hide.bs.collapse", () => { 
                if (icon.classList.contains("bi-caret-up")) {
                    icon.classList.replace("bi-caret-up","bi-caret-down");
                }
            });
        }
    });
}

// TC Kimlik No input ayarları
function setupTCInput() {
    const tcInput = document.getElementById('tcInput');
    if (tcInput) {
        tcInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
            
            // Arama önerileri
            if (this.value.length >= 3) {
                showRealSearchSuggestions(this.value);
            } else {
                hideSearchSuggestions();
            }
        });
        
        // Input dışına tıklandığında önerileri gizle
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.sidebar-search')) {
                hideSearchSuggestions();
            }
        });
    }
}

// Menü öğesi ayarları
function setupMenuItems() {
    const menuItems = document.querySelectorAll('.list-group-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            if (this.getAttribute('data-bs-toggle') !== 'collapse') {
                // Tüm menü öğelerinden active classını kaldır
                menuItems.forEach(i => i.classList.remove('active'));
                // Tıklanan öğeye active classını ekle
                this.classList.add('active');
            }
        });
    });
}

// Sayfa yüklendiğinde aktif menü öğesini belirle
function setActiveMenu() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuItems = document.querySelectorAll('.list-group-item');
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes(currentPage)) {
            item.classList.add('active');
            
            // Üst menüyü aç
            const parentCollapse = item.closest('.collapse');
            if (parentCollapse) {
                const collapseId = parentCollapse.id;
                const toggleElement = document.querySelector(`[href="#${collapseId}"]`);
                if (toggleElement) {
                    const bsCollapse = new bootstrap.Collapse(parentCollapse, { toggle: false });
                    bsCollapse.show();
                }
            }
        }
    });
}

// Gerçek arama önerilerini göster
function showRealSearchSuggestions(query) {
    fetch('api/search_personel.php?q=' + query)
        .then(response => response.json())
        .then(data => {
            const resultsContainer = document.getElementById('searchResults');
            if (data.length > 0) {
                let html = '<div class="list-group">';
                data.forEach(person => {
                    // Görev durumuna göre sınıf ekle
                    const statusClass = person.gorevde === '1' ? 'status-active' : 'status-inactive';
                    
                    html += `<a href="kimlik_bilgileri.php?tc_search=${person.tc_no}" class="list-group-item list-group-item-action ${statusClass}">
                        <span class="tc-result">${person.tc_no}</span> - 
                        <span class="name-result">${person.ad_soyadi}</span>
                    </a>`;
                });
                html += '</div>';
                resultsContainer.innerHTML = html;
                resultsContainer.style.display = 'block';
                
                // Yeni öneri işleyicilerini ekle
                attachSearchSuggestionHandlers();
            } else {
                hideSearchSuggestions();
            }
        })
        .catch(() => hideSearchSuggestions());
}

// Öneri tıklama işleyicisi
function attachSearchSuggestionHandlers() {
    const suggestionItems = document.querySelectorAll('#searchResults .list-group-item');
    suggestionItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const urlParams = new URLSearchParams(this.getAttribute('href').split('?')[1]);
            const tcNo = urlParams.get('tc_search');
            
            // kimlik_bilgileri.php sayfasına yönlendir
            window.location.href = `kimlik_bilgileri.php?tc_search=${tcNo}`;
        });
    });
}

// Arama önerilerini gizle
function hideSearchSuggestions() {
    const resultsContainer = document.getElementById('searchResults');
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}

// Alert mesajı gösterme fonksiyonu
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Feedback container'a ekle veya oluştur
    let feedbackContainer = document.querySelector('.feedback-container');
    if (!feedbackContainer) {
        feedbackContainer = document.createElement('div');
        feedbackContainer.className = 'feedback-container';
        document.body.prepend(feedbackContainer);
    }
    
    feedbackContainer.appendChild(alertDiv);
    
    // 5 saniye sonra otomatik kapat
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Dinamik arama formu yönetimi
class SearchManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupFormAction();
        this.setupSearchSuggestions();
    }
    
    setupFormAction() {
        const searchForm = document.getElementById('searchForm');
        if (!searchForm) return;
        
        const currentPage = this.getCurrentPage();
        const isHomepage = this.isHomepage(currentPage);
        
        searchForm.action = isHomepage ? 'kimlik_bilgileri.php' : currentPage;
    }
    
    setupSearchSuggestions() {
        const tcInput = document.getElementById('tcInput');
        if (tcInput) {
            tcInput.addEventListener('input', this.handleSearchInput.bind(this));
        }
    }
    
    handleSearchInput(e) {
        const query = e.target.value.replace(/\D/g, '').slice(0, 11);
        e.target.value = query;
        
        if (query.length >= 3) {
            this.showSearchSuggestions(query);
        } else {
            this.hideSearchSuggestions();
        }
    }
    
    showSearchSuggestions(query) {
        fetch('api/search_personel.php?q=' + query)
            .then(response => response.json())
            .then(data => {
                const resultsContainer = document.getElementById('searchResults');
                if (data.length > 0) {
                    let html = '<div class="list-group">';
                    data.forEach(person => {
                        const statusClass = person.gorevde === '1' ? 'status-active' : 'status-inactive';
                        html += `<a href="#" data-tc="${person.tc_no}" class="list-group-item list-group-item-action ${statusClass}">
                            <span class="tc-result">${person.tc_no}</span> - 
                            <span class="name-result">${person.ad_soyadi}</span>
                        </a>`;
                    });
                    html += '</div>';
                    resultsContainer.innerHTML = html;
                    resultsContainer.style.display = 'block';
                    
                    this.attachSuggestionHandlers();
                } else {
                    this.hideSearchSuggestions();
                }
            })
            .catch(() => this.hideSearchSuggestions());
    }
    
    attachSuggestionHandlers() {
        const suggestionItems = document.querySelectorAll('#searchResults .list-group-item');
        suggestionItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const tcNo = item.getAttribute('data-tc');
                this.redirectToSearch(tcNo);
            });
        });
    }
    
    redirectToSearch(tcNo) {
        const currentPage = this.getCurrentPage();
        const isHomepage = this.isHomepage(currentPage);
        const targetPage = isHomepage ? 'kimlik_bilgileri.php' : currentPage;
        
        window.location.href = `${targetPage}?tc_search=${tcNo}`;
    }
    
    hideSearchSuggestions() {
        const resultsContainer = document.getElementById('searchResults');
        if (resultsContainer) {
            resultsContainer.style.display = 'none';
        }
    }
    
    getCurrentPage() {
        return window.location.pathname.split('/').pop();
    }
    
    isHomepage(page) {
        return page === 'dashboard_Anasayfa.php' || page === '' || page === 'index.php';
    }
}

// Spinner için CSS ekle
(function addSpinnerStyles() {
    if (!document.querySelector('#spinner-styles')) {
        const style = document.createElement('style');
        style.id = 'spinner-styles';
        style.textContent = `
            .spinner {
                animation: spin 1s linear infinite;
                margin-right: 5px;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
})();