# [DigiQuali] [23.5.0] - Droit de modifier la configuration - Réponses mieux contrôlées

Description : la configuration de DigiQuali peut désormais être consultée sans pouvoir être modifiée, grâce à un droit dédié ; les réponses enregistrées pendant la saisie sont contrôlées comme à l'envoi, et plusieurs avertissements disparaissent. **Mise à jour recommandée pour toutes les instances.**

**Cette version demande Saturne 23.10.1 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Configuration : consulter sans modifier

* Nouveau droit **« Modifier la configuration du module DigiQuali »**. Le droit « Accéder à la configuration » permet désormais seulement de la consulter : sans le nouveau droit, les pages de configuration s'affichent en lecture seule, avec un avis, et sans bouton Enregistrer.
* Les objets qu'un modèle peut lier se règlent avec ce droit, et plus seulement par un administrateur.
* Quand une configuration manque (fiche d'un modèle, listes des contrôles et des questionnaires, fiche d'un contrôle), le lien vers la configuration n'est proposé qu'à qui peut la modifier ; les autres sont invités à contacter un administrateur.
* **Après la mise à jour** : accordez le droit « Modifier la configuration du module DigiQuali » aux utilisateurs non administrateurs qui modifiaient la configuration.

![Configuration de DigiQuali en lecture seule, pour un utilisateur sans le droit de la modifier](https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.5.0/configuration-lecture-seule.png)

## Améliorations & corrections

### Réponses aux contrôles et aux questionnaires

* Les réponses et les commentaires enregistrés automatiquement pendant la saisie reçoivent les mêmes contrôles qu'à l'envoi final, et ils s'affichent toujours comme du texte.
* La liste des réponses aux contrôles affiche le libellé des réponses à choix multiples, et plus seulement leurs numéros.

### Photos des questions

* Les vignettes moyenne et grande d'une photo de question sont maintenant créées. Aucun fichier « Bad parameter extName » n'est plus écrit dans le dossier du module.

### Documents

* Le PDF d'un contrôle ou d'un questionnaire crée la police de ses pictogrammes dans le dossier des documents, et plus dans les fichiers de Dolibarr : elle n'est plus perdue à la mise à jour de Dolibarr.

### Avertissements

* L'onglet Médias d'un contrôle dont le modèle n'a aucune question n'affiche plus d'avertissement.
* La page publique de suivi d'un ticket ne provoque plus d'avertissement de DigiQuali.

## Testez les nouveautés maintenant

**Allez tester la configuration en lecture seule sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.4.3](https://github.com/Evarisk/digiquali/compare/23.4.3...23.5.0) et 23.5.0

* [Answer] fix: enregistrement automatique filtré comme un envoi, réponses échappées à l'affichage [`4a8ad8d3`](https://github.com/Evarisk/digiquali/commit/4a8ad8d3)
* [#2854] [Question] fix: create the medium and large thumbs of question photos with saturne_vignette [`d6d4ed45`](https://github.com/Evarisk/digiquali/commit/d6d4ed45)
* [#2852] [Medias] fix: plus d'avertissement « Undefined array key digiquali_question » sans question liee [`c8c21768`](https://github.com/Evarisk/digiquali/commit/c8c21768)
* [#2850] [ControlDocument] fix: police des pictos générée dans documents, plus dans le code du cœur [`ade85cd0`](https://github.com/Evarisk/digiquali/commit/ade85cd0)
* [#2847] [Hook] fix: formObjectOptions sans objet Dolibarr, sur la page publique d'un ticket [`b9200fd6`](https://github.com/Evarisk/digiquali/commit/b9200fd6)
* [#2332] [Admin] feat: droit Modifier la configuration et message a la place du lien vers une configuration interdite [`9d11fde3`](https://github.com/Evarisk/digiquali/commit/9d11fde3)
