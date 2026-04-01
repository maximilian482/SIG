document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // TROCA DE ABAS
    // ============================
    document.querySelectorAll(".aba").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".aba").forEach(b => b.classList.remove("ativa"));
            document.querySelectorAll(".conteudo-aba").forEach(c => c.classList.remove("ativo"));

            btn.classList.add("ativa");
            document.getElementById(btn.dataset.aba).classList.add("ativo");
        });
    });

    // ============================
    // CONTADORES
    // ============================
    function atualizarContadores() {
        const totalColetar = document.querySelectorAll("#coletar .card-trilho").length;
        const totalRota = document.querySelectorAll("#rota .card-trilho").length;
        const totalEntregues = document.querySelectorAll("#entregues .card-trilho").length;

        document.querySelector('button[data-aba="coletar"]').innerText = `Coletar (${totalColetar})`;
        document.querySelector('button[data-aba="rota"]').innerText = `Em rota (${totalRota})`;
        document.querySelector('button[data-aba="entregues"]').innerText = `Entregues (${totalEntregues})`;
    }

    atualizarContadores();


   // ============================
// MODAL DE DETALHES
// ============================
const modal = document.getElementById("modalDetalhes");
const modalBody = document.getElementById("modal-body-detalhes");
const fechar = document.querySelector(".modal-fechar");

// Fechar modal no X
fechar.addEventListener("click", () => {
    modal.style.display = "none";
});

// Fechar clicando fora do conteúdo
window.addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
    }
});

// Abrir modal (delegação)
document.addEventListener("click", (e) => {
    if (e.target.classList.contains("btn-detalhes")) {

        const id = e.target.dataset.id;
        if (!id) return;

        modal.style.display = "flex";
        modalBody.innerHTML = "Carregando...";

        fetch("chamados_trilho_detalhes.php?id=" + id)
            .then(r => r.text())
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(() => {
                modalBody.innerHTML = "<p style='color:red;'>Erro ao carregar detalhes.</p>";
            });
    }
});



    // ============================
    // COLETAR (delegação)
    // ============================
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("btn-coletar")) {

            const id = e.target.dataset.id;
            if (!id) return;

            if (!confirm("Confirmar coleta?")) return;

            fetch("trilho_motoboy_coletar.php?id=" + id)
                .then(r => r.json())
                .then(res => {
                    if (res.sucesso) {
                        mostrarMensagem("Coleta registrada com sucesso!", "sucesso");
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        mostrarMensagem(res.mensagem || "Erro ao coletar.", "erro");
                    }
                })
                .catch(() => mostrarMensagem("Erro de comunicação com o servidor.", "erro"));
        }
    });


    // ============================
    // ENTREGAR (delegação)
    // ============================
    document.addEventListener("click", (e) => {
        if (e.target.classList.contains("btn-entregar")) {

            const id = e.target.dataset.id;
            if (!id) return;

            mostrarMensagem("Redirecionando para assinatura...", "info");

            setTimeout(() => {
                window.location.href = "trilho_assinar.php?id=" + id;
            }, 600);
        }
    });

});
