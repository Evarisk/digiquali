# [DigiQuali] [23.2.1] - Activation fiable sur une instance neuve - Fiche de contrôle PDF plus lisible

Description : Version corrective. DigiQuali s'active du premier coup sur une instance neuve, la fiche de contrôle PDF gagne un en-tête épuré, des libellés traduits et le nom de l'objet contrôlé, et le clonage d'un contrôle garde enfin ses réponses.

**Cette version s'utilise avec Saturne 23.5.0 ou plus récent. Mettez-la à jour avant Saturne 23.6.0**, dont elle sait lire le nouveau registre d'objets. À partir de Saturne 23.6.0, activer DigiQuali active d'abord Saturne et Projets ; avec Saturne 23.5.0, activez Saturne en premier.

## Améliorations & corrections

### Installation

* **Activation du premier coup sur une instance neuve.** Si Saturne n'avait encore jamais été activé, l'activation échouait sur « La table llx_saturne_object_standard n'existe pas » : Dolibarr installe un module avant ses dépendances. Avec Saturne 23.6.0, DigiQuali active d'abord Saturne et Projets, et la liaison aux projets (onglets, listes, champ de fréquence de contrôle) est en place dès la première activation.
* **Plus de page des modules blanche quand Saturne manque.** L'activation de DigiQuali est refusée avec un message qui nomme le module manquant.
* **Le tag « Défaut » des modèles est créé au bon type.** Il pouvait apparaître parmi les catégories de produits. Un tag déjà mal rangé est corrigé à la mise à jour, s'il n'est utilisé par aucun produit.

### Fiche de contrôle (PDF)

* **En-tête épuré** : la mention « DigiQuali · digiquali.com » disparaît et le statut remonte sur la ligne du titre. Un titre ou un sous-titre long passe à la ligne au lieu d'être coupé ou de déborder.
* **L'objet contrôlé est nommé** : « Utilisateur — Jeanne Martin » au lieu de « Utilisateur — 6 », pour tous les types d'objets. Il ne disparaît plus quand le document est généré depuis un autre écran que la fiche du contrôle.
* **Libellés traduits dans la langue du document** : une fiche générée en anglais n'est plus à moitié en français.
* « Signés électroniquement » ne s'affiche que si tous les signataires ont signé, et les lignes Projet et Objet lié vides sont masquées.

### Contrôles et questionnaires

* **Le clonage recopie les réponses** des contrôles et des questionnaires, et le contrôleur d'un contrôle redevient modifiable.
* **Questions Pourcentage** : le curseur n'est plus écrasé à une largeur nulle sur la fiche de contrôle.
* **Questionnaires** : leurs champs complémentaires s'affichent et s'enregistrent sur la fiche.

### Compatibilité

* **Prêt pour Saturne 23.6.0 et les prochaines versions des modules Evarisk.** DigiQuali lit les chemins du registre d'objets sans supposer qu'ils commencent par `custom/` : il fonctionne avec un module installé dans un autre dossier, et avec les entrées sans ce préfixe des prochaines versions de Saturne, Digirisk, DoliMeet, DoliCar et ReedCRM. Sans ce correctif, les étiquettes QR des tâches, des expéditions et des objets DoliCar se seraient arrêtées sur une erreur.

## Testez les nouveautés maintenant

**Allez tester DigiQuali sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.0](https://github.com/Evarisk/digiquali/compare/23.2.0...23.2.1) et 23.2.1

* #2694 [Module] fix: activate dependencies before init so Saturne tables exist on a fresh instance [`#2695`](https://github.com/Evarisk/digiquali/pull/2695)
* #2697 [Module] fix: no fatal on admin/modules.php when Saturne is missing [`#2699`](https://github.com/Evarisk/digiquali/pull/2699)
* #2696 [Category] fix: create the default sheet tag with its type and repair product-typed ones [`#2698`](https://github.com/Evarisk/digiquali/pull/2698)
* #2685 [PDF] fix: fiche de contrôle sans marque en en-tête, sous-titre sur plusieurs lignes, nom de l'objet lié [`#2686`](https://github.com/Evarisk/digiquali/pull/2686)
* #2689 [PDF] fix: libellés de la fiche de contrôle traduits dans la langue du document [`#2690`](https://github.com/Evarisk/digiquali/pull/2690)
* #2687 [Control] fix: le clonage (contrôles et questionnaires) recopie les réponses et le contrôleur devient modifiable [`#2688`](https://github.com/Evarisk/digiquali/pull/2688)
* #2683 [Question] fix: largeur minimale du curseur des questions Pourcentage, écrasé à 0 px sur la fiche [`#2684`](https://github.com/Evarisk/digiquali/pull/2684)
* #2665 [Survey] fix: nommer $extrafields comme les gabarits du coeur l'attendent [`#2666`](https://github.com/Evarisk/digiquali/pull/2666)
* #2661 [Object] fix: lire class_path et create_url du registre par dol_buildpath() [`#2662`](https://github.com/Evarisk/digiquali/pull/2662)
* #2691 [Object] rework: retirer le prefixe custom/ des entrees du registre d'objets [`#2692`](https://github.com/Evarisk/digiquali/pull/2692)
* #2700 [Public] fix: load the QR label object class through dol_buildpath() [`#2701`](https://github.com/Evarisk/digiquali/pull/2701)
