console.log("JS Auditoria PP carregado!");

let signaturePad = null;

// ==========================================================
// Ajustar tamanho REAL do canvas (corrige deslocamento)
// ==========================================================
function ajustarCanvasAssinatura() {
    const canvas = document.getElementById("signature-pad");
    if (!canvas) return;

    const ratio = Math.max(window.devicePixelRatio || 1, 1);

    canvas.width  = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;

    const ctx = canvas.getContext("2d");
    ctx.scale(ratio, ratio);
}

// ==========================================================
// DOM READY
// ==========================================================
document.addEventListener('DOMContentLoaded', () => {

    const lojaSelect = document.getElementById('loja_id');
    const itensContainer = document.getElementById('itens-container');
    const carrossel = document.getElementById('carrossel-auditoria');
    const slideResumo = document.getElementById('slide-resumo');
    const slideFinal = document.getElementById('slide-final');
    const nav = document.getElementById('carrossel-nav');

    const btnVoltar = document.getElementById('btn-voltar');
    const btnAvancar = document.getElementById('btn-avancar');

    let slides = [];
    let slideAtual = 0;

    // ==========================================================
    // Carregar itens da loja
    // ==========================================================
    lojaSelect.addEventListener('change', () => {
        const lojaId = lojaSelect.value;

        if (!lojaId) {
            itensContainer.classList.add('oculto');
            carrossel.querySelectorAll(".carrossel-slide[data-item-id]").forEach(s => s.remove());
            nav.classList.add('oculto');
            return;
        }

        document.getElementById('loja_id_hidden').value = lojaId;

        fetch(`/ajax/auditoria_pp_carregar_itens_auditoria.php?loja_id=${lojaId}`)
            .then(res => res.json())
            .then(lista => {

                // Remove apenas slides dinâmicos (itens)
                carrossel.querySelectorAll(".carrossel-slide[data-item-id]").forEach(s => s.remove());

                if (lista.length === 0) {
                    itensContainer.classList.add('oculto');
                    nav.classList.add('oculto');
                    return;
                }

                lista.forEach(item => {
                    const slide = document.createElement('div');
                    slide.classList.add('carrossel-slide');
                    slide.dataset.itemId = item.id;

                    slide.innerHTML = `
                        <h3 class="titulo-setor">${item.pergunta}</h3>

                        <div class="grupo-botoes">
                            <button class="btn-nota" data-valor="10">Sim</button>
                            <button class="btn-nota" data-valor="5">Parcial</button>
                            <button class="btn-nota" data-valor="0">Não</button>
                        </div>

                        <input type="hidden" class="input-nota" value="">
                        
                        <div class="grupo-campo">
                            <label class="label-premium">Observação:</label>
                            <textarea class="obs-item input-premium" rows="2"></textarea>
                        </div>
                    `;

                    // insere ANTES do slide-resumo
                    carrossel.insertBefore(slide, slideResumo);
                });

                // monta lista de slides (itens + resumo + final)
                slides = [...carrossel.querySelectorAll('.carrossel-slide')];

                slideAtual = 0;
                mostrarSlide(0);

                itensContainer.classList.remove('oculto');
                nav.classList.remove('oculto');

                carrossel.querySelectorAll('.btn-nota').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const valor = btn.dataset.valor;
                        const slide = btn.closest('.carrossel-slide');
                        const input = slide.querySelector('.input-nota');

                        slide.querySelectorAll('.btn-nota').forEach(b => b.classList.remove('ativo'));
                        btn.classList.add('ativo');

                        input.value = valor;
                    });
                });
            });
    });

    // ==========================================================
    // Funções de navegação
    // ==========================================================
    function esconderTudo() {
        slides.forEach(s => s.classList.add('oculto'));
    }

    function mostrarSlide(index) {
        esconderTudo();

        const slide = slides[index];
        slide.classList.remove('oculto');

        if (slide.id === "slide-resumo") {
            gerarResumo();
            btnAvancar.textContent = "Avançar ➜";
        }

        else if (slide.id === "slide-final") {

            const campoData = document.getElementById("data_auditoria");
            if (campoData && !campoData.value) {
                campoData.valueAsDate = new Date();
            }

            btnAvancar.textContent = "Finalizar ➜";

            const canvas = document.getElementById("signature-pad");
            if (canvas && !signaturePad) {
                ajustarCanvasAssinatura();
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: "white"
                });
            }
        }

        else {
            btnAvancar.textContent = "Avançar ➜";
        }

        btnVoltar.disabled = (index === 0);
    }

    function validarSlide(index) {
        const slide = slides[index];

        if (slide.id === "slide-resumo" || slide.id === "slide-final") return true;

        const nota = slide.querySelector('.input-nota').value;
        return nota !== "";
    }

    btnAvancar.addEventListener('click', () => {

        if (!validarSlide(slideAtual)) {
            alert('Selecione uma resposta antes de avançar.');
            return;
        }

        if (slideAtual === slides.length - 1) {
            finalizarAuditoria();
            return;
        }

        slideAtual++;
        mostrarSlide(slideAtual);
    });

    btnVoltar.addEventListener('click', () => {
        if (slideAtual === 0) return;

        slideAtual--;
        mostrarSlide(slideAtual);
    });

});

