# [DigiQuali] [23.4.0] - Contrôles obligatoires ou informatifs - Période de contrôle sur le modèle - Bilan des Photos multiples

Description : Chaque modèle de contrôle porte désormais sa période (en jours, mois ou années) et la nature de ses contrôles. Obligatoires, ils décident de l'état de l'objet contrôlé ; informatifs, ils ne le changent plus et n'encombrent plus la page publique. Les questions Photos multiples affichent leur bilan et expliquent comment elles se remplissent, et une prochaine date de contrôle saisie avant la date du contrôle est désormais signalée.

**Cette version demande Saturne 23.9.1 ou supérieur.**

## 🎬 La nouveauté en vidéo

<a href="https://github.com/Evarisk/digiquali/releases/download/23.4.0/digiquali-23.4.0-controles-obligatoires.mp4"><img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/video_poster_23.4.0.jpeg" alt="Contrôles obligatoires ou informatifs : la période et la nature réglées sur le modèle, les contrôles déjà faits mis à jour, la page publique d'un lot, en vidéo (24 s)"></a>

*Cliquez sur l'image pour voir la vidéo (24 s).*

## Nouvelles fonctionnalités et innovations

### Période et nature des contrôles, réglées sur le modèle

* **Période de contrôle** : un nombre et une unité (jours, mois ou années) sur la fiche du modèle. Au verrouillage d'un contrôle, la prochaine date de contrôle en est tirée, à partir de la date du contrôle. « 2 années » tombe le même jour deux ans plus tard, même avec un 29 février en route.
* Elle passe avant la périodicité de l'objet contrôlé. Laissée vide, c'est celle de l'objet qui s'applique, comme avant.
* **Nature des contrôles** : **Obligatoire** ou **Informatif**, donnée à chaque nouveau contrôle fait avec le modèle, y compris depuis l'application mobile et le carnet de bord de DoliCar.
* Les deux réglages se modifient directement sur un modèle verrouillé, déjà en service (pas sur un modèle archivé). Le clone, l'export et l'import d'un modèle les reprennent ; un ancien export s'importe toujours.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/modele-periode-nature.png" alt="Fiche d'un modèle de contrôle, options de création du contrôle : Période de contrôle 1 année et Nature des contrôles Obligatoire, chacune avec son aide et son crayon de modification">

### Les contrôles déjà réalisés suivent, si vous le décidez

* Après un changement de nature ou de période, la fiche du modèle compte les contrôles déjà faits qui ne suivent pas le nouveau réglage et demande **« Les mettre à jour ? »**.
* **Oui** : la nature passe sur tous les contrôles du modèle, et la prochaine date des contrôles verrouillés et conformes est recalculée depuis leur date de contrôle.
* **Non** : seuls les prochains contrôles suivent le réglage. Une échéance saisie à la main n'est jamais écrasée sans votre accord.
* Les rappels déjà créés dans l'agenda ne sont pas déplacés.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/controles-deja-faits.png" alt="Fenêtre Contrôles déjà réalisés : 2 contrôles faits avec ce modèle ne suivent pas le réglage, nature Obligatoire pour 2 contrôles, prochaine date recalculée avec la période de 1 année pour 2 contrôles verrouillés, boutons Oui et Non">

### Une nature sur chaque contrôle

* À la création, la **Nature du contrôle** est préremplie avec celle du modèle choisi. Elle reste modifiable sur la fiche jusqu'au verrouillage, comme les dates.
* La liste des contrôles gagne une colonne **Nature du contrôle**, filtrable.
* La prochaine date annoncée par la confirmation de verrouillage, puis enregistrée, tient compte de la période du modèle.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/controle-nature.png" alt="Formulaire Nouveau contrôle : modèle Vérification annuelle de la trousse de secours, contrôleur, Nature du contrôle préremplie sur Obligatoire, choix du lot">

### Une prochaine date saisie avant la date du contrôle est signalée

