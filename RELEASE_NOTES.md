# [DigiQuali] [23.4.3] - Création sans avertissement en multi-société

Description : les formulaires de création d'un contrôle, d'un questionnaire ou d'une ligne de contrôle n'affichent plus d'avertissement dans les sociétés autres que la principale, et l'absence de Saturne est signalée clairement.

**Cette version demande Saturne 23.10.1 ou supérieur.**

## Améliorations & corrections

### Multi-société

* Créer un contrôle, un questionnaire ou une ligne de contrôle dans une autre société que la principale n'affiche plus l'avertissement « Undefined array key 1 » : leurs documents vont dans le dossier de la société courante.

### Installation

* Quand Saturne est introuvable, DigiQuali l'indique par un message explicite au lieu d'une page d'erreur.

## Testez les nouveautés maintenant

**Allez tester la création d'un contrôle sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.4.2](https://github.com/Evarisk/digiquali/compare/23.4.2...23.4.3) et 23.4.3

* [#2842] [Multi-entité] fix: test isset() gardé pour les propriétés entity typées int (PHPStan) [`345138d5`](https://github.com/Evarisk/digiquali/commit/345138d5)
* [#2842] [Multi-entité] fix: dossier des documents lu dans l'entité courante quand l'objet n'est pas chargé [`23172314`](https://github.com/Evarisk/digiquali/commit/23172314)
* [#2840] [Saturne] fix: message explicite quand l'amorçage ne trouve pas Saturne [`e6fb8d68`](https://github.com/Evarisk/digiquali/commit/e6fb8d68)