// ==========================================================
// GERAR RESUMO (barras + gráfico)
// ==========================================================
function gerarResumo() {

    const container = document.getElementById("grafico-itens");
    if (!container) return;

    container.innerHTML = "";

    let soma = 0;
    let total = 0;

    document.querySelectorAll(".carrossel-slide[data-item-id]").forEach(slide => {

        const nome = slide.querySelector("h3").innerText;

        let nota = parseInt(slide.querySelector(".input-nota").value || -1);
        const obs  = slide.querySelector(".obs-item")?.value || "";

        // converter nota 10/5/0 para 100/50/0
        if (nota === 10) nota = 100;
        else if (nota === 5) nota = 50;
        else if (nota === 0) nota = 0;

        if (nota === -1 || isNaN(nota)) {
            container.innerHTML += `
                <div class="barra-setor setor-item">
                    <div class="barra-label"><strong>${nome}</strong></div>
                    <div class="barra barra-na">N/A</div>
                </div>
            `;
            return;
        }

        soma += nota;
        total++;

        let classe = "barra-ruim";
        if (nota >= 75) classe = "barra-bom";
        else if (nota >= 40) classe = "barra-parcial";

        container.innerHTML += `
            <div class="barra-setor setor-item">

                <div class="barra-label">
                    <strong>${nome}</strong>
                </div>

                <div class="barra ${classe}" style="width:100%;">
                    <span class="barra-nota">${nota}%</span>
                </div>

                <div class="setor-detalhes oculto">
                    ${
                        obs.trim() !== ""
                        ? `<div class="obs-setor-detalhe"><strong>Obs:</strong> ${obs}</div>`
                        : "<em>Sem observações.</em>"
                    }
                </div>

            </div>
        `;
    });

    document.querySelectorAll(".setor-item").forEach(item => {
        item.addEventListener("click", () => {
            item.querySelector(".setor-detalhes").classList.toggle("oculto");
        });
    });

    const geral = total > 0 ? soma / total : 0;
    montarGraficoGeral("grafico-geral", geral);
}

// ==========================================================
// DESENHAR GRÁFICO GERAL (donut)
// ==========================================================
function desenharGraficoGeral(ctx, nota) {

    let cor = "#e53935";
    if (nota >= 75) cor = "#43a047";
    else if (nota >= 40) cor = "#ffb300";

    const centerText = {
        id: "centerText",
        afterDraw(chart) {
            const { ctx, chartArea } = chart;
            if (!chartArea || chartArea.width === 0) return;

            const x = (chartArea.left + chartArea.right) / 2;
            const y = (chartArea.top + chartArea.bottom) / 2;

            ctx.save();
            ctx.fillStyle = "#333";
            ctx.font = "bold 22px Arial";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            ctx.fillText(nota.toFixed(2) + "%", x, y);
            ctx.restore();
        }
    };

    new Chart(ctx, {
        type: "doughnut",
        plugins: [centerText],
        data: {
            datasets: [{
                data: [nota, 100 - nota],
                backgroundColor: [cor, "#e0e0e0"],
                borderWidth: 0
            }]
        },
        options: {
            cutout: "70%",
            responsive: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false }
            }
        }
    });
}

// ==========================================================
// MONTAR GRÁFICO GERAL
// ==========================================================
function montarGraficoGeral(canvasId, nota) {

    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const chartExistente = Chart.getChart(canvasId);
    if (chartExistente) chartExistente.destroy();

    const ctx = canvas.getContext("2d");
    desenharGraficoGeral(ctx, nota);
}

