// =============================================================
// grafico.js — Inicialização do gráfico histórico (Chart.js)
// Consome o endpoint definido em data-endpoint do <canvas> e desenha
// um gráfico de barras empilhadas: Sim / Não / Sem resposta por data.
// =============================================================

document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('grafico-historico');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    const endpoint = canvas.dataset.endpoint || '../api/grafico.php';

    fetch(endpoint)
        .then(function (resp) {
            if (!resp.ok) {
                throw new Error('Falha ao carregar o gráfico.');
            }
            return resp.json();
        })
        .then(function (dados) {
            if (!Array.isArray(dados) || dados.length === 0) {
                return;
            }

            const labels      = dados.map(function (d) { return d.data; });
            const serieSim    = dados.map(function (d) { return d.sim; });
            const serieNao    = dados.map(function (d) { return d.nao; });
            const serieSemResp = dados.map(function (d) { return d.sem_resposta; });

            // Lê as cores institucionais das variáveis CSS
            const estilo = getComputedStyle(document.documentElement);
            const corSim = estilo.getPropertyValue('--vermelho-atrativo').trim() || '#EB0033';
            const corNao = estilo.getPropertyValue('--cinza-texto').trim() || '#4A555C';
            const corSem = estilo.getPropertyValue('--cinza-medio').trim() || '#D1D1D1';

            // Destrói a instância anterior antes de recriar (evita crescimento infinito)
            if (window.graficoPrincipal instanceof Chart) {
                window.graficoPrincipal.destroy();
            }

            window.graficoPrincipal = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'Sim',          data: serieSim,     backgroundColor: corSim },
                        { label: 'Não',          data: serieNao,     backgroundColor: corNao },
                        { label: 'Sem resposta', data: serieSemResp, backgroundColor: corSem }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                    },
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        })
        .catch(function (err) {
            console.error(err);
        });
});
