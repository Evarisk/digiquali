# [DigiQuali] [23.3.0] - Verrouillage en masse des contrôles - PDF des questionnaires - Statuts À réparer et NA des photos

Description : Les contrôles se verrouillent en une fois depuis leur liste, et un contrôle validé dit enfin ce qu'il reste à faire avant de pouvoir le verrouiller. Les questionnaires ont leur propre fiche PDF, les photos d'une question Photos multiples reçoivent les statuts **À réparer** et **NA**, et la liste des contrôles réunit tous les objets contrôlés dans une seule colonne qui se trie et se filtre.

**Cette version demande Saturne 23.8.0 ou supérieur.**

## Nouvelles fonctionnalités et innovations

### Verrouillage : les étapes restantes, puis le verrouillage en masse

* **Un contrôle validé explique où il en est.** Un encart rappelle que, tant qu'il n'est pas verrouillé, le contrôle peut être remis en brouillon, n'apparaît pas sur l'interface publique et sa date de prochain contrôle n'est pas calculée. Les étapes restantes sont cochées selon l'état réel du contrôle : verdict donné, participants signés, puis verrouillage.
* **Le bouton « Verrouiller » grisé dit pourquoi.** Son infobulle nomme les étapes qui manquent, signatures comprises, au lieu d'un message général.
* **Une confirmation claire.** Avant de verrouiller, DigiQuali rappelle que le verrouillage est définitif et qu'il publie le contrôle sur l'interface publique.
* **Verrouillage en masse.** Sur la liste des contrôles, cochez les contrôles puis choisissez l'action **« Verrouiller »** : ils sont verrouillés en une fois, avec les mêmes effets que depuis leur fiche. Ceux qui ne sont pas encore prêts (sans verdict, non signés, brouillons…) sont ignorés et signalés, sans interrompre le lot.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.3.0/verrouillage-etapes.png" alt="Fiche d'un contrôle validé : encart « Contrôle validé, pas encore verrouillé », verdict défini, signatures des participants à faire, puis verrouillage ; bouton Verrouiller grisé">

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.3.0/verrouillage-masse.png" alt="Liste des contrôles : quatre contrôles validés cochés et le menu des actions de masse ouvert sur Verrouiller">

### Questionnaires : une fiche PDF native

* Nouveau modèle **« Fiche du Questionnaire PDF avec photo »**, à activer dans Configuration → Documents. Il reprend la mise en page de la fiche de contrôle PDF.
* **Une synthèse en tête** : date, objets liés, projet, progression, score de réussite attendu, score obtenu et participants. Le résultat OK ou KO, avec son pourcentage, est calculé comme sur la fiche du questionnaire.
* **Les questions groupe par groupe**, avec leurs réponses, leurs photos, le tableau d'une question Photos multiples et les tâches.
* **En fin de document** : les signatures des participants, puis le plan d'action.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.3.0/questionnaire-pdf.png" alt="Fiche PDF d'un questionnaire : synthèse avec score de réussite 40 % et résultat OK à 43,75 %, tableau des photos d'une question Photos multiples avec les statuts OK et À réparer, réponses texte, pourcentage et OK/KO">

### Photos multiples : statuts « À réparer » et « NA »

* Chaque photo d'une question Photos multiples reçoit l'un des quatre statuts **OK, KO, À réparer ou NA**, avec les couleurs des réponses d'une question OK/KO/À réparer/NA.
* Les statuts suivent partout : fiche du contrôle, page publique, application mobile, résumé des listes, documents ODT et PDF, statistiques du modèle.
* Le résumé ne cite que les statuts donnés, par exemple « 3 photo(s) - 2 OK - 1 A réparer ».
* Sur téléphone, les quatre boutons se rangent en grille 2 × 2, à la hauteur de la photo.
* La fiche d'une réponse affiche le tableau des photos (miniature, commentaire, statut) au lieu d'un texte illisible.
* Rien à migrer : les réponses OK / KO déjà saisies restent telles quelles.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.3.0/photos-multiples-statuts.png" alt="Question Photos multiples d'un contrôle : quatre photos d'outils, chacune avec son commentaire et un statut différent, OK, KO, À réparer et NA">

### Liste des contrôles : une seule colonne « Objet contrôlé »