// ==========================================================
// Finalizar auditoria
// ==========================================================
async function finalizarAuditoria() {

    const lojaId = document.getElementById("loja_id_hidden").value;
    const avaliadorId = document.getElementById("avaliador_id").value;
    const responsavelNome = document.getElementById("responsavel_nome").value;
    const observacaoFinal = document.getElementById("observacao_final")?.value || "";
    const dataAuditoria = document.getElementById("data_auditoria").value;

    if (!responsavelNome.trim()) {
        alert("Por favor, informe o nome do responsável.");
        return;
    }

    if (!signaturePad || signaturePad.isEmpty()) {
        alert("Por favor, assine antes de finalizar.");
        return;
    }

    const assinaturaBase64 = signaturePad.toDataURL();

    const itens = [];

    document.querySelectorAll(".carrossel-slide[data-item-id]").forEach(slide => {

        itens.push({
            item_id: parseInt(slide.dataset.itemId),
            pergunta: slide.querySelector("h3").textContent,
            resposta: parseInt(slide.querySelector(".input-nota").value),
            observacao: slide.querySelector(".obs-item")?.value || ""
        });
    });

    const dados = {
        loja_id: parseInt(lojaId),
        avaliador_id: parseInt(avaliadorId),
        responsavel_nome: responsavelNome,
        assinatura: assinaturaBase64,
        observacao_final: observacaoFinal,
        data_auditoria: dataAuditoria,
        itens: itens
    };

    try {
        const resposta = await fetch("auditoria_pp_salvar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(dados)
        });

        const resultado = await resposta.json();

        if (resultado.status === "ok") {

            if (typeof mostrarMensagem === "function") {
                mostrarMensagem("Auditoria salva com sucesso!", "sucesso");
            }

            setTimeout(() => {
                window.location.href = "/modulos/auditoria_pp.php";
            }, 1200);

        } else {
            alert("Erro ao salvar: " + resultado.mensagem);
        }

    } catch (error) {
        console.error("Erro ao enviar:", error);
        alert("Falha ao enviar auditoria.");
    }
}

// ==========================================================
// Limpar assinatura
// ==========================================================
function limparAssinatura() {
    if (signaturePad) {
        signaturePad.clear();
    }
}

// ==========================================================
// CARREGAR ÚLTIMAS AUDITORIAS
// ==========================================================
document.addEventListener("DOMContentLoaded", carregarUltimasAuditorias);

function carregarUltimasAuditorias() {

    fetch("/modulos/auditoria_pp_listar.php")
        .then(res => res.json())
        .then(lista => {

            const tbody = document.getElementById("lista-auditorias");
            tbody.innerHTML = "";

            if (!lista || lista.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align:center; padding:15px;">
                            Nenhuma auditoria encontrada.
                        </td>
                    </tr>
                `;
                return;
            }

            lista.forEach(a => {

                const id = a.id;

                tbody.innerHTML += `
                    <tr class="linha-auditoria" data-id="${id}">
                        <td>${a.loja}</td>
                        <td><strong>${a.nota_geral}</strong></td>
                        <td>${a.data}</td>
                        <td class="acoes">
                            <button class="btn-icone btn-detalhes" data-id="${id}"></button>
                            <button class="btn-icone btn-excluir" data-id="${id}"></button>
                        </td>
                    </tr>
                    <tr class="linha-detalhes oculto">
                        <td colspan="4">
                            <div class="detalhes-conteudo">
                                <div class="detalhes-col">
                                    <h4>Carregando detalhes...</h4>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
        });
}

// Abrir detalhes da auditoria
function abrirDetalhesAuditoria() {

    const id = this.dataset.id;
    const linha = document.getElementById("detalhes-" + id);
    const box = document.getElementById("box-" + id);

    // Toggle
    linha.classList.toggle("oculto");

    // Se já carregou antes, não recarrega
    if (!box.dataset.loaded) {

        fetch(`/modulos/auditoria_pp_detalhes.php?id=${id}`)
            .then(res => res.text())
            .then(html => {
                box.innerHTML = html;
                box.dataset.loaded = "1";
            });
    }
}


// Excluir auditoria
function excluirAuditoria(id) {

    if (!confirm("Tem certeza que deseja excluir esta auditoria?")) return;

    fetch("/modulos/auditoria_pp_excluir.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id
    })
    .then(res => res.json())
    .then(r => {
        if (r.status === "ok") {
            alert("Auditoria excluída com sucesso!");
            carregarUltimasAuditorias();
        }
    });
}
