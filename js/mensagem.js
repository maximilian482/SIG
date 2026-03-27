document.addEventListener('DOMContentLoaded', () => {
  const inputNome = document.getElementById('destinatario_nome');
  const inputId   = document.getElementById('destinatario_id');
  const dataList  = document.getElementById('lista-funcionarios');

  if (!inputNome || !inputId || !dataList) return;

  inputNome.addEventListener('input', () => {
    const valor = inputNome.value.trim();
    let idEncontrado = '';

    for (const opt of dataList.options) {
      if (opt.value === valor) {
        idEncontrado = opt.getAttribute('data-id') || '';
        break;
      }
    }

    inputId.value = idEncontrado;
  });
});
