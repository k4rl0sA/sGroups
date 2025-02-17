document.addEventListener('DOMContentLoaded', () => {
    const body = document.querySelector('body'),
        sidebar = document.querySelector('.sidebar'),
        toggle = document.querySelector('.toggle'),
        searchBtn = document.querySelector('.search-box'),
        modeSwitch = document.querySelector('.toggle-switch'),
        modeText = document.querySelector('.mode-text');

    if (toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('close');
            const user = document.querySelector('.profile_pic');
            const nomb = document.querySelector('.profile_info');
            if(sidebar.classList.contains('close')){
                user.style.display='none';
                nomb.style.display='none';
            }else{
                user.style.display='block';
                nomb.style.display='block';
            }
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', () => {
            sidebar.classList.remove('close');
        });
    }

    if (modeSwitch) {
        modeSwitch.addEventListener('click', () => {
            body.classList.toggle('dark');

            if (body.classList.contains('dark')) {
                modeText.innerText = 'Claro';
            } else {
                modeText.innerText = 'Oscuro';
            }
        });
    }

    const mainItems = document.querySelectorAll('.main-item');
    mainItems.forEach(item => {
        item.addEventListener('click', () => {
            const parent = item.parentElement;
            parent.classList.toggle('open');
            parent.classList.toggle('active');
        });
    });

    const navLinks = document.querySelectorAll('.nav-link > a');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            navLinks.forEach(link => link.parentElement.classList.remove('active'));
            e.target.parentElement.classList.add('active');
        });
    });

    const searchInput = document.getElementById('search');
    if (searchInput) {
        const menuItems = document.querySelectorAll('.menu-links li');
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();

            menuItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});