* Une prochaine date de contrôle saisie à la main avant la date du contrôle (une erreur d'année, par exemple) est signalée sur la fiche : une pastille **« Antérieure à la date du contrôle »** à côté de la date, avec le détail en infobulle.
* Le même avertissement s'affiche à l'enregistrement de l'une des deux dates, puis dans la confirmation de verrouillage. C'est la dernière occasion de corriger : un contrôle verrouillé ne se modifie plus, et l'objet contrôlé paraîtrait en retard (le statut d'un véhicule dans DoliCar, par exemple).
* Rien n'est bloqué : la date reste celle saisie. La pastille reste visible sur un contrôle verrouillé, pour expliquer un retard affiché.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/prochaine-date-anterieure.png" alt="Fiche d'un contrôle daté du 10/11/2026 : prochaine date de contrôle 10/11/2025 suivie de la pastille Antérieure à la date du contrôle, et l'infobulle « La prochaine date de contrôle (10/11/2025) est antérieure à la date du contrôle (10/11/2026) : vérifiez la saisie. »">

### L'état d'un objet vient de ses contrôles obligatoires

* Dès qu'un objet a des contrôles obligatoires, son état (conforme, non conforme, en retard) vient du **dernier contrôle obligatoire de chaque modèle**. Une ronde informative, même plus récente, ne le change plus. Sans contrôle obligatoire, c'est le dernier contrôle qui compte, comme avant.
* Le pire état l'emporte, puis l'échéance la plus proche. Un contrôle fait avec un modèle archivé ne met jamais l'objet en retard.
* **Page publique de suivi** : l'état cite le « Contrôle obligatoire du … » et la période de contrôle du modèle ; la liste des contrôles ne garde que les contrôles obligatoires, marqués « Obligatoire ».
* **À la mise à jour**, une seule fois : vos contrôles et modèles existants passent en « Obligatoire ». Ils décidaient tous de l'état de leurs objets : rien ne change pour vous. Un nouveau modèle est « Informatif » par défaut : choisissez « Obligatoire » pour un contrôle réglementaire.

<img width="32%" src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/suivi-public-etat.png" alt="Page publique de suivi d'un lot sur téléphone : Conforme, contrôle obligatoire du 05/03/26, prochain contrôle le 05/03/27 dans 102 jours, période de contrôle 1 année"> <img width="32%" src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/suivi-public-historique.png" alt="Liste des contrôles de la page publique sur téléphone : le contrôle obligatoire FC2611-0056, conforme, modèle Vérification annuelle de la trousse de secours marqué Obligatoire ; la ronde mensuelle informative, plus récente, n'y figure pas">

### Widget « Prochains contrôles à réaliser »

* Seul le dernier contrôle de chaque modèle sur chaque objet est listé : un contrôle refait ne réapparaît plus. Les modèles archivés sont écartés.
* Un contrôle relié aussi à un objet d'un type désactivé n'est plus sauté : le widget affiche bien ses dix lignes.

### Photos multiples : le bilan de la question, et une aide pour bien la remplir

* En tête du tableau des photos, le **Bilan de la question** : « 4 photo(s) - 1 OK - 1 KO - 1 A réparer - 1 NA ». Il se met à jour à chaque statut donné, sur le contrôle, le questionnaire, la page publique et l'application mobile.
* Tant que la saisie est ouverte, une ligne rappelle qu'un statut par photo est facultatif et que la question n'a pas de statut global : elle ne compte pas dans les points, ni dans le verdict d'un contrôle.
* Sur la fiche d'une question, une aide apparaît sous le type **Photos multiples** dès qu'on le choisit.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/photos-multiples-bilan.png" alt="Question Photos multiples d'un contrôle : Bilan de la question 4 photos, 1 OK, 1 KO, 1 A réparer, 1 NA, ligne d'aide, puis quatre photos d'outils avec leur commentaire et leur statut">

