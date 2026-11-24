# [DigiQuali] [23.4.1] - Page publique de réponse plus sûre - API REST réparée - DigiQuali en anglais

Description : la page publique de réponse contrôle mieux les liens qu'elle reçoit, l'API REST accepte de nouveau les créations et les modifications, et DigiQuali est désormais entièrement traduit en anglais. Le reste de la version corrige les contrôles, les modèles et les questions. **Mise à jour recommandée pour toutes les instances.**

**Cette version demande Saturne 23.9.1 ou supérieur.**

## Améliorations & corrections

### Page publique de réponse : contrôles renforcés

* Un lien de réponse inconnu ou incomplet affiche maintenant une page introuvable, avant toute lecture ou tout enregistrement.
* Les liens envoyés aux répondants fonctionnent comme avant.

### API REST

* Les créations et les modifications par l'API répondaient « 400 Invalid value specified for request_data » : elles acceptent de nouveau leurs données.
* Créer un contrôle par l'API répondait 403 à tout le monde, administrateur compris. Il demande maintenant le droit de créer ou modifier un contrôle, comme à l'écran.

### Contrôles

* Un contrôle sans libellé se crée normalement, par exemple depuis l'API. Il provoquait une erreur fatale.
* La confirmation du verrouillage compte l'échéance depuis aujourd'hui. Une prochaine date de contrôle déjà passée y est annoncée « Dépassée depuis N jours », avec les mêmes libellés que la page publique (Dans N jours, Demain, Aujourd'hui).

### Modèles et questions

* Les questions et les groupes ajoutés à un modèle reçoivent leur position aussi sous MySQL 8 : ils restaient sans position.
* Une question Photos multiples ne compte plus de points. Le champ est mis à 0 et désactivé : ces points, impossibles à gagner, faisaient baisser la note du questionnaire.
* L'enregistrement d'une fiche question ne laisse plus d'avertissement dans le journal PHP.

### Traductions

* DigiQuali est entièrement traduit en anglais : 703 libellés au lieu de 209. Un utilisateur en anglais ne voit plus de clés brutes dans les réglages, les listes, les fiches et l'application mobile.
* L'icône de création de projet de la page Correspondances affiche de nouveau « Créer projet » au lieu d'une phrase propre à DigiQuali.

### Cohabitation avec les autres modules

* Un module qui déclenche un événement sur un objet qui n'est pas un objet Dolibarr (ExternalAccess, par exemple) ne laisse plus d'avertissements dans le journal PHP.

## Testez les nouveautés maintenant

**Allez tester les contrôles et leur page publique sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.4.0](https://github.com/Evarisk/digiquali/compare/23.4.0...23.4.1) et 23.4.1

* [#2830] [Public] fix: un lien inconnu répond 404 avant toute action sur la page publique de réponse [`1edcc01d`](https://github.com/Evarisk/digiquali/commit/1edcc01d)
* [#2826] [API] fix: créer un contrôle par l'API demande le droit control->write, comme à l'écran [`c1e6d9a1`](https://github.com/Evarisk/digiquali/commit/c1e6d9a1)
* [#2825] [API] fix: les créations et modifications acceptent leur request_data (annotation lisible par Restler) [`5e16fbab`](https://github.com/Evarisk/digiquali/commit/5e16fbab)
* [#2823] [Control] fix: un contrôle se crée sans libellé, sans erreur fatale [`4b8e7c0f`](https://github.com/Evarisk/digiquali/commit/4b8e7c0f)
* [#2822] [Sheet] fix: position des questions et groupes ajoutés à un modèle écrite aussi sur MySQL 8 [`fec13125`](https://github.com/Evarisk/digiquali/commit/fec13125)
* [#2818] [Question] fix: plus d'avertissement « Undefined variable $noback » à l'enregistrement d'une fiche question [`1ad5c35c`](https://github.com/Evarisk/digiquali/commit/1ad5c35c)
* [#2819] [JS] docs: versions réelles de addCommentFromLibrary (23.1.0) et fitTextAnswer (23.3.0) dans object.js [`c3431059`](https://github.com/Evarisk/digiquali/commit/c3431059)
* [#2811] [Control] fix: échéance comptée depuis aujourd'hui dans la confirmation du verrouillage, retard annoncé pour une date passée [`51ff480a`](https://github.com/Evarisk/digiquali/commit/51ff480a)
* [#2812] [Question] fix: nombre de points figé à 0 sur une question Photos multiples [`d6c1299f`](https://github.com/Evarisk/digiquali/commit/d6c1299f)
* [#2810] [Trigger] fix: plus d'avertissement sur un événement sans « _ » ou dont l'objet n'est pas un CommonObject [`c65cf1b8`](https://github.com/Evarisk/digiquali/commit/c65cf1b8)
* [#2813] [JS] docs: @since et @version 23.4.0 sur les fonctions arrivées ou modifiées en 23.4.0 [`62b78a5f`](https://github.com/Evarisk/digiquali/commit/62b78a5f)
* [#2808] [Langs] fix: AddProject n'est plus redefinie, le sens propre a DigiQuali passe sous ProjectNeededForTasks [`4865b1cd`](https://github.com/Evarisk/digiquali/commit/4865b1cd)
* [#2745] [Langs] fix: traduction en_US complete (703 cles) [`a39c756c`](https://github.com/Evarisk/digiquali/commit/a39c756c)
