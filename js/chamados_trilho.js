// ============================================================
// TROCA DE ABAS
// ============================================================

document.querySelectorAll(".aba").forEach(btn => {
    btn.addEventListener("click", () => {

        document.querySelectorAll(".aba").forEach(a => a.classList.remove("ativa"));
        btn.classList.add("ativa");

        const aba = btn.dataset.aba;

        document.querySelectorAll(".conteudo-aba").forEach(div => div.classList.remove("ativo"));
        document.getElementById(aba).classList.add("ativo");

        document.getElementById("filtros-abertos").style.display   = "none";
        document.getElementById("filtros-rota").style.display      = "none";
        document.getElementById("filtros-entregues").style.display = "none";

        if (aba === "abertos")   document.getElementById("filtros-abertos").style.display = "flex";
        if (aba === "rota")      document.getElementById("filtros-rota").style.display = "flex";
        if (aba === "entregues") document.getElementById("filtros-entregues").style.display = "flex";

        if (aba === "abertos")   atualizarAbertos();
        if (aba === "rota")      atualizarRota();
        if (aba === "entregues") atualizarEntregues();
    });
});


/* ================================
   MODAL DE DETALHES
================================ */
const modalDetalhes = document.getElementById('modalDetalhes');
const modalBody = document.getElementById('modal-body-detalhes');

document.querySelectorAll('.btn-detalhes').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;

        fetch(`/modulos/chamados_trilho_detalhes.php?id=${id}`)
            .then(r => r.text())
            .then(html => {
                modalBody.innerHTML = html;
                modalDetalhes.style.display = 'block';
            });
    });
});

document.querySelector('#modalDetalhes .modal-fechar').onclick = () => {
    modalDetalhes.style.display = 'none';
};


/* ================================
   MODAL DE TIPO (MEDICAMENTO / PERFUMARIA)
================================ */

const modalTipo = document.getElementById('modalTipoProtocolo');

// Abrir modal ao clicar em Novo Protocolo
document.querySelector('.btn-novo').addEventListener('click', e => {
    e.preventDefault();
    modalTipo.style.display = 'block';
});

// Fechar no X
document.querySelector('#modalTipoProtocolo .modal-fechar').onclick = () => {
    modalTipo.style.display = 'none';
};

// Fechar ao clicar fora
window.addEventListener('click', e => {
    if (e.target === modalTipo) {
        modalTipo.style.display = 'none';
    }
});

// Fechar com ESC
document.addEventListener('keydown', e => {
    if (e.key === "Escape") {
        modalTipo.style.display = 'none';
    }
});

// Escolha do tipo (Medicamento / Perfumaria)
document.querySelectorAll('#modalTipoProtocolo .btn-tipo').forEach(btn => {
    btn.addEventListener('click', () => {
        const tipo = btn.dataset.tipo;
        window.location = `chamados_trilho_abrir.php?tipo=${tipo}`;
    });
});


/* ================================
   MODAL DE FATURAMENTO
================================ */
const modalFaturar = document.getElementById('modalFaturar');
let idFaturar = null;

document.querySelectorAll('.btn-faturar').forEach(btn => {
    btn.addEventListener('click', () => {
        idFaturar = btn.dataset.id;
        modalFaturar.style.display = 'block';
    });
});

document.getElementById('fecharModalFaturar').onclick = () => {
    modalFaturar.style.display = 'none';
};

document.getElementById('btnCancelarFaturar').onclick = () => {
    modalFaturar.style.display = 'none';
};

document.getElementById('btnConfirmarFaturar').onclick = () => {
    const nota = document.getElementById('notaTransferencia').value.trim();

    if (nota === "") {
        alert("Informe a nota de transferência.");
        return;
    }

    fetch('/modulos/chamados_trilho_faturar.php', {
        method: 'POST',
        body: new URLSearchParams({
            id: idFaturar,
            nota: nota
        })
    })
    .then(r => r.text())
    .then(() => location.reload());
};


/* ================================
   COLETAR
================================ */
document.querySelectorAll('.btn-coletar').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;

        if (!confirm("Confirmar coleta deste protocolo?")) return;

        fetch('/modulos/chamados_trilho_coletar.php', {
            method: 'POST',
            body: new URLSearchParams({ id })
        })
        .then(r => r.text())
        .then(() => location.reload());
    });
});


/* ================================
   FINALIZAR ENTREGA
================================ */
document.querySelectorAll('.btn-entregar').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;

        if (!confirm("Finalizar entrega deste protocolo?")) return;

        fetch('/modulos/chamados_trilho_entregar.php', {
            method: 'POST',
            body: new URLSearchParams({ id })
        })
        .then(r => r.text())
        .then(() => location.reload());
    });
});


