<?php
function redimensionarEComprimir($origem, $destino, $maxLargura = 1920, $qualidade = 80) {
  $info = getimagesize($origem);
  $tipo = $info['mime'] ?? '';

  switch ($tipo) {
    case 'image/jpeg':
    case 'image/jpg':
      $imagem = @imagecreatefromjpeg($origem);
      break;
    case 'image/png':
      $imagem = @imagecreatefrompng($origem);
      break;
    case 'image/webp':
      $imagem = @imagecreatefromwebp($origem);
      break;
    default:
      echo json_encode(['erro' => 'Formato não suportado']);
      return false;
  }

  if (!$imagem) {
    echo json_encode(['erro' => 'Erro ao processar imagem']);
    return false;
  }

  $largura = imagesx($imagem);
  $altura = imagesy($imagem);

  if ($largura > $maxLargura) {
    $novaLargura = $maxLargura;
    $novaAltura = intval(($altura / $largura) * $novaLargura);
    $canvas = imagecreatetruecolor($novaLargura, $novaAltura);
    $branco = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $branco);
    imagecopyresampled($canvas, $imagem, 0, 0, 0, 0, $novaLargura, $novaAltura, $largura, $altura);
  } else {
    $canvas = $imagem;
  }

  if (!imagejpeg($canvas, $destino, $qualidade)) {
    echo json_encode(['erro' => 'Falha ao salvar imagem']);
    return false;
  }

  return true;
}

if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
  $arquivo = $_FILES['imagem'];
  $nome = uniqid('img_') . '.jpg';
  $caminhoTemporario = $arquivo['tmp_name'];
  $caminhoFinal = "../uploads/$nome";

  if (redimensionarEComprimir($caminhoTemporario, $caminhoFinal)) {
    echo json_encode(['url' => "uploads/$nome"]);
    exit;
  } else {
    echo json_encode(['erro' => 'Erro ao processar imagem']);
    exit;
  }
}

echo json_encode(['erro' => 'Nenhum arquivo enviado']);
