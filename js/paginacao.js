function mudarPagina(pagina) {
  const url = new URL(window.location.href);
  url.searchParams.set("pagina", pagina);
  window.location.href = url.toString();
}
