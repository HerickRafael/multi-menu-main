/* Guide Engine — scrollspy + navegação lateral
 * Compartilhado por todos os guias em app/views/admin/guide/
 */
window.Guide = {
    init: function () {
        this.scrollSpy();
        this.navLinks();
    },

    scrollSpy: function () {
        var secs  = document.querySelectorAll('.gc-sec');
        var links = document.querySelectorAll('.gc-nav a[data-section]');

        function update() {
            var y = window.scrollY + 150, c = '';
            var atBottom = (window.innerHeight + window.scrollY) >= (document.documentElement.scrollHeight - 80);
            if (atBottom && secs.length) {
                c = secs[secs.length - 1].id;
            } else {
                secs.forEach(function (s) { if (s.offsetTop <= y) c = s.id; });
            }
            links.forEach(function (a) { a.classList.toggle('active', a.dataset.section === c); });
        }

        window.addEventListener('scroll', update);
        update();
    },

    navLinks: function () {
        var links = document.querySelectorAll('.gc-nav a[data-section]');
        links.forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                var t = document.getElementById(this.dataset.section);
                if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    },
};

Guide.init();
