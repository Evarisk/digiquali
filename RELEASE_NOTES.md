# [DigiQuali] [23.1.0] - Plan d'action sur les contrôles - Interface publique enrichie - Questions Durée et commentaires prédéfinis

Description : Cette version ouvre le **plan d'action** sur les contrôles, avec son onglet, sa vue Gantt et son accès depuis l'interface publique, où les actions correctives peuvent désormais être saisies. Elle ajoute un **type de question Durée**, une **bibliothèque de commentaires prédéfinis**, la liste des contrôles de lots et séries sur la fiche produit, et une première passe **mobile** sur les listes et la saisie des réponses. Les trames gagnent l'affectation multiple de questions et la configuration des options de création de contrôle.

**Cette version demande Saturne 23.2.0 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Plan d'action

* Nouvel **onglet plan d'action** sur un contrôle, avec **vue Gantt** et accès depuis l'interface publique.
* Les **actions correctives** se saisissent directement depuis l'interface publique.

### Questions

* Nouveau type de question **Durée**, en heures, minutes et secondes.
* **Bibliothèque de commentaires prédéfinis**, pour éviter de ressaisir les mêmes remarques.
* Des **documents s'attachent à la réponse** d'une question.

### Trames

* **Affectation multiple** des questions, en AJAX.
* Liste déroulante select2 optionnelle pour les objets contrôlés.
* Options de création de contrôle configurables depuis la trame — affichage du projet, des étiquettes, valeurs par défaut — avec un formulaire réorganisé.
* Bouton de **désarchivage** sur la fiche, comme sur les contrôles.

### Contrôles

* La fiche produit affiche la **liste des contrôles de ses lots et séries**.
* Première passe **mobile** sur les listes et sur la zone de réponse, avec une option pour désactiver les fichiers joints.
* Bloc média du socle intégré à la fiche et aux questions.

### Interface publique

* Les informations de l'objet contrôlé apparaissent dans l'en-tête de la réponse publique.
* Hooks d'extension sur l'onglet documentation et sur l'identité de l'objet lié, état vide géré.

### Administration

* Gestion des **éléments liables** depuis la page de configuration d'une trame ; onglets et hooks déclarés uniquement pour les liaisons activées.
* Les liens ne sont plus créés à l'activation du module : ils sont repris et synchronisés.

### Éléments et outils

* Les onglets « Activités » et « Processus » fusionnent en une seule page.
* Console de diagnostic d'import, avec un rapport d'erreur exploitable.

## Améliorations & corrections

* **Dolibarr 24 : la génération de documents est réparée** — le cœur y refuse les modèles livrés avec le module et ne transmet plus ses paramètres au générateur.
* Appels aux bibliothèques du socle sécurisés (`dol_include_once` et vérification d'existence) : un socle absent ou plus ancien ne provoque plus de fatale.
* Calcul proportionnel des points d'une question en pourcentage.
* Propriété typée lue avant initialisation (#2515).
* Style et mise en page du PDF de contrôle revus.
* Une trentaine de correctifs sur les contrôles, les trames, les listes, les onglets et les médias.

## Comparaison des versions [23.0.0](https://github.com/Evarisk/digiquali/compare/23.0.0...23.1.0) et 23.1.0
