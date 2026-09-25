# [DigiQuali] [23.1.1] - Dolibarr 24 - Conformité Dolistore - Chaîne qualité

Description : Cette version corrige les deux motifs pour lesquels le contrôle de paquet du **Dolistore** refusait le zip, déclare le module compatible **Dolibarr 24**, et met en place une **chaîne de contrôles qualité** sur les pull requests — analyse statique, lint PHP et parité des fichiers de langue.

**Cette version demande Saturne 23.2.1 ou supérieur.**

## Améliorations & corrections

### Conformité du paquet Dolistore

* Le contrôle de paquet refusait le zip : `manifest.json.php` chargeait l'environnement Dolibarr par un `require` unique, alors que la règle demande **au moins deux tentatives** — une pour le module à la racine de Dolibarr, une pour le module dans `custom`. C'était le seul fichier du module dans ce cas, les 25 autres points d'entrée étaient déjà conformes.
* Second motif : la classe d'API incluait ses neuf classes — une de Saturne, huit de DigiQuali — par un chemin `/custom` en dur, qui ne résout pas si les modules sont installés à la racine de Dolibarr. Elles passent par `dol_include_once`, qui cherche dans les deux racines de documents.

### Compatibilité

* Le module déclare **Dolibarr 23 au minimum et 24 au maximum**.

### Intégration continue

* Les pull requests passent désormais **PHPStan**, un **lint PHP** et un contrôle de **parité des fichiers de langue** français / anglais.
* PHPStan ne scanne plus les classes bouchons des tests de Saturne, qui masquaient les vraies signatures en CI et y laissaient passer des erreurs invisibles en local — 94 erreurs démasquées, absorbées dans la baseline.
* La baseline figeait le numéro de version du module dans un message d'erreur : la première release aurait cassé la chaîne qualité. Le motif est désormais ignoré indépendamment du numéro.
* `phpstan.neon` est aligné sur le gabarit commun aux modules du socle.

## Comparaison des versions [23.1.0](https://github.com/Evarisk/digiquali/compare/23.1.0...23.1.1) et 23.1.1

* [#2621] [CI] rework: aligner phpstan.neon sur le gabarit commun [`3fc9e618`](https://github.com/Evarisk/digiquali/commit/3fc9e618)
* [#2619] [CI] fix: ignorer par motif la version des triggers, figée dans la baseline [`7d8f2c80`](https://github.com/Evarisk/digiquali/commit/7d8f2c80)
* [#2617] [CI] fix: PHPStan ne scanne plus les stubs de test de Saturne [`1bf7f17e`](https://github.com/Evarisk/digiquali/commit/1bf7f17e)
* [#2615] [API] fix: inclure les classes du module par dol_include_once [`1b11d636`](https://github.com/Evarisk/digiquali/commit/1b11d636)
* [#2613] [Module] fix: bootstrap main.inc.php à deux tentatives, exigé par le Dolistore [`3607054e`](https://github.com/Evarisk/digiquali/commit/3607054e)
* [CI] fix: compléter les dossiers du coeur vus par PHPStan [`7cbc25e8`](https://github.com/Evarisk/digiquali/commit/7cbc25e8)
* [#2611] [CI] feat: PHPStan, lint PHP et parité des langues [`3aa986a8`](https://github.com/Evarisk/digiquali/commit/3aa986a8)
* [#2609] [Module] rework: bornes de version Dolibarr 23 minimum, 24 maximum [`d7cf9527`](https://github.com/Evarisk/digiquali/commit/d7cf9527)
