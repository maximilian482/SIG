console.log("auditoria_checklist.js carregado");
console.log("Auditoria Checklist — motor finalizado!");

// ==========================================================
// VARIÁVEIS GLOBAIS
// ==========================================================
let itensAuditoria = [];
let indiceAtual = 0;
let signaturePad = null;
let slides = [];

// ==========================================================
// INICIALIZAÇÃO
// ==========================================================
document.addEventListener("DOMContentLoaded", () => {

    document.getElementById("loja_id").addEventListener("change", carregarItens);

    document.getElementById("btn-avancar").addEventListener("click", avancarSlide);
    document.getElementById("btn-voltar").addEventListener("click", voltarSlide);

    document.getElementById("btn-add-observacao").addEventListener("click", () => {
        document.getElementById("obs-wrapper").classList.toggle("oculto");
    });

    carregarUltimasAuditorias();
});

// ==========================================================
// MARCAR OPÇÃO (SIM / PARCIAL / NÃO)
// ==========================================================
document.addEventListener("click", function (e) {

    const btn = e.target.closest(".btn-nota");
    if (!btn) return;

    const slide = btn.closest(".carrossel-slide");
    const grupo = btn.closest(".grupo-botoes");
    const input = slide.querySelector(".input-nota");

    grupo.querySelectorAll(".btn-nota").forEach(b => b.classList.remove("ativo"));

    btn.classList.add("ativo");
    input.value = btn.dataset.valor;
});

// ==========================================================
// CARREGAR ITENS
// ==========================================================
function carregarItens() {
    const loja_id = document.getElementById("loja_id").value;

    if (!loja_id) return;

    fetch(`/ajax/auditoria_checklist_itens.php?loja_id=${loja_id}`)
        .then(res => res.json())
        .then(lista => {
            itensAuditoria = lista;
            montarCarrossel();
        });
}

// ==========================================================
// MONTAR CARROSSEL
// ==========================================================
function montarCarrossel() {

    const container = document.getElementById("carrossel-auditoria");
    const slideResumo = document.getElementById("slide-resumo");
    const slideFinal = document.getElementById("slide-final");

    container.innerHTML = "";

    itensAuditoria.forEach((item, index) => {

        const slide = document.createElement("div");
        slide.className = "carrossel-slide oculto";
        slide.dataset.index = index;

        slide.innerHTML = `
            <h3 class="card-titulo">${item.pergunta}</h3>

            <div class="grupo-campo">
                <label class="label-premium">Resposta:</label>

                <div class="grupo-botoes">
                    <button type="button" class="btn-nota" data-valor="100">SIM</button>
                    <button type="button" class="btn-nota" data-valor="50">PARCIAL</button>
                    <button type="button" class="btn-nota" data-valor="0">NÃO</button>
                </div>

                <input type="hidden" class="input-nota" value="">
            </div>

            <div class="grupo-campo">
                <label class="label-premium">Observação:</label>
                <textarea class="input-premium obs-item" rows="2"></textarea>
            </div>
        `;

        container.appendChild(slide);
    });

    container.appendChild(slideResumo);
    container.appendChild(slideFinal);

    atualizarListaSlides();

    document.getElementById("itens-container").classList.remove("oculto");
    document.getElementById("carrossel-nav").classList.remove("oculto");

    mostrarSlide(0);
}

// ==========================================================
// RECONTAR SLIDES
// ==========================================================
function atualizarListaSlides() {
    slides = [...document.querySelectorAll(".carrossel-slide")];
}

// ==========================================================
// MOSTRAR SLIDE
// ==========================================================
function mostrarSlide(indice) {

    atualizarListaSlides();

    slides.forEach(s => s.classList.add("oculto"));

    const slide = slides[indice];
    if (slide) slide.classList.remove("oculto");

    indiceAtual = indice;

    const totalItens = itensAuditoria.length;
    const indiceResumo = totalItens;
    const indiceFinal = totalItens + 1;

    const btnAvancar = document.getElementById("btn-avancar");

    if (indice < indiceResumo) {
        btnAvancar.textContent = "Avançar ➜";
    } else if (indice === indiceResumo) {
        btnAvancar.textContent = "Ir para final ➜";
        setTimeout(() => montarResumo(), 50);
    } else if (indice === indiceFinal) {
        btnAvancar.textContent = "Salvar Auditoria";

        const campoData = document.getElementById("data_auditoria");
        if (campoData && !campoData.value) {
            const hoje = new Date();
            const yyyy = hoje.getFullYear();
            const mm = String(hoje.getMonth() + 1).padStart(2, "0");
            const dd = String(hoje.getDate()).padStart(2, "0");
            campoData.value = `${yyyy}-${mm}-${dd}`;
        }

        setTimeout(() => inicializarAssinatura(), 100);
    }

    document.getElementById("btn-voltar").style.display =
        indice === 0 ? "none" : "inline-block";
}