* Une colonne **« Objet contrôlé »** remplace les colonnes par type d'objet : elle affiche, avec leur pictogramme, tous les objets reliés au contrôle (lot, produit, tiers, utilisateur, projet, ticket…).
* **Elle se trie** : les contrôles sont regroupés par type d'objet, puis rangés par nom. Ceux qui ne sont reliés à rien restent en bas, dans les deux sens.
* **Elle se filtre** sur le nom, la référence ou le libellé de l'objet relié.
* Un contrôle relié à plusieurs objets n'apparaît plus qu'une fois dans la liste.
* Une sélection de colonnes enregistrée qui contenait une ancienne colonne par type affiche d'office la nouvelle.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.3.0/liste-objet-controle.png" alt="Liste des contrôles triée sur la colonne Objet contrôlé : un utilisateur, un tiers, des tickets et des projets, chacun avec son pictogramme">

### Le thème moderne habille aussi les écrans propres à DigiQuali

* Arrivé avec Saturne 23.7.0 sur les blocs communs, le thème moderne s'applique désormais aux composants de DigiQuali : verdict en pastille teintée (fiche, listes, widget), jours avant le prochain contrôle, barre de progression, bascule Questions / Statistiques.
* Les questions deviennent des cartes blanches dont le liseré suit l'état de la réponse, avec le type de question en pastille et les suggestions de commentaires en étiquettes.
* Le plan d'action (liste et Gantt), les statistiques, la fiche d'un modèle et son arbre de groupes et de questions, la galerie des médias suivent le même style. La fiche d'une réponse adopte aussi le thème partagé.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.3.0/theme-modele.png" alt="Fiche d'un modèle de contrôle au thème moderne : arbre des groupes de questions à gauche, informations en cartes, groupes de questions en liste">

## Améliorations & corrections

### Saisie des réponses

* **Les photos des réponses d'un questionnaire validé** disparaissaient de sa fiche et de ses documents : elles restaient rangées sous la référence provisoire. Un questionnaire reçoit désormais sa référence définitive dès sa création, comme un contrôle, et les photos des questionnaires et contrôles déjà validés sont remises à leur place à la mise à jour, à la première ouverture de l'accueil de DigiQuali.
* Plus d'avertissement PHP à la création d'un questionnaire.
* **Réponse Texte en pleine largeur.** Le champ était coincé à côté de l'énoncé, sur environ deux lignes : il passe sous la question, s'ouvre sur quatre lignes et grandit avec le texte tapé ou collé. Sur un contrôle verrouillé, la réponse s'affiche en entier. Fiche, questionnaire, page publique et application mobile en profitent.
* **Le commentaire d'une question grandit avec son texte**, y compris quand il est rempli depuis la bibliothèque de commentaires. Sur téléphone, il fait au moins trois lignes.
* **Questions Pourcentage** : une question enregistrée avec une seule étape faisait planter la fiche du contrôle. Elle s'affiche avec un curseur à deux positions, et le formulaire de la question exige désormais au moins deux étapes.
* **Photo du contrôle** : le bloc Photo de la fiche propose la bibliothèque de médias en plus de l'appareil, comme les réponses, et la photo rejoint la bibliothèque de DigiQuali.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.3.0/reponse-texte.png" alt="Question Texte d'un contrôle : la réponse de trois lignes occupe toute la largeur de la question">

### Modèles de contrôle

* **Retirer une question ou un groupe d'un modèle ne retire que ce lien-là.** Une question partagée par plusieurs modèles pouvait disparaître d'un autre modèle que celui de la page, et retirer un groupe à la racine le retirait de tous les modèles. Chaque retrait laisse maintenant un événement dans l'agenda.
* **Statistiques d'un modèle** : plus d'erreur fatale quand la page est ouverte sans modèle.
* **Configuration des modèles** : quand le lien vers un type d'objet est désactivé, les champs complémentaires encore utilisés par un modèle d'export enregistré sont conservés (Saturne 23.8.0). La page les nomme et explique comment les faire supprimer, au lieu d'annoncer « 0 supprimé ».

### Listes et fiches

* **« Supprimer les filtres »** sur les listes des contrôles, des modèles et des questions revient au filtre de statut par défaut. Avant, la liste affichait tout, archivés compris, puis le filtre revenait sans prévenir au premier tri.
* **La fiche d'une réponse** charge la feuille de style du module, affiche la réponse en entier (elle était coupée à 250 px) et traduit son titre, son onglet et ses champs de points.
* **Verdict** : plus d'avertissement PHP sur un contrôle sans verdict, et les libellés de statut ne sont plus écrasés.
* **Tableau de bord** : sans début d'exercice fiscal configuré, les graphiques par mois commençaient par décembre. Ils commencent en janvier.

### Documents

* L'aperçu du modèle de fiche de contrôle, fait sur un contrôle vide, ne lève plus d'avertissement PHP sur la section des moyens de contrôle.

