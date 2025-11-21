// Validação do formulário de edição de perfil
function validarFormulario() {
  const email = document.querySelector("[name='email']").value.trim();
  const telefone = document.querySelector("[name='telefone']").value.trim();
  const endereco = document.querySelector("[name='endereco']").value.trim();

  if (!email || !telefone || !endereco) {
    alert("⚠️ Preencha todos os campos obrigatórios.");
    return false;
  }
  return true;
}

// Alternar entre modo de visualização e edição
function ativarEdicao() {
  document.getElementById("viewMode").style.display = "none";
  document.getElementById("editMode").style.display = "block";
}

function cancelarEdicao() {
  document.getElementById("editMode").style.display = "none";
  document.getElementById("viewMode").style.display = "block";
}

// Modal de senha
function abrirModalSenha() {
  document.getElementById("modalSenha").style.display = "block";
}

function fecharModalSenha() {
  document.getElementById("modalSenha").style.display = "none";
}

function validarSenha() {
  const atual = document.querySelector("[name='senha_atual']").value;
  const nova = document.querySelector("[name='nova_senha']").value;
  const confirmar = document.querySelector("[name='confirmar_senha']").value;

  if (!atual || !nova || !confirmar) {
    alert("⚠️ Preencha todos os campos de senha.");
    return false;
  }
  if (nova !== confirmar) {
    alert("⚠️ As senhas não coincidem.");
    return false;
  }
  if (nova.length < 6) {
    alert("⚠️ A nova senha deve ter pelo menos 6 caracteres.");
    return false;
  }
  return true;
}

function resetarSenha() {
  if (confirm("Deseja realmente resetar sua senha? A nova senha será os 6 primeiros dígitos do seu CPF.")) {
    window.location = "resetar_senha.php";
  }
}

// Modal de foto
function abrirModalFoto() {
  document.getElementById("modalFoto").style.display = "block";
}

function fecharModalFoto() {
  document.getElementById("modalFoto").style.display = "none";
}

// Fecha modal ao clicar fora
window.onclick = function(event) {
  const modalFoto = document.getElementById("modalFoto");
  if (event.target === modalFoto) {
    modalFoto.style.display = "none";
  }
}

// Validação de tamanho da imagem no cliente
document.addEventListener("DOMContentLoaded", function () {
  const inputFoto = document.getElementById("nova_foto");
  if (inputFoto) {
    inputFoto.addEventListener("change", function () {
      const file = this.files[0];
      if (file) {
        const maxSize = 2 * 1024 * 1024; // 2 MB
        if (file.size > maxSize) {
          alert("⚠️ O arquivo é muito grande! O limite é 2 MB.");
          this.value = ""; // limpa o campo
        }
      }
    });
  }
});
