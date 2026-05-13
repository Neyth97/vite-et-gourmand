/* ============================================================
   Lightbox galerie
   ============================================================ */
const lightbox = document.getElementById('lightbox');

if (lightbox) {
  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxClose = document.getElementById('lightbox-close');

  document.querySelectorAll('.detail-galerie-img img').forEach(img => {
    img.addEventListener('click', () => {
      lightboxImg.src = img.src;
      lightboxImg.alt = img.alt;
      lightbox.hidden = false;
      lightboxClose.focus();
    });
  });

  function closeLightbox() {
    lightbox.hidden = true;
    lightboxImg.src = '';
  }

  lightboxClose.addEventListener('click', closeLightbox);

  lightbox.addEventListener('click', e => {
    if (e.target === lightbox) closeLightbox();
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && !lightbox.hidden) closeLightbox();
  });
}

/* ============================================================
   Page commande — récapitulatif dynamique
   ============================================================ */
const commandeForm = document.getElementById('commande-form');

if (commandeForm) {
  const menus = {
    'classique-prestige':  { nom: 'Classique Prestige',  prixBase: 125, min: 10 },
    'noel-gourmand':       { nom: 'Noël Gourmand',       prixBase: 280, min: 8  },
    'paques-famille':      { nom: 'Pâques en Famille',   prixBase: 160, min: 6  },
    'cocktail-evenement':  { nom: 'Cocktail Événement',  prixBase: 360, min: 20 },
    'vegetarien-festif':   { nom: 'Végétarien Festif',   prixBase: 190, min: 10 },
    'vegan-equilibre':     { nom: 'Vegan Équilibré',     prixBase: 150, min: 8  },
  };

  const menuSelect       = document.getElementById('menu-choisi');
  const nbInput          = document.getElementById('nb-personnes');
  const villeInput       = document.getElementById('ville');

  const elMenuNom        = document.getElementById('recap-menu-nom');
  const elNbPersonnes    = document.getElementById('recap-nb-personnes');
  const elPrixMenu       = document.getElementById('recap-prix-menu');
  const elLivraison      = document.getElementById('recap-livraison');
  const elReductionLigne = document.getElementById('recap-reduction-ligne');
  const elReduction      = document.getElementById('recap-reduction');
  const elTotal          = document.getElementById('recap-total');

  elReductionLigne.hidden = true;

  function euro(montant) {
    return montant.toFixed(2).replace('.', ',') + ' €';
  }

  function updateRecap() {
    const menu = menus[menuSelect.value];
    const nb   = parseInt(nbInput.value, 10);
    const ville = villeInput.value.trim().toLowerCase();

    if (!menu) {
      elMenuNom.textContent     = '—';
      elNbPersonnes.textContent = '—';
      elPrixMenu.textContent    = '—';
      elLivraison.textContent   = '—';
      elReductionLigne.hidden   = true;
      elTotal.textContent       = '—';
      return;
    }

    elMenuNom.textContent = menu.nom;

    const prixParPersonne = menu.prixBase / menu.min;

    /* Nombre de personnes — vérification du minimum */
    if (!nb || nb < menu.min) {
      elNbPersonnes.textContent = nb ? `${nb} (min. ${menu.min})` : '—';
      elPrixMenu.textContent    = '—';
      elLivraison.textContent   = '—';
      elReductionLigne.hidden   = true;
      elTotal.textContent       = '—';
      return;
    }

    elNbPersonnes.textContent = `${nb} personnes`;

    /* Prix du menu */
    const prixMenu = nb * prixParPersonne;
    elPrixMenu.textContent = euro(prixMenu);

    /* Réduction 10 % si nb >= min + 5 */
    const hasReduction     = nb >= menu.min + 5;
    const montantReduction = hasReduction ? prixMenu * 0.10 : 0;

    elReductionLigne.hidden = !hasReduction;
    if (hasReduction) {
      elReduction.textContent = `− ${euro(montantReduction)}`;
    }

    /* Frais de livraison */
    let prixLivraison = 0;
    let livraisonAffiche = '';

    if (ville === '') {
      livraisonAffiche = '—';
    } else if (ville === 'bordeaux') {
      livraisonAffiche = 'Gratuite';
    } else {
      prixLivraison    = 5;
      livraisonAffiche = '5,00 € + frais kilométriques';
    }
    elLivraison.textContent = livraisonAffiche;

    /* Total */
    if (ville === '') {
      elTotal.textContent = '—';
    } else {
      const total = prixMenu - montantReduction + prixLivraison;
      elTotal.textContent = euro(total) + (ville !== 'bordeaux' ? ' + frais km' : '');
    }
  }

  menuSelect.addEventListener('change', () => {
    const menu = menus[menuSelect.value];
    if (menu) {
      nbInput.min         = menu.min;
      nbInput.placeholder = `Minimum : ${menu.min} personnes`;
    }
    updateRecap();
  });

  nbInput.addEventListener('input', updateRecap);
  villeInput.addEventListener('input', updateRecap);
}