/* ================================
   EXCLUIR
================================ */
document.querySelectorAll('.btn-excluir').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;

        if (!confirm("Tem certeza que deseja excluir este protocolo?")) return;

        fetch('/modulos/chamados_trilho_excluir.php', {
            method: 'POST',
            body: new URLSearchParams({ id })
        })
        .then(r => r.text())
        .then(() => location.reload());
    });
});


// ============================================================
// FILTROS POR ABA (VERSÃO MÍNIMA)
// ============================================================

const filtroLib      = document.getElementById("filtro-lib");
const filtroSolic    = document.getElementById("filtro-solic");
const filtroEntregue = document.getElementById("filtro-entregue");

const btnLimparAbertos   = document.getElementById("btn-limpar-abertos");
const btnLimparRota      = document.getElementById("btn-limpar-rota");
const btnLimparEntregues = document.getElementById("btn-limpar-entregues");

const boxAbertos   = document.querySelector("#abertos");
const boxRota      = document.querySelector("#rota");
const boxEntregues = document.querySelector("#entregues");

if (filtroLib && filtroSolic && filtroEntregue) {

    fetch("/ajax/trilho_filtros_listas.php")
        .then(r => r.json())
        .then(dados => {

            dados.destinos.forEach(d => {
                filtroLib.innerHTML      += `<option value="${d.id}">${d.nome}</option>`;
                filtroEntregue.innerHTML += `<option value="${d.id}">${d.nome}</option>`;
            });

            dados.origens.forEach(o => {
                filtroSolic.innerHTML += `<option value="${o.id}">${o.nome}</option>`;
            });

            atualizarAbertos();
            atualizarRota();
            atualizarEntregues();
        });

    function atualizarAbertos() {
        fetch(`/ajax/trilho_abertos.php?lib=${filtroLib.value}`)
            .then(r => r.text())
            .then(html => {
                boxAbertos.innerHTML = html;
                reativarEventosTrilho();
            });
    }

    function atualizarRota() {
        fetch(`/ajax/trilho_rota.php?solic=${filtroSolic.value}`)
            .then(r => r.text())
            .then(html => {
                boxRota.innerHTML = html;
                reativarEventosTrilho();
            });
    }

    function atualizarEntregues() {
        fetch(`/ajax/trilho_entregues.php?loja=${filtroEntregue.value}`)
            .then(r => r.text())
            .then(html => {
                boxEntregues.innerHTML = html;
                reativarEventosTrilho();
            });
    }

    filtroLib.addEventListener("change", atualizarAbertos);
    filtroSolic.addEventListener("change", atualizarRota);
    filtroEntregue.addEventListener("change", atualizarEntregues);

    btnLimparAbertos.onclick = () => { filtroLib.value = ""; atualizarAbertos(); };
    btnLimparRota.onclick    = () => { filtroSolic.value = ""; atualizarRota(); };
    btnLimparEntregues.onclick = () => { filtroEntregue.value = ""; atualizarEntregues(); };
}


// ============================================================
// REATIVAR EVENTOS APÓS AJAX
// ============================================================

function reativarEventosTrilho() {

    // DETALHES
    document.querySelectorAll('.btn-detalhes').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            fetch(`/modulos/chamados_trilho_detalhes.php?id=${id}`)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('modal-body-detalhes').innerHTML = html;
                    document.getElementById('modalDetalhes').style.display = 'block';
                });
        };
    });

    // EDITAR 
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            window.location.href = `/modulos/chamados_trilho_editar.php?id=${id}`;
        };
    });

    // FATURAR
    document.querySelectorAll('.btn-faturar').forEach(btn => {
        btn.onclick = () => {
            idFaturar = btn.dataset.id;
            modalFaturar.style.display = 'block';
        };
    });

    // COLETAR
    document.querySelectorAll('.btn-coletar').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            if (!confirm("Confirmar coleta deste protocolo?")) return;
            fetch('/modulos/chamados_trilho_coletar.php', {
                method: 'POST',
                body: new URLSearchParams({ id })
            }).then(() => location.reload());
        };
    });

    // ENTREGAR
    document.querySelectorAll('.btn-entregar').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            if (!confirm("Finalizar entrega deste protocolo?")) return;
            fetch('/modulos/chamados_trilho_entregar.php', {
                method: 'POST',
                body: new URLSearchParams({ id })
            }).then(() => location.reload());
        };
    });

    // EXCLUIR
    document.querySelectorAll('.btn-excluir').forEach(btn => {
        btn.onclick = () => {
            const id = btn.dataset.id;
            if (!confirm("Tem certeza que deseja excluir este protocolo?")) return;
            fetch('/modulos/chamados_trilho_excluir.php', {
                method: 'POST',
                body: new URLSearchParams({ id })
            }).then(() => location.reload());
        };
    });
}


window.addEventListener("click", e => {
    if (e.target === modalDetalhes) {
        modalDetalhes.style.display = "none";
    }
});
document.addEventListener("keydown", e => {
    if (e.key === "Escape") {
        modalDetalhes.style.display = "none";
    }
});

