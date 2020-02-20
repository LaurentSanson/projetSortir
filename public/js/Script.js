function affichageDivGroupe() {

    let etat = document.getElementById("sortieCheck").checked;
    let hidden = document.createAttribute("hidden");

    if (etat) {
        document.getElementById('div.groupe').removeAttribute('hidden');
    } else {
        document.getElementById('div.groupe').setAttributeNode(hidden);
    }
}