// ==========================================================
// VALIDAÇÃO DO SLIDE
// ==========================================================
function validarSlide(indice) {
    const slide = slides[indice];
    if (!slide) return false;

    const btnAtivo = slide.querySelector(".btn-nota.ativo");
    return !!btnAtivo;
}

// ==========================================================
// NAVEGAÇÃO
// ==========================================================
function avancarSlide() {

    const totalItens = itensAuditoria.length;
    const indiceResumo = totalItens;
    const indiceFinal = totalItens + 1;

    if (indiceAtual < totalItens) {
        if (!validarSlide(indiceAtual)) {
            mostrarMensagem("Avalie este item antes de avançar.", "aviso");
            return;
        }
    }

    if (indiceAtual < totalItens - 1) {
        mostrarSlide(indiceAtual + 1);
        return;
    }

    if (indiceAtual === totalItens - 1) {
        mostrarSlide(indiceResumo);
        return;
    }

    if (indiceAtual === indiceResumo) {
        mostrarSlide(indiceFinal);
        return;
    }

    if (indiceAtual === indiceFinal) {
        salvarAuditoria();
        return;
    }
}

function voltarSlide() {
    if (indiceAtual > 0) mostrarSlide(indiceAtual - 1);
}

// ==========================================================
// RESUMO — MODELO A
// ==========================================================
function montarResumo() {

    const respostas = coletarDados().filter(r => r.valor !== null);

    montarResumoItens(respostas);
    montarGraficoGeral(respostas);
}

// ==========================================================
// BARRAS HORIZONTAIS POR ITEM
// ==========================================================
function montarResumoItens(respostas) {

    const container = document.getElementById("lista-resumo-itens");
    if (!container) return;

    container.innerHTML = "";

    respostas.forEach(r => {

        const valor = parseInt(r.valor);

        let classe = "barra-ruim";
        if (valor === 50) classe = "barra-parcial";
        if (valor === 100) classe = "barra-bom";

        const linha = document.createElement("div");
        linha.className = "resumo-item";

        linha.innerHTML = `
            <div class="resumo-item-titulo">${r.pergunta}</div>

            <div class="resumo-barra" data-has-obs="${r.observacao ? "1" : "0"}">
                <div class="resumo-barra-preenchida ${classe}" style="width:${valor}%;">
                    ${valor}%
                </div>
            </div>

            ${r.observacao ? `
                <div class="resumo-observacao oculto">
                    <strong>Observação:</strong><br>
                    ${r.observacao}
                </div>
            ` : ""}
        `;

        container.appendChild(linha);
    });
}

// Clique para mostrar/ocultar observação
document.addEventListener("click", function(e) {

    const barra = e.target.closest(".resumo-barra");
    if (!barra) return;

    if (barra.dataset.hasObs === "1") {
        const obs = barra.parentElement.querySelector(".resumo-observacao");
        if (obs) obs.classList.toggle("oculto");
    }
});

// ==========================================================
// COLETAR DADOS
// ==========================================================
function coletarDados() {

    const respostas = [];

    itensAuditoria.forEach((item, index) => {

        const slide = slides[index];

        const valor = slide.querySelector(".btn-nota.ativo")?.dataset.valor || null;
        const observacao = slide.querySelector(".obs-item").value.trim();

        respostas.push({
            item_id: item.id,
            pergunta: item.pergunta,
            valor: valor,
            observacao: observacao
        });
    });

    return respostas;
}

// ==========================================================
// SALVAR AUDITORIA
// ==========================================================
function salvarAuditoria() {

    const loja_id = document.getElementById("loja_id").value;
    const avaliador_id = document.getElementById("avaliador_id").value;
    const responsavel_nome = document.getElementById("responsavel_nome").value.trim();
    const data_auditoria = document.getElementById("data_auditoria").value;
    const observacao_final = document.getElementById("observacao_final").value.trim();

    const assinatura = signaturePad.toDataURL();

    const respostas = coletarDados();

    fetch("auditoria_checklist_salvar.php", {
        method: "POST",
        body: JSON.stringify({
            loja_id,
            avaliador_id,
            responsavel_nome,
            data_auditoria,
            observacao_final,
            assinatura_base64: assinatura,
            respostas
        })
    })
        .then(res => res.json())
        .then(ret => {
            if (ret.sucesso) {
               mostrarMensagem("Auditoria salva com sucesso!", "sucesso");
               setTimeout(() => window.location.reload(), 1500);    
            } else {
                mostrarMensagem("Erro ao salvar auditoria.", "erro");

            }
        });
}

