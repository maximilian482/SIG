document.addEventListener("DOMContentLoaded", function() {

    Chart.register(ChartDataLabels);

    // GRÁFICO SETORES
    if (window.labelsSetores && window.valoresSetores) {
        const ctx = document.getElementById('graficoSetores');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: window.labelsSetores,
                    datasets: [{
                        label: 'Chamados',
                        data: window.valoresSetores,
                        backgroundColor: '#4e73df'
                    }]
                },
                options: {
                    indexAxis: 'x',
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            color: '#000',
                            font: { weight: 'bold' }
                        }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    }

    // GRÁFICO LOJAS
    if (window.labelsLojas && window.valoresLojas) {
        const ctx = document.getElementById('graficoLojas');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: window.labelsLojas,
                    datasets: [{
                        label: 'Chamados',
                        data: window.valoresLojas,
                        backgroundColor: '#1cc88a'
                    }]
                },
                options: {
                    indexAxis: 'x',
                    plugins: {
                        legend: { display: false },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            color: '#000',
                            font: { weight: 'bold' }
                        }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    }

    // MODAL
    window.abrirModalDetalhesChamadoAdmin = function(id) {
        const modal    = document.getElementById('modalDetalhesChamado');
        const conteudo = document.getElementById('conteudoDetalhesChamado');

        conteudo.innerHTML = 'Carregando...';

        fetch('chamados_detalhes.php?id=' + id)
            .then(r => r.text())
            .then(html => {
                conteudo.innerHTML = html;
                modal.style.display = 'block';
            })
            .catch(() => {
                conteudo.innerHTML = 'Erro ao carregar detalhes.';
            });
    };

    window.fecharModalDetalhesChamado = function() {
        document.getElementById('modalDetalhesChamado').style.display = 'none';
    };

});

// FECHAR AO CLICAR FORA
window.addEventListener('click', function(event) {
    const modal = document.getElementById('modalDetalhesChamado');
    if (event.target === modal) modal.style.display = 'none';
});

// FECHAR COM ESC
window.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        const modal = document.getElementById('modalDetalhesChamado');
        modal.style.display = 'none';
    }
});
