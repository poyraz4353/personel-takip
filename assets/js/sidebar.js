// sidebar.js - Ana menü yönetimi
console.log('✅ sidebar.js yüklendi');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Sidebar başlatılıyor...');
    
    // 1. Sidebar toggle işlevi
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
            if (overlay) overlay.classList.toggle('active');
            console.log('📂 Sidebar toggled');
        });
        
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                overlay.classList.remove('active');
            });
        }
    }
    
    // 2. Alt menü toggle işlevleri
    const menuToggles = document.querySelectorAll('.menu-toggle, [data-bs-toggle="collapse"]');
    
    menuToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target') || this.getAttribute('href');
            if (targetId) {
                const target = document.querySelector(targetId);
                if (target) {
                    console.log(`📁 Menü aç/kapat: ${targetId}`);
                    
                    // İkon güncelleme
                    const icon = this.querySelector('i');
                    if (icon) {
                        if (target.classList.contains('show')) {
                            icon.classList.remove('bi-chevron-down');
                            icon.classList.add('bi-chevron-right');
                        } else {
                            setTimeout(() => {
                                icon.classList.remove('bi-chevron-right');
                                icon.classList.add('bi-chevron-down');
                            }, 300);
                        }
                    }
                }
            }
        });
    });
    
    // 3. Aktif menüyü belirleme
    const currentPage = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('#sidebar a.nav-link');
    
    menuLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && href !== '#') {
            const linkPage = href.split('/').pop();
            if (linkPage === currentPage) {
                link.classList.add('active');
                // Parent menüleri de aç
                let parent = link.closest('.collapse');
                while (parent) {
                    parent.classList.add('show');
                    const parentToggle = document.querySelector(`[data-bs-target="#${parent.id}"]`);
                    if (parentToggle) {
                        parentToggle.classList.add('active');
                        const icon = parentToggle.querySelector('i');
                        if (icon) {
                            icon.classList.remove('bi-chevron-right');
                            icon.classList.add('bi-chevron-down');
                        }
                    }
                    parent = parent.parentElement.closest('.collapse');
                }
            }
        }
    });
    
    console.log('✅ Sidebar başlatma tamamlandı');
});