<img width="32%" src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/photos-multiples-telephone.png" alt="Même question sur la page publique de réponse, sur téléphone : bilan, ligne d'aide, et pour chaque photo les boutons OK, KO, A réparer et NA en grille, A réparer écrit en entier">

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.4.0/question-type-aide.png" alt="Fiche d'une nouvelle question : type Photos multiples choisi, l'aide dessous explique une ligne par photo avec commentaire et statut, sans statut global ni points ; nombre de points à 0">

## Améliorations & corrections

### Pages publiques

* **Liste des contrôles** : le contrôle qui fait l'état de l'objet affiche « Échéance dépassée » une fois sa prochaine date atteinte. Avant, un contrôle conforme mais échu restait affiché « Conforme ».
* **« A réparer » ne se coupe plus au milieu du mot** sur téléphone, sur les boutons de statut d'une photo : l'icône passe au-dessus du libellé et, sur un écran étroit, c'est la photo qui cède de la place.
* **Suivi d'un lot** : une longue référence de produit ne fait plus déborder toute la page sur téléphone.
* **Logo de la société** : il ne s'affiche plus deux fois sur le suivi des contrôles et sur le plan d'action.

### Documents et fichiers

* **Documents générés en brouillon** : ceux d'anciens contrôles et questionnaires, restés sous leur référence provisoire (PROV…), disparaissaient de la fiche à la validation. Ils sont remis sous la référence définitive à la mise à jour, sans rien écraser.
* **Activation depuis Accueil > Modules** : les fichiers des modèles et autres objets validés restés sous (PROV…) sont désormais repris là aussi, et plus seulement depuis l'accueil de DigiQuali (avec Saturne 23.9.1).
* **Documents ODT** du contrôle et du questionnaire : plus d'avertissement PHP pour une question à choix restée sans réponse.

### Modèles et tableau de bord

* **Fiche d'un modèle** : le message « Il n'est pas possible de déplacer un élément en dehors de son parent » ne s'affiche plus en permanence sous les champs ; il n'apparaît qu'en cas de déplacement refusé.
* **Widget « Prochains contrôles à réaliser »** : la colonne s'intitule « Objet contrôlé » au lieu d'afficher une clé de traduction brute.

### Compatibilité et stabilité

* **Plus d'erreur fatale** quand un autre module annule un formulaire alors que DigiQuali est actif (par exemple la fiche d'une enveloppe de DoliLetter).
* **Racine alternative** : DigiQuali trouve EasyURL et DoliCar même quand ils sont installés dans une autre racine que la sienne.
* **Choix du modèle** : la liste des modèles n'appelle plus une fonction héritée de Digirisk, absente de DigiQuali, qui pouvait finir en erreur fatale sous PHP 8.
* **Icône de DigiQuali** rétablie dans la liste des déclencheurs (Configuration > Modules > Déclencheurs).
* **Pages de configuration** : plus d'avertissements PHP dans le journal à chaque affichage pour un utilisateur qui n'a pas le droit de lire la configuration de DigiQuali. L'accès reste refusé sans ce droit, et accordé avec.

## Testez les nouveautés maintenant

