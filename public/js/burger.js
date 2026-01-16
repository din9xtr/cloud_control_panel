document.addEventListener('DOMContentLoaded', function () {
    const header = document.querySelector('.main-header');

    if (!header) return;

    const burgerBtn = document.createElement('button');
    burgerBtn.className = 'burger-btn';
    burgerBtn.setAttribute('aria-label', 'Toggle menu');
    burgerBtn.innerHTML = `
        <span class="burger-line"></span>
        <span class="burger-line"></span>
        <span class="burger-line"></span>
    `;
    const spanBrand = document.createElement('span');
    spanBrand.className = 'navbar-brand-burger';
    spanBrand.innerHTML = `
                   <span class="navbar-brand">Cloud Control Panel</span>

    `;
    const mobileMenu = document.createElement('div');
    mobileMenu.className = 'mobile-menu';

    const headerContent = header.innerHTML;
    mobileMenu.innerHTML = `
        <div class="mobile-menu-header">
            <span class="navbar-brand">Cloud Control Panel</span>
            <button class="close-btn" aria-label="Close menu">✕</button>
        </div>
        <div class="mobile-menu-body">
            ${headerContent}
        </div>
    `;

    header.appendChild(spanBrand);

    header.appendChild(burgerBtn);
    document.body.appendChild(mobileMenu);

    function openMenu() {
        mobileMenu.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        mobileMenu.classList.remove('active');
        document.body.style.overflow = '';
    }

    burgerBtn.addEventListener('click', openMenu);

    const closeBtn = mobileMenu.querySelector('.close-btn');
    closeBtn.addEventListener('click', closeMenu);

    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', closeMenu);
    });

    mobileMenu.addEventListener('click', function (e) {
        if (e.target === mobileMenu) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
            closeMenu();
        }
    });
});