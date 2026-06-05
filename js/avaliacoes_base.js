console.log("BASE JS carregada!");

// ==========================================================
// VARIÁVEIS GERAIS DO MOTOR
// ==========================================================
let signaturePad = null;
let slides = [];
let slideAtual = 0;

// ==========================================================
// AJUSTAR CANVAS DA ASSINATURA
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
// FORMATAR DATA
// ==========================================================
function formatarData(data) {
    const d = new Date(data);
    return d.toLocaleDateString("pt-BR");
}

// ==========================================================
// ESCONDER / MOSTRAR SLIDES
// ==========================================================
function esconderTudo() {
    slides.forEach(s => s.classList.add("oculto"));
}

function mostrarSlide(index) {
    esconderTudo();

    const slide = slides[index];
    slide.classList.remove("oculto");

    const btnAvancar = document.getElementById("btn-avancar");
    const btnVoltar  = document.getElementById("btn-voltar");

    if (slide.id === "slide-resumo") {
        recalcularTodasAsMedias();
        setTimeout(() => gerarResumoBase(), 50);
        btnAvancar.textContent = "Avançar ➜";
    }

    else if (slide.id === "slide-final") {
        const campoData = document.getElementById("data_avaliacao");
        if (campoData && !campoData.value) {
            campoData.valueAsDate = new Date();
        }

        btnAvancar.textContent = "Finalizar ➜";

        const canvas = document.getElementById("signature-pad");
        if (slide.id === "slide-final") {
            setTimeout(() => {
                ajustarCanvasAssinatura();
                signaturePad = new SignaturePad(canvas, { backgroundColor: "white" });
            }, 50);
        }
    }

    else {
        btnAvancar.textContent = "Avançar ➜";
    }

    btnVoltar.disabled = (index === 0);
}

// ==========================================================
// VALIDAR SLIDE
// ==========================================================
function validarSlide(index) {
    const slide = slides[index];

    if (slide.id === "slide-resumo" || slide.id === "slide-final") return true;

    const inputs = slide.querySelectorAll(".input-nota");
    for (let i of inputs) {
        if (!i.value) return false;
    }
    return true;
}

// ==========================================================
// CALCULAR NOTA DO SETOR
// ==========================================================
function calcularNotaSetor(index) {
    const slide = slides[index];
    const inputs = slide.querySelectorAll(".input-nota");

    let soma = 0;
    let total = 0;

    inputs.forEach(i => {
        const valor = parseInt(i.value);
        if (valor !== -1) {
            soma += valor;
            total++;
        }
    });

    const media = total > 0 ? soma / total : 0;

    const inputNota = slide.querySelector(".nota-setor-auto");
    if (inputNota) {
        inputNota.value = media.toFixed(2);
    }
}

// ==========================================================
// RE-CALCULAR TODAS AS MÉDIAS
// ==========================================================
function recalcularTodasAsMedias() {
    document.querySelectorAll(".carrossel-slide[data-setor-id]").forEach(slide => {
        const inputs = slide.querySelectorAll(".input-nota");

        let soma = 0;
        let total = 0;

        inputs.forEach(i => {
            const valor = parseInt(i.value);
            if (valor !== -1) {
                soma += valor;
                total++;
            }
        });

        const media = total > 0 ? soma / total : 0;

        const inputNota = slide.querySelector(".nota-setor-auto");
        if (inputNota) {
            inputNota.value = media.toFixed(2);
        }
    });
}

// ==========================================================
// OBSERVAÇÃO FINAL OPCIONAL
// ==========================================================
document.addEventListener("DOMContentLoaded", () => {
    const btnAddObs = document.getElementById("btn-add-observacao");
    const obsWrapper = document.getElementById("obs-wrapper");

    if (btnAddObs) {
        btnAddObs.addEventListener("click", () => {
            obsWrapper.classList.remove("oculto");
            btnAddObs.style.display = "none";
        });
    }
});

// ==========================================================
// FUNÇÃO AUXILIAR PARA MENSAGEM PREMIUM (COM FALLBACK)
// ==========================================================
function mostrarMensagemSeguro(msg, tipo = "sucesso") {
    if (typeof mostrarMensagem === "function") {
        mostrarMensagem(msg, tipo);
    } else {
        alert(msg);
    }
}