**Allez tester les contrôles obligatoires et la période de contrôle sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.3.0](https://github.com/Evarisk/digiquali/compare/23.3.0...23.4.0) et 23.4.0

* [#2802] [Admin] fix: droit de lecture de la configuration lu par hasRight(), sans avertissement pour un utilisateur qui ne l'a pas [`02261f32`](https://github.com/Evarisk/digiquali/commit/02261f32)
* [#2798] [Mod] fix: fichiers des objets validés restés sous (PROVn) repris à l'activation depuis Accueil > Modules [`20a74834`](https://github.com/Evarisk/digiquali/commit/20a74834)
* [#2799] [Trigger] fix: picto digiquali_color@digiquali, l'image digiquali@digiquali n'existe pas [`3161185d`](https://github.com/Evarisk/digiquali/commit/3161185d)
* [#2796] [Hook] fix: doActions accepte $action nul [`115c45de`](https://github.com/Evarisk/digiquali/commit/115c45de)
* [#2791] [Langs] fix: informative controls hidden from the public page [`919814c0`](https://github.com/Evarisk/digiquali/commit/919814c0)
* [#2791] [Public] fix: only the mandatory controls on the public page, controls made before the nature are mandatory [`52a2d2f6`](https://github.com/Evarisk/digiquali/commit/52a2d2f6)
* [#2793] [Langs] add: PhotoMultiple question summary and help [`c4f3d8d8`](https://github.com/Evarisk/digiquali/commit/c4f3d8d8)
* [#2793] [Question] feat: help of the PhotoMultiple type under the question type selector [`e9d68970`](https://github.com/Evarisk/digiquali/commit/e9d68970)
* [#2793] [Answer] feat: question summary and help line on a PhotoMultiple question [`6e5cde43`](https://github.com/Evarisk/digiquali/commit/6e5cde43)
* [#2789] [Langs] add: control period, control nature and controls already made [`9c667ea7`](https://github.com/Evarisk/digiquali/commit/9c667ea7)
* [#2789] [Control] feat: mandatory or informative control, object status from the last mandatory control, next controls widget [`5e05e584`](https://github.com/Evarisk/digiquali/commit/5e05e584)
* [#2789] [Sheet] feat: control period in days, months or years, default nature of the controls, align the controls already made [`984145d3`](https://github.com/Evarisk/digiquali/commit/984145d3)
* [#2789] [DB] add: control period and default nature on the sheet, nature on the control [`f1446265`](https://github.com/Evarisk/digiquali/commit/f1446265)
* [#2778] [ODT] fix: plus de warning pour une question sans réponse dans les documents ODT [`debffb7c`](https://github.com/Evarisk/digiquali/commit/debffb7c)
* [#2776] [Public] fix: logo de la société affiché une seule fois sur les pages publiques au design PWA [`52152291`](https://github.com/Evarisk/digiquali/commit/52152291)
* [#2775] [Public] fix: vue État du suivi d'un lot sans débordement avec une longue référence produit [`11f86af6`](https://github.com/Evarisk/digiquali/commit/11f86af6)
* [#2774] [Public] fix: « A réparer » ne se coupe plus au milieu du mot sur téléphone [`abb75578`](https://github.com/Evarisk/digiquali/commit/abb75578)
* [#2772] [Media] fix: balise \file du docblock de digiquali_media_block_actions.tpl.php [`a4c3eb25`](https://github.com/Evarisk/digiquali/commit/a4c3eb25)
* [#2773] [Survey] fix: les documents générés en brouillon suivent la référence définitive [`28b08851`](https://github.com/Evarisk/digiquali/commit/28b08851)
* [#2771] [Sheet] fix: selectSheetList() n'appelle plus fetchDigiriskElementFlat() [`2dbb5aa3`](https://github.com/Evarisk/digiquali/commit/2dbb5aa3)
* [#2770] [Module] fix: classes d'EasyURL et de DoliCar cherchées dans toutes les racines [`d6898274`](https://github.com/Evarisk/digiquali/commit/d6898274)
* [#2769] [Dashboard] fix: en-tête « Objet contrôlé » dans le widget Prochains contrôles [`b59e1554`](https://github.com/Evarisk/digiquali/commit/b59e1554)
* [#2768] [Sheet] fix: message de déplacement hors du parent masqué jusqu'au déplacement refusé [`b3ab961d`](https://github.com/Evarisk/digiquali/commit/b3ab961d)
* [#2743] [Control] feat: avertir quand la prochaine date de controle precede la date du controle [`42e88b6f`](https://github.com/Evarisk/digiquali/commit/42e88b6f)
