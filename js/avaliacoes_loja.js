console.log("JS carregou!");

let signaturePad = null;

// ==========================================================
// Ajustar tamanho REAL do canvas (corrige deslocamento no desktop)
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

document.addEventListener('DOMContentLoaded', () => {

    const lojaSelect = document.getElementById('loja-id') 
                    || document.getElementById('loja_id');

    const setoresContainer = document.getElementById('setores-container');
    const carrossel        = document.getElementById('carrossel-avaliacao');
    const slideResumo      = document.getElementById('slide-resumo');
    const slideFinal       = document.getElementById('slide-final');
    const nav              = document.getElementById('carrossel-nav');

    const btnVoltar  = document.getElementById('btn-voltar');
    const btnAvancar = document.getElementById('btn-avancar');

    let slides = [];
    let slideAtual = 0;

    // ==========================================================
    // Funções utilitárias
    // ==========================================================

    function esconderTudo() {
        slides.forEach(s => s.classList.add('oculto'));
    }

    function mostrarSlide(index) {
        esconderTudo();

        const slide = slides[index];
        slide.classList.remove('oculto');

        if (slide.id === "slide-resumo") {
            gerarResumo(); // vem do arquivo de gráficos
            btnAvancar.textContent = "Avançar ➜";
        }

        else if (slide.id === "slide-final") {

            const campoData = document.getElementById("data_avaliacao");
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

        const inputs = slide.querySelectorAll('.input-nota');

        for (let i of inputs) {
            if (!i.value) return false;
        }
        return true;
    }

    function calcularNotaSetor(index) {
        const slide = slides[index];
        const inputs = slide.querySelectorAll('.input-nota');

        let soma = 0;
        let total = 0;

        inputs.forEach(i => {
            soma += parseInt(i.value);
            total++;
        });

        const media = soma / total;
        const inputNota = slide.querySelector('.nota-setor-auto');
        if (inputNota) {
            inputNota.value = media.toFixed(2);
        }
    }

    // ==========================================================
    // Observações gerais opcionais
    // ==========================================================

    const btnAddObs = document.getElementById("btn-add-observacao");
    const obsWrapper = document.getElementById("obs-wrapper");

    if (btnAddObs) {
        btnAddObs.addEventListener("click", () => {
            obsWrapper.classList.remove("oculto");
            btnAddObs.style.display = "none";
        });
    }

    // ==========================================================
    // Carregar setores via AJAX
    // ==========================================================

    lojaSelect.addEventListener('change', () => {
        const lojaId = lojaSelect.value;

        if (!lojaId) {
            setoresContainer.classList.add('oculto');
            carrossel.innerHTML = '';
            nav.classList.add('oculto');
            return;
        }

        document.getElementById('loja_id_hidden').value = lojaId;

        fetch(`/ajax/carregar_setores_loja.php?loja_id=${lojaId}`)
            .then(res => res.text())
            .then(html => {

                carrossel.innerHTML = html;

                carrossel.appendChild(slideResumo);
                carrossel.appendChild(slideFinal);

                slides = [...carrossel.querySelectorAll('.carrossel-slide')];

                slideAtual = 0;
                mostrarSlide(0);

                setoresContainer.classList.remove('oculto');
                nav.classList.remove('oculto');

                carrossel.querySelectorAll('.btn-nota').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const valor = btn.dataset.valor;
                        const grupo = btn.closest('.criterio-item');
                        const input = grupo.querySelector('.input-nota');

                        grupo.querySelectorAll('.btn-nota').forEach(b => b.classList.remove('ativo'));
                        btn.classList.add('ativo');
                        input.value = valor;
                    });
                });
            });
    });

    // ==========================================================
    // Navegação
    // ==========================================================

    btnAvancar.addEventListener('click', () => {

        if (!validarSlide(slideAtual)) {
            alert('Avalie todos os critérios antes de avançar.');
            return;
        }

        if (slides[slideAtual].dataset.setorId) {
            calcularNotaSetor(slideAtual);
        }

        if (slideAtual === slides.length - 1) {
            finalizarAvaliacao();
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
// Finalizar avaliação
// ==========================================================

async function finalizarAvaliacao() {

    const lojaId = document.getElementById("loja_id_hidden").value;
    const avaliadorId = document.getElementById("avaliador_id").value;
    const responsavelNome = document.getElementById("responsavel_nome").value;
    const observacaoFinal = document.getElementById("observacao_final")?.value || "";
    const dataAvaliacao = document.getElementById("data_avaliacao").value;

    if (!responsavelNome.trim()) {
        alert("Por favor, informe o nome do responsável.");
        return;
    }

    if (!signaturePad || signaturePad.isEmpty()) {
        alert("Por favor, assine antes de finalizar.");
        return;
    }

    const assinaturaBase64 = signaturePad.toDataURL();
    document.getElementById("assinatura_base64").value = assinaturaBase64;

    const setores = [];

    document.querySelectorAll(".carrossel-slide[data-setor-id]").forEach(slide => {
        const setorId = slide.dataset.setorId;
        const nota = slide.querySelector(".nota-setor-auto").value;
        const obs = slide.querySelector(".obs-setor")?.value || "";

        setores.push({
            setor_id: parseInt(setorId),
            nota_setor: parseFloat(nota),
            observacao: obs
        });
    });

    const dadosAvaliacao = {
        loja_id: parseInt(lojaId),
        avaliador_id: parseInt(avaliadorId),
        responsavel_nome: responsavelNome,
        assinatura: assinaturaBase64,
        observacao_final: observacaoFinal,
        data_avaliacao: dataAvaliacao,
        setores: setores
    };

    try {
        const resposta = await fetch("avaliacoes_loja_salvar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(dadosAvaliacao)
        });

        const resultado = await resposta.json();

        if (resultado.status === "ok") {

            if (typeof mostrarMensagem === "function") {
                mostrarMensagem("Avaliação salva com sucesso!", "sucesso");
            }

            setTimeout(() => {
                window.location.href = "/modulos/avaliacoes_loja.php";
            }, 1200);

        } else {
            alert("Erro ao salvar: " + resultado.mensagem);
        }

    } catch (error) {
        console.error("Erro ao enviar:", error);
        alert("Falha ao enviar avaliação.");
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
// Reajustar canvas ao redimensionar janela
// ==========================================================
window.addEventListener("resize", () => {
    if (signaturePad) {
        ajustarCanvasAssinatura();
        signaturePad.clear();
    }
});

// ==========================================================
// Carregar últimas avaliações (somente tabela)
// ==========================================================
document.addEventListener("DOMContentLoaded", carregarUltimasAvaliacoes);

function carregarUltimasAvaliacoes() {

    fetch("/ajax/carregar_ultimas_avaliacoes.php")
        .then(res => res.json())
        .then(lista => {

            const tbody = document.getElementById("lista-avaliacoes");
            tbody.innerHTML = "";

            if (lista.length === 0) {
                tbody.innerHTML = "<tr><td colspan='4'>Nenhuma avaliação encontrada.</td></tr>";
                return;
            }

            lista.forEach(av => {

                let classeNota = "nota-ruim";
                if (av.nota_geral >= 75) classeNota = "nota-bom";
                else if (av.nota_geral >= 40) classeNota = "nota-parcial";

                tbody.innerHTML += `
                    <tr>
                        <td>${av.loja}</td>
                        <td class="${classeNota}">${parseFloat(av.nota_geral).toFixed(2)}</td>
                        <td>${av.data_avaliacao}</td>
                        <td>
                            <button class="btn-detalhes" data-id="${av.id}">
                                Detalhes
                            </button>
                        </td>
                    </tr>
                `;
            });
        });
}