// ==========================================================
// ÚLTIMAS AUDITORIAS
// ==========================================================
function carregarUltimasAuditorias() {

    fetch("/ajax/auditoria_checklist_historico_lista.php?limite=10")
        .then(res => res.json())
        .then(lista => {

            const tbody = document.getElementById("lista-auditorias");
            tbody.innerHTML = "";

            lista.forEach(a => {

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${a.loja}</td>
                    <td class="${a.classeNota}">${a.nota}%</td>
                    <td>${a.data}</td>
                    <td class="col-acoes">
                        <a class="btn-icone btn-detalhes" data-id="${a.id}"></a>
                        <a class="btn-icone btn-excluir" data-id="${a.id}"></a>
                    </td>
                `;

                const trDetalhes = document.createElement("tr");
                trDetalhes.className = "linha-detalhes oculto";
                trDetalhes.innerHTML = `
                    <td colspan="4">
                        <div class="detalhes-conteudo"></div>
                    </td>
                `;

                tbody.appendChild(tr);
                tbody.appendChild(trDetalhes);
            });
        });
}

// ==========================================================
// ASSINATURA — CORREÇÃO DE DPI E ALINHAMENTO
// ==========================================================
function inicializarAssinatura() {
    const canvas = document.getElementById("signature-pad");
    if (!canvas) return;

    ajustarCanvasAssinatura(canvas);

    signaturePad = new SignaturePad(canvas, {
        backgroundColor: "rgba(255,255,255,0)",
        penColor: "black"
    });
}

function ajustarCanvasAssinatura(canvas) {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);

    const width = canvas.offsetWidth;
    const height = canvas.offsetHeight;

    canvas.width = width * ratio;
    canvas.height = height * ratio;

    const ctx = canvas.getContext("2d");
    ctx.scale(ratio, ratio);
}

function limparAssinatura() {
    if (signaturePad) signaturePad.clear();
}

window.addEventListener("resize", () => {
    const canvas = document.getElementById("signature-pad");
    if (!canvas || !signaturePad) return;

    const data = signaturePad.toData();

    ajustarCanvasAssinatura(canvas);
    signaturePad.fromData(data);
});

document.addEventListener("DOMContentLoaded", inicializarAssinatura);

// EVENTO BOTÃO DETALHES

document.addEventListener("click", function(e) {

    const btn = e.target.closest(".btn-detalhes");
    if (!btn) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const id = btn.dataset.id;

    const linha = btn.closest("tr").nextElementSibling;
    const box = linha.querySelector(".detalhes-conteudo");

    if (!linha.classList.contains("oculto")) {
        linha.classList.add("oculto");
        box.innerHTML = "";
        return;
    }

    fetch(`/ajax/auditoria_checklist_detalhes.php?id=${id}`)
        .then(res => res.json())
        .then(dados => {

            if (!dados || !dados.avaliacao || !dados.setores) {
                box.innerHTML = "<p>Erro ao carregar detalhes.</p>";
                linha.classList.remove("oculto");
                return;
            }

            box.innerHTML = montarHTMLDetalhes(dados);
            linha.classList.remove("oculto");
        })
        .catch(err => {
            box.innerHTML = "<p>Erro ao carregar detalhes.</p>";
            linha.classList.remove("oculto");
        });
});

// Montar detalhes

function montarHTMLDetalhes(d) {

    let html = `
    <div class="detalhes-conteudo">

        <div class="detalhes-topo">

            <div class="detalhes-col-esq">
                <div class="detalhes-header">
                    <h3>${d.avaliacao.loja}</h3>

                    <p><strong>Data:</strong> ${formatarDataBR(d.avaliacao.data_avaliacao)}</p>
                    <p><strong>Responsável:</strong> ${d.avaliacao.responsavel_nome}</p>
                    <p><strong>Avaliador:</strong> ${d.avaliacao.avaliador_nome}</p>

                    <div class="nota-geral-box">${d.avaliacao.nota_geral}%</div>
                </div>
            </div>

            <div class="detalhes-col-dir">
                <div class="detalhes-assinatura">
                    <p><strong>Assinatura do responsável:</strong></p>
                    <img src="${d.avaliacao.assinatura}" alt="Assinatura">
                </div>
            </div>

        </div>

        <div class="detalhes-barras">
    `;

    d.setores.forEach(item => {

        let classe = "barra-ruim";
        if (item.nota_setor == 50) classe = "barra-parcial";
        if (item.nota_setor == 100) classe = "barra-bom";

        html += `
            <div class="detalhe-item">
                <div class="detalhe-pergunta">${item.setor}</div>

                <div class="resumo-barra">
                    <div class="resumo-barra-preenchida ${classe}" style="width:${item.nota_setor}%;">
                        ${item.nota_setor}%
                    </div>
                </div>

                ${item.observacao ? `
                    <div class="detalhe-observacao">
                        <strong>Observação:</strong> ${item.observacao}
                    </div>
                ` : ""}
            </div>
        `;
    });

    html += `
        </div>
    </div>
    `;

    return html;
}

function formatarDataBR(dataISO) {
    if (!dataISO) return "";
    const [ano, mes, dia] = dataISO.split("-");
    return `${dia}-${mes}-${ano}`;
}
