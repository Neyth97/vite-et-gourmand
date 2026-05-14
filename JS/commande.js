'use strict';

(function () {
    const MENUS = window.CMD_MENUS || [];

    const selMenu        = document.getElementById('menu_id');
    const inputVille     = document.getElementById('ville_prestation');
    const inputKm        = document.getElementById('distance_km');
    const inputNb        = document.getElementById('nombre_personne');
    const bloqFrais      = document.getElementById('cmd-frais-livraison');
    const bloqConditions = document.getElementById('cmd-conditions');
    const texteConditions = document.getElementById('cmd-conditions-texte');
    const bloqPersonnes  = document.getElementById('cmd-personnes-bloc');
    const hintMin        = document.getElementById('cmd-personnes-min');
    const hintReduc      = document.getElementById('cmd-reduction-hint');

    const recapVide      = document.getElementById('cmd-recap-vide');
    const recapDetail    = document.getElementById('cmd-recap-detail');
    const elMenuTitre    = document.getElementById('recap-menu-titre');
    const elNbPers       = document.getElementById('recap-nb-personnes');
    const elPrixPers     = document.getElementById('recap-prix-pers');
    const elRowReduc     = document.getElementById('recap-row-reduction');
    const elReduction    = document.getElementById('recap-reduction');
    const elPrixMenu     = document.getElementById('recap-prix-menu');
    const elLivraison    = document.getElementById('recap-livraison');
    const elTotal        = document.getElementById('recap-total');

    function formatEur(n) {
        return n.toFixed(2).replace('.', ',') + ' €';
    }

    function getMenuById(id) {
        return MENUS.find(function (m) { return m.id === id; }) || null;
    }

    function majFraisLivraison() {
        const ville = (inputVille.value || '').trim().toLowerCase();
        const horsBoroaux = ville !== '' && ville !== 'bordeaux';
        if (bloqFrais) {
            bloqFrais.hidden = !horsBoroaux;
        }
        if (inputKm) {
            inputKm.required = horsBoroaux;
        }
    }

    function majRecap() {
        const menuId = parseInt(selMenu ? selMenu.value : '', 10);
        const menu   = isNaN(menuId) ? null : getMenuById(menuId);
        const nb     = parseInt(inputNb ? inputNb.value : '', 10);
        const ville  = (inputVille ? inputVille.value : '').trim().toLowerCase();
        const km     = parseFloat(inputKm ? inputKm.value : '') || 0;

        if (!menu || isNaN(nb) || nb < 1) {
            recapVide.hidden   = false;
            recapDetail.hidden = true;
            return;
        }

        let prixMenu = nb * menu.prix;
        const aReduc = nb >= menu.min + 5;

        if (aReduc) {
            prixMenu *= 0.9;
        }

        const horsBoroaux = ville !== '' && ville !== 'bordeaux';
        const livraison   = horsBoroaux ? 5 + (km * 0.59) : 0;
        const total       = prixMenu + livraison;

        elMenuTitre.textContent = selMenu.options[selMenu.selectedIndex].text.split('—')[0].trim();
        elNbPers.textContent    = nb;
        elPrixPers.textContent  = formatEur(menu.prix);

        if (aReduc) {
            const montantReduc = (nb * menu.prix) - prixMenu;
            elReduction.textContent  = '- ' + formatEur(montantReduc);
            elRowReduc.hidden        = false;
            if (hintReduc) hintReduc.hidden = false;
        } else {
            elRowReduc.hidden = true;
            if (hintReduc) hintReduc.hidden = true;
        }

        elPrixMenu.textContent  = formatEur(prixMenu);
        elLivraison.textContent = horsBoroaux ? formatEur(livraison) : 'Incluse (Bordeaux)';
        elTotal.textContent     = formatEur(total);

        recapVide.hidden   = true;
        recapDetail.hidden = false;
    }

    function majMenu() {
        const menuId = parseInt(selMenu ? selMenu.value : '', 10);
        const menu   = isNaN(menuId) ? null : getMenuById(menuId);

        if (!menu) {
            if (bloqConditions) bloqConditions.hidden = true;
            if (bloqPersonnes)  bloqPersonnes.hidden  = true;
            return;
        }

        if (bloqConditions && texteConditions) {
            if (menu.cond && menu.cond.trim() !== '') {
                texteConditions.textContent = menu.cond;
                bloqConditions.hidden = false;
            } else {
                bloqConditions.hidden = true;
            }
        }

        if (bloqPersonnes) {
            bloqPersonnes.hidden = false;
        }

        if (hintMin) {
            hintMin.textContent = '(minimum ' + menu.min + ' personnes)';
        }

        if (inputNb) {
            inputNb.min = menu.min;
            if (!inputNb.value || parseInt(inputNb.value, 10) < menu.min) {
                inputNb.value = menu.min;
            }
        }

        majRecap();
    }

    if (selMenu)    selMenu.addEventListener('change',  majMenu);
    if (inputVille) inputVille.addEventListener('input', function () { majFraisLivraison(); majRecap(); });
    if (inputKm)    inputKm.addEventListener('input',   majRecap);
    if (inputNb)    inputNb.addEventListener('input',   majRecap);

    majFraisLivraison();
    majMenu();
}());
