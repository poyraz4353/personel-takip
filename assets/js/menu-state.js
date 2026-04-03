// menu-state.js - Tüm sayfalarda çalışacak menü durum yöneticisi

document.addEventListener('DOMContentLoaded', function() {
    console.log('Menü durum yöneticisi yüklendi');
    
    // Aktif sayfayı belirle
    const currentPage = window.location.pathname.split('/').pop();
    console.log('Aktif sayfa:', currentPage);
    
    // Menü öğelerini bul
    const menuItems = document.querySelectorAll('.sidebar a.list-group-item');
    const collapseButtons = document.querySelectorAll('.sidebar [data-bs-toggle="collapse"]');
    
    // 1. Önce tüm aktif sınıflarını temizle
    menuItems.forEach(item => item.classList.remove('active'));
    collapseButtons.forEach(btn => {
        btn.classList.remove('active-menu-parent');
        btn.setAttribute('aria-expanded', 'false');
        
        // Caret ikonunu sıfırla
        const caret = btn.querySelector('.bi-caret-down');
        if (caret) {
            caret.style.transform = 'translateY(-50%)';
        }
    });
    
    // 2. Tüm collapse'ları kapat (sadece Bootstrap ile açılanları)
    document.querySelectorAll('.sidebar .collapse').forEach(collapse => {
        if (!collapse.classList.contains('show')) return;
        
        const collapseInstance = bootstrap.Collapse.getInstance(collapse);
        if (collapseInstance) {
            collapseInstance.hide();
        } else {
            collapse.classList.remove('show');
        }
    });
    
    // 3. Aktif sayfayı bul ve menüyü ayarla
    let activeMenuItem = null;
    
    menuItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && (currentPage === href || 
                     (currentPage.includes('kimlik_bilgileri.php') && href.includes('kimlik_bilgileri.php')) ||
                     (currentPage.includes('gorev_kaydi.php') && href.includes('gorev_kaydi.php')))) {
            
            activeMenuItem = item;
            item.classList.add('active');
            
            // Ebeveyn collapse'ı bul ve aç
            const parentCollapse = item.closest('.collapse');
            if (parentCollapse) {
                const collapseId = parentCollapse.id;
                
                // Collapse'ı aç
                const collapseInstance = new bootstrap.Collapse(parentCollapse, {
                    toggle: false
                });
                collapseInstance.show();
                
                // Ebeveyn butonu bul ve ayarla
                const parentButton = document.querySelector(`[data-bs-target="#${collapseId}"]`);
                if (parentButton) {
                    parentButton.classList.add('active-menu-parent');
                    parentButton.setAttribute('aria-expanded', 'true');
                    
                    // Caret ikonunu döndür
                    const caret = parentButton.querySelector('.bi-caret-down');
                    if (caret) {
                        caret.style.transform = 'translateY(-50%) rotate(180deg)';
                    }
                }
            }
        }
    });
    
    // 4. Eğer aktif menü bulunamazsa, dashboard'u aktif yap
    if (!activeMenuItem && (currentPage === '' || currentPage === 'index.php' || currentPage === 'dashboard_Anasayfa.php')) {
        const dashboardLink = document.querySelector('a[href*="dashboard_Anasayfa.php"], a[href*="index.php"]');
        if (dashboardLink) {
            dashboardLink.classList.add('active');
        }
    }
    
    console.log('Menü ayarlaması tamamlandı');
    
    // 5. Tıklama olaylarını dinle - manuel kontrol
    collapseButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = this.getAttribute('data-bs-target');
            const targetCollapse = document.querySelector(targetId);
            
            if (!targetCollapse) return;
            
            // Bootstrap collapse instance oluştur veya al
            let collapseInstance = bootstrap.Collapse.getInstance(targetCollapse);
            if (!collapseInstance) {
                collapseInstance = new bootstrap.Collapse(targetCollapse, {
                    toggle: false
                });
            }
            
            // Toggle işlemi
            if (targetCollapse.classList.contains('show')) {
                collapseInstance.hide();
                this.setAttribute('aria-expanded', 'false');
                this.classList.remove('active-menu-parent');
                
                // Caret ikonunu düzelt
                const caret = this.querySelector('.bi-caret-down');
                if (caret) {
                    caret.style.transform = 'translateY(-50%)';
                }
            } else {
                collapseInstance.show();
                this.setAttribute('aria-expanded', 'true');
                
                // Caret ikonunu döndür
                const caret = this.querySelector('.bi-caret-down');
                if (caret) {
                    caret.style.transform = 'translateY(-50%) rotate(180deg)';
                }
            }
        });
    });
    
    // 6. Menü öğelerine tıklama olayı
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            // Tüm aktif sınıflarını temizle
            menuItems.forEach(i => i.classList.remove('active'));
            
            // Tıklananı aktif yap
            this.classList.add('active');
            
            // Eğer bu bir alt menü öğesiyse, parent collapse'ı işaretle
            if (!this.hasAttribute('data-bs-toggle')) {
                const parentCollapse = this.closest('.collapse');
                if (parentCollapse) {
                    const parentButton = document.querySelector(`[data-bs-target="#${parentCollapse.id}"]`);
                    if (parentButton) {
                        // Diğer butonlardan active-menu-parent'ı kaldır
                        collapseButtons.forEach(btn => btn.classList.remove('active-menu-parent'));
                        
                        parentButton.classList.add('active-menu-parent');
                    }
                }
            }
        });
    });
});