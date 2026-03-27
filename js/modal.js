function abrirModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;

  modal.style.display = 'block';
}

function fecharModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.style.display = 'none';
  }
}
