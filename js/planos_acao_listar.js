document.addEventListener("DOMContentLoaded", function () {

    // ===============================
    // FILTRO POR ABAS
    // ===============================
    const tabs = document.querySelectorAll('.tab');
    const rows = document.querySelectorAll('#planos-body tr');

    function setActiveTab(filter) {
        tabs.forEach(t => t.setAttribute('aria-selected', t.dataset.filter === filter));

        rows.forEach(r => {
            const aba = r.dataset.aba;
            const hide = filter !== 'todas' && aba !== filter;
            r.classList.toggle('hidden', hide);
        });
    }

    tabs.forEach(t => {
        t.addEventListener('click', () => setActiveTab(t.dataset.filter));
    });

    // Aba inicial
    setActiveTab('ativas');
});