// ==========================================================
// FINALIZAR AVALIAÇÃO (GENÉRICO)
// ==========================================================
function finalizarAvaliacaoBase() {

    const itemId = document.getElementById("item_id_hidden").value;
    const avaliadorId = document.getElementById("avaliador_id").value;
    const responsavelNome = document.getElementById("responsavel_nome").value;
    const observacaoFinal = document.getElementById("observacao_final")?.value || "";
    const dataAvaliacao = document.getElementById("data_avaliacao").value;

    if (!responsavelNome.trim()) {
        mostrarMensagemSeguro("Por favor, informe o nome do responsável.", "aviso");
        return null;
    }

    if (!signaturePad || signaturePad.isEmpty()) {
        mostrarMensagemSeguro("Por favor, assine antes de finalizar.", "aviso");
        return null;
    }

    const assinaturaBase64 = signaturePad.toDataURL();
    document.getElementById("assinatura_base64").value = assinaturaBase64;

    const setores = [];

    document.querySelectorAll(".carrossel-slide[data-setor-id]").forEach(slide => {

        const setorId = parseInt(slide.dataset.setorId);
        const nota = parseFloat(slide.querySelector(".nota-setor-auto").value);
        const obs = slide.querySelector(".obs-setor")?.value || "";

        const criterios = [];

        slide.querySelectorAll(".criterio-item").forEach(item => {
            const nome = item.querySelector(".criterio-nome")?.textContent.trim();
            const valor = parseInt(item.querySelector(".input-nota")?.value || -1);

            if (!nome || nome === "Observação") return;

            criterios.push({ nome, valor });
        });

        setores.push({
            setor_id: setorId,
            nota_setor: nota,
            observacao: obs,
            criterios: criterios
        });
    });

    return {
        item_id: parseInt(itemId),
        avaliador_id: parseInt(avaliadorId),
        responsavel_nome: responsavelNome,
        assinatura: assinaturaBase64,
        observacao_final: observacaoFinal,
        data_avaliacao: dataAvaliacao,
        setores: setores
    };
}

// ==========================================================
// LIMPAR ASSINATURA
// ==========================================================
function limparAssinatura() {
    if (signaturePad) signaturePad.clear();
}

// ==========================================================
// AJUSTAR CANVAS AO REDIMENSIONAR
// ==========================================================
window.addEventListener("resize", () => {
    if (signaturePad) {
        ajustarCanvasAssinatura();
        signaturePad.clear();
    }
});

// ==========================================================
// CARREGAR ÚLTIMAS AVALIAÇÕES (SEM DETALHES)
// ==========================================================
function carregarUltimasAvaliacoesBase(tipo, tabelaId = "lista-avaliacoes") {

    fetch(`/ajax/avaliacoes_ultimas.php?tipo=${tipo}`)
        .then(res => res.json())
        .then(lista => {

            const tbody = document.getElementById(tabelaId);
            if (!tbody) return;

            tbody.innerHTML = "";

            if (lista.length === 0) {
                tbody.innerHTML = "<tr><td colspan='4'>Nenhuma avaliação encontrada.</td></tr>";
                return;
            }

            lista.forEach(av => {

                let classeNota = "nota-ruim";
                if (av.nota >= 75) classeNota = "nota-bom";
                else if (av.nota >= 40) classeNota = "nota-parcial";

                tbody.innerHTML += `
<tr>
    <td>${av.item}</td>
    <td class="${classeNota}">${parseFloat(av.nota).toFixed(2)}</td>
    <td>${formatarData(av.data)}</td>
    <td class="col-acoes">
        <button class="btn-detalhes" data-id="${av.id}">🔍</button>
        <button class="btn-excluir" data-id="${av.id}">🗑️</button>
    </td>
</tr>
`;
            });
        });
}

// ==========================================================
// BOTÃO EXCLUIR — GENÉRICO
// ==========================================================
document.addEventListener("click", async (e) => {

    const botao = e.target.closest(".btn-excluir");
if (!botao) return;


    e.stopPropagation(); // impede o clique de subir e duplicar eventos

    const id = e.target.dataset.id;

    if (!confirm("Tem certeza que deseja excluir esta avaliação?")) return;

    try {
        const resposta = await fetch("/ajax/avaliacao_excluir.php?id=" + id, {
            method: "DELETE"
        });

        const resultado = await resposta.json();

        if (resultado.sucesso) {
            const linha = e.target.closest("tr");
            linha?.remove();
            mostrarMensagemSeguro("Avaliação excluída!", "sucesso");
        } else {
            mostrarMensagemSeguro("Erro ao excluir: " + resultado.mensagem, "erro");
        }

    } catch (err) {
        console.error("Erro ao excluir:", err);
        mostrarMensagemSeguro("Falha ao excluir avaliação.", "erro");
    }
});

