document.addEventListener('DOMContentLoaded', function () {
  const form  = document.getElementById('filtres-form');
  const cards = document.querySelectorAll('.menu-card-wrapper');

  function filtrer() {
    const theme    = document.getElementById('filtre-theme').value;
    const regime   = document.getElementById('filtre-regime').value;
    const prixMin  = parseInt(document.getElementById('filtre-prix-min').value)  || 0;
    const prixMax  = parseInt(document.getElementById('filtre-prix-max').value)  || Infinity;
    const personnes = parseInt(document.getElementById('filtre-personnes').value) || Infinity;

    cards.forEach(function (card) {
      const matchTheme    = !theme   || card.dataset.theme   === theme;
      const matchRegime   = !regime  || card.dataset.regime  === regime;
      const matchPrix     = parseInt(card.dataset.prix)      >= prixMin
                         && parseInt(card.dataset.prix)      <= prixMax;
      const matchPersonnes = parseInt(card.dataset.personnes) <= personnes;

      card.style.display = (matchTheme && matchRegime && matchPrix && matchPersonnes)
        ? ''
        : 'none';
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    filtrer();
  });

  form.addEventListener('reset', function () {
    setTimeout(function () {
      cards.forEach(function (card) {
        card.style.display = '';
      });
    }, 0);
  });
});
