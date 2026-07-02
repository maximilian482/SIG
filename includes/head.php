<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= $titulo ?? 'Portal Corporativo' ?></title>

  <!-- BOOTSTRAP 5.3.3 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- CSS GLOBAL DO SISTEMA -->
  <link rel="stylesheet" href="/css/layout.css">
  <link rel="stylesheet" href="/css/cards.css">
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/menu_perfil.css">

  <!-- FontAwesome -->
  <link rel="stylesheet" href="/assets/fontawesome/css/all.css">

  <!-- Quill -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

  <!-- CSS específico da página -->
  <?php if (!empty($cssExtra)): ?>
      <link rel="stylesheet" href="<?= $cssExtra ?>">
  <?php endif; ?>
</head>