### Sécurité

* **Jeton anti-CSRF** sur les liens qui en manquaient (modification des catégories, retrait d'un moyen de contrôle, suppression d'une question, d'un groupe ou d'un modèle, modification d'une réponse). Avec la protection CSRF renforcée de Dolibarr, ces liens tombaient en erreur 403.
* **Documents joints à une réponse** : les fichiers dangereux (HTML, SVG, scripts, exécutables) sont refusés avec un message, sur l'extension comme sur le contenu.

### Installation et compatibilité

* **Version minimale de Saturne.** DigiQuali refuse de s'activer sur un Saturne absent ou plus ancien que 23.8.0, avec un message qui nomme la version demandée, au lieu d'une erreur fatale.
* **Racine alternative et liens symboliques.** DigiQuali fonctionne installé ailleurs que dans `custom/` ou par lien symbolique : liens, onglets, pages de l'application mobile, inclusions de Saturne.
* **Activation plus légère** : elle ne génère plus, pour chaque contrôle, un QR code vers une ancienne page publique qui n'existe plus.
* Plus d'avertissement PHP sur la fiche d'un élément, ni à chaque affichage du temps consommé d'un projet.

## Testez les nouveautés maintenant

**Allez tester le verrouillage en masse des contrôles sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.2.2](https://github.com/Evarisk/digiquali/compare/23.2.2...23.3.0) et 23.3.0

* [#2761] [Survey] fix: référence définitive dès la création, les photos des réponses suivent la référence [`5e12d9e1`](https://github.com/Evarisk/digiquali/commit/5e12d9e1)
* [#2763] [Survey] fix: plus de warning createfromclone à la création d'un questionnaire [`97ebeca2`](https://github.com/Evarisk/digiquali/commit/97ebeca2)
* [#2759] [Sheet] fix: tell the admin which extrafields stay for a saved export model [`ed665bf3`](https://github.com/Evarisk/digiquali/commit/ed665bf3)
* [#2757] [Media] fix: restriction et validation des types de fichiers sur l'upload de documents [`a0f0c218`](https://github.com/Evarisk/digiquali/commit/a0f0c218)
* [#2755] [Question] fix: comment field grows with its text, three lines on phone [`4b3a2b27`](https://github.com/Evarisk/digiquali/commit/4b3a2b27)
* [#2753] [CI] fix: phpstan baseline messages for the clamped step type [`b35ab567`](https://github.com/Evarisk/digiquali/commit/b35ab567)
* [#2753] [Question] fix: percentage slider needs at least two steps [`18e54bb4`](https://github.com/Evarisk/digiquali/commit/18e54bb4)
* [#2751] [Control] feat: single sortable controlled object column on control list [`bf132502`](https://github.com/Evarisk/digiquali/commit/bf132502)
* [#2747] [Dashboard] fix: default fiscal month start to January in the by-month graphs [`215f73b5`](https://github.com/Evarisk/digiquali/commit/215f73b5)
* [Medias] fix: bloc Photo du contrôle relié à la bibliothèque, actions photo de Saturne [`08704643`](https://github.com/Evarisk/digiquali/commit/08704643)
* [#2719] [CI] ci: retrait du workflow Generate Auto Changelog sur main [`e510b894`](https://github.com/Evarisk/digiquali/commit/e510b894)
* [#2738] [ControlDocument] fix: section équipements sautée quand la lecture échoue [`c98736b4`](https://github.com/Evarisk/digiquali/commit/c98736b4)
* [#2737] [Sheet] fix: plus de fatale count() sur les statistiques sans trame [`8de231a3`](https://github.com/Evarisk/digiquali/commit/8de231a3)
* [#2735] [Control] fix: plus de QR code vers public_control.php à l'activation [`553db04d`](https://github.com/Evarisk/digiquali/commit/553db04d)
* [#2735] [Module] fix: chemins relatifs confiés au cœur sans custom/ [`6dc289a2`](https://github.com/Evarisk/digiquali/commit/6dc289a2)
* [#2735] [Module] fix: dol_buildpath('/custom/…') sans custom/ [`92bd3201`](https://github.com/Evarisk/digiquali/commit/92bd3201)
* [#2735] [Module] fix: URL construites avec DOL_URL_ROOT . '/custom/…' [`a1561089`](https://github.com/Evarisk/digiquali/commit/a1561089)
* [#2731] [Public] fix: variables globales déclarées avant la validation, fetchObjectLinked() avec null [`fe814abe`](https://github.com/Evarisk/digiquali/commit/fe814abe)
* [#2731] [Public] fix: valider object_type et échapper track_id sur les pages publiques de réponse et d'enquête [`b180ecac`](https://github.com/Evarisk/digiquali/commit/b180ecac)
* [#2729] [Control] fix: libellés propres au verdict, plus de warning ni de statut écrasé [`8a889cb2`](https://github.com/Evarisk/digiquali/commit/8a889cb2)
* [#2727] [List] fix: la suppression des filtres revient au statut par défaut [`5f290f93`](https://github.com/Evarisk/digiquali/commit/5f290f93)
* [#2725] [Control] feat: étapes avant le verrouillage et verrouillage en masse [`c303e7b7`](https://github.com/Evarisk/digiquali/commit/c303e7b7)
* [#2723] [CI] ci: actions GitHub en Node 24 (checkout@v7, cache@v6) [`20cb0a90`](https://github.com/Evarisk/digiquali/commit/20cb0a90)
* [#2721] [CI] ci: contrôle « API Saturne publiée » dans la CI qualité [`a2810e3b`](https://github.com/Evarisk/digiquali/commit/a2810e3b)
* [#2719] [CI] ci: retrait du workflow Generate Auto Changelog [`57fb66ca`](https://github.com/Evarisk/digiquali/commit/57fb66ca)
* [#2717] [Mod] feat: version minimale de Saturne, activation refusee sur un Saturne trop ancien [`fe32c1fd`](https://github.com/Evarisk/digiquali/commit/fe32c1fd)
* [#2715] [Registre] fix: chemins du registre publies tels qu'installes, lisibles par Saturne < 23.3.0 [`3b66c01f`](https://github.com/Evarisk/digiquali/commit/3b66c01f)
* [#2713] [QRCode] fix: warning Undefined array key massaction sur le temps consomme d'un projet [`86a725fa`](https://github.com/Evarisk/digiquali/commit/86a725fa)
* [#2711] [SurveyDocument] add: PDF natif pour les questionnaires [`55258fa9`](https://github.com/Evarisk/digiquali/commit/55258fa9)
* [#2659] [CSRF] fix: ajouter le token au crayon d'edition d'une reponse [`37ab5ef0`](https://github.com/Evarisk/digiquali/commit/37ab5ef0)
* [#2650] [ControlDet] fix: fiche réponse avec le CSS du module et la réponse affichée en entier [`1f9f9ef1`](https://github.com/Evarisk/digiquali/commit/1f9f9ef1)
* [#2650] [ControlDet] fix: traduire le titre, l'onglet et les champs de points de la fiche réponse [`8cf0bbd6`](https://github.com/Evarisk/digiquali/commit/8cf0bbd6)
* [#2650] [Question] fix: couleurs des statuts et réponse lisible sur la fiche réponse [`d599c5ac`](https://github.com/Evarisk/digiquali/commit/d599c5ac)
* [#2650] [Question] add: statuts A réparer et NA sur les photos d'une question Photos multiples [`ea194cff`](https://github.com/Evarisk/digiquali/commit/ea194cff)
* [#2707] [Element] fix: plus de HTML envoye avant les en-tetes par la fiche element [`77fc1785`](https://github.com/Evarisk/digiquali/commit/77fc1785)
* [#2705] [CI] ci: refuser les inclusions relatives de Saturne [`d2efb285`](https://github.com/Evarisk/digiquali/commit/d2efb285)
* [#2680] [Header] feat: thème partagé sur la fiche des réponses de contrôle [`53cc55cc`](https://github.com/Evarisk/digiquali/commit/53cc55cc)
* [#2680] [SCSS] feat: thème moderne des écrans DigiQuali [`10f54cfa`](https://github.com/Evarisk/digiquali/commit/10f54cfa)
* [#2659] [CSRF] fix: ajouter le token CSRF aux liens et appels AJAX [`bb956217`](https://github.com/Evarisk/digiquali/commit/bb956217)
* [#2657] [Saturne] fix: inclure Saturne par dol_include_once / dol_buildpath [`c23321a5`](https://github.com/Evarisk/digiquali/commit/c23321a5)
* [#2112] [Control] fix: text answer field full width, growing with its text [`7aabc410`](https://github.com/Evarisk/digiquali/commit/7aabc410)
* [#2321] [CI] chore: retirer de la baseline phpstan les erreurs corrigées [`444fe5c5`](https://github.com/Evarisk/digiquali/commit/444fe5c5)
* [#2321] [Sheet] fix: événement agenda et retrait du seul lien cliqué sur une question ou un groupe [`68b08b0b`](https://github.com/Evarisk/digiquali/commit/68b08b0b)
