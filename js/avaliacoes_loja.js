console.log("JS específico da LOJA carregado!");

// ==========================================================
// 1. BLOCO PRINCIPAL — CARREGAR SETORES, CARROSSEL, ASSINATURA
// ==========================================================
document.addEventListener("DOMContentLoaded", () => {

    const selectLoja       = document.getElementById("item_id");
    const setoresContainer = document.getElementById("setores-container");
    const carrossel        = document.getElementById("carrossel-avaliacao");
    const nav              = document.getElementById("carrossel-nav");
    const btnConfig        = document.getElementById("btn-configurar");

    if (!selectLoja) {
        console.error("ERRO: Select de loja não encontrado!");
        return;
    }

    // ==========================================================
    // 1.1. CARREGAR SETORES DA LOJA E MONTAR CARROSSEL
    // ==========================================================
    selectLoja.addEventListener("change", () => {

        const lojaId = selectLoja.value;

        if (!lojaId) {
            setoresContainer.classList.add("oculto");
            carrossel.querySelectorAll(".carrossel-slide[data-setor-id]").forEach(s => s.remove());
            nav.classList.add("oculto");
            return;
        }

        document.getElementById("item_id_hidden").value = lojaId;

        fetch(`/ajax/carregar_setores_loja.php?loja_id=${lojaId}`)
            .then(res => res.text())
            .then(html => {

                // 1) Remove apenas slides dinâmicos
                carrossel.querySelectorAll(".carrossel-slide[data-setor-id]").forEach(s => s.remove());

                // 2) Insere os novos slides antes do slide-resumo
                const slideResumo = document.getElementById("slide-resumo");
                slideResumo.insertAdjacentHTML("beforebegin", html);

                // 3) Atualiza lista de slides
                slides = [...carrossel.querySelectorAll(".carrossel-slide")];
                slideAtual = 0;

                // 4) Mostra o primeiro slide
                mostrarSlide(0);

                setoresContainer.classList.remove("oculto");
                nav.classList.remove("oculto");

                // ==========================================================
                // 5) NAVEGAÇÃO DO CARROSSEL
                // ==========================================================
                const btnVoltar  = document.getElementById("btn-voltar");
                const btnAvancar = document.getElementById("btn-avancar");

                btnAvancar.onclick = () => {

                    if (!validarSlide(slideAtual)) {
                        mostrarMensagemSeguro("Avalie todos os critérios antes de avançar.", "aviso");
                        return;
                    }

                    if (slides[slideAtual].dataset.setorId) {
                        calcularNotaSetor(slideAtual);
                    }

                    if (slideAtual === slides.length - 1) {
                        finalizarAvaliacaoLoja();
                        return;
                    }

                    slideAtual++;
                    mostrarSlide(slideAtual);

                    if (slides[slideAtual].id === "slide-final") {
                        setTimeout(() => {
                            const canvas = document.getElementById("signature-pad");
                            ajustarCanvasAssinatura();
                            signaturePad = new SignaturePad(canvas, { backgroundColor: "white" });
                        }, 50);
                    }
                };

                btnVoltar.onclick = () => {
                    if (slideAtual === 0) return;

                    slideAtual--;
                    mostrarSlide(slideAtual);

                    if (slides[slideAtual].id === "slide-final") {
                        setTimeout(() => {
                            const canvas = document.getElementById("signature-pad");
                            ajustarCanvasAssinatura();
                            signaturePad = new SignaturePad(canvas, { backgroundColor: "white" });
                        }, 50);
                    }
                };

                // ==========================================================
                // 6) BOTÕES DE NOTA
                // ==========================================================
                carrossel.querySelectorAll(".btn-nota").forEach(btn => {
                    btn.addEventListener("click", () => {
                        const valor = btn.dataset.valor;
                        const grupo = btn.closest(".criterio-item");
                        const input = grupo.querySelector(".input-nota");

                        grupo.querySelectorAll(".btn-nota").forEach(b => b.classList.remove("ativo"));
                        btn.classList.add("ativo");
                        input.value = valor;
                    });
                });

                // ==========================================================
                // 7) INICIALIZA ASSINATURA SE O PRIMEIRO SLIDE FOR FINAL
                // ==========================================================
                if (slides[0].id === "slide-final") {
                    setTimeout(() => {
                        const canvas = document.getElementById("signature-pad");
                        ajustarCanvasAssinatura();
                        signaturePad = new SignaturePad(canvas, { backgroundColor: "white" });
                    }, 50);
                }

            });
    });

    // ==========================================================
    // 2. CARREGAR ÚLTIMAS AVALIAÇÕES
    // ==========================================================
    carregarUltimasAvaliacoesBase("loja");

    // ==========================================================
    // 3. BOTÃO CONFIGURAR SETORES
    // ==========================================================
    if (btnConfig) {
        btnConfig.addEventListener("click", () => {
            const loja = selectLoja.value || "";
            btnConfig.href = "avaliacoes_setores.php?loja=" + loja;
        });
    }
});

// ==========================================================
// 4. FUNÇÃO DE SALVAMENTO ESPECÍFICA DA LOJA
// ==========================================================
async function finalizarAvaliacaoLoja() {

    let dados = finalizarAvaliacaoBase();
    if (!dados) return;

    // Ajuste para o PHP antigo
    dados.loja_id = dados.item_id;
    delete dados.item_id;

    try {
        const resposta = await fetch("/modulos/avaliacoes_loja_salvar.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(dados)
        });

        const resultado = await resposta.json();

        if (resultado.status === "ok") {
            mostrarMensagemSeguro("Avaliação salva com sucesso!", "sucesso");
            setTimeout(() => window.location.reload(), 1200);
        } else {
            mostrarMensagemSeguro("Erro ao salvar: " + resultado.mensagem, "erro");
        }

    } catch (e) {
        console.error("Erro ao enviar:", e);
        mostrarMensagemSeguro("Falha ao enviar avaliação.", "erro");
    }
}
