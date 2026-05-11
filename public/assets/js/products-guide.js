(function () {
    var secs = document.querySelectorAll('.gd-sec');
    var pills = document.querySelectorAll('.gd-pill');
    var nav = document.querySelector('.gd-nav');

    function update() {
        var y = window.scrollY + 150, cur = '';
        secs.forEach(function (s) { if (s.offsetTop <= y) cur = s.id; });
        pills.forEach(function (p) {
            var id = p.getAttribute('href').replace('#', ''), active = id === cur;
            p.classList.toggle('active', active);
            if (active && nav) { nav.scrollTo({ left: p.offsetLeft - nav.offsetLeft - 16, behavior: 'smooth' }); }
        });
        secs.forEach(function (s) {
            var c = s.querySelector('.gd-card,.gd-mode');
            if (c) c.classList.toggle('visible', s.id === cur);
        });
    }

    window.addEventListener('scroll', update);
    update();

    pills.forEach(function (p) {
        p.addEventListener('click', function (e) {
            e.preventDefault();
            var t = document.getElementById(this.getAttribute('href').replace('#', ''));
            if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}());
