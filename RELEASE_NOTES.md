# [DigiQuali] [23.4.2] - Pages publiques plus sûres

Description : les pages publiques de DigiQuali contrôlent l'entité de leur lien, et les formulaires de création d'un contrôle ou d'un questionnaire contrôlent le produit et la catégorie choisis. **Mise à jour recommandée pour toutes les instances.**

**Cette version demande Saturne 23.10.0 ou supérieur.**

## Améliorations & corrections

### Pages publiques : contrôles renforcés

* L'entité du lien est contrôlée avant toute lecture sur les pages publiques de réponse, de questionnaire, d'historique et de plan d'action d'un contrôle.
* Un lien de plan d'action sans entité s'ouvre dans l'entité courante au lieu d'afficher une erreur.

### Création d'un contrôle ou d'un questionnaire

* Le produit parent et la catégorie de modèle choisis dans le formulaire de création sont contrôlés.

## Testez les nouveautés maintenant

**Allez tester un contrôle et sa page publique sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.4.1](https://github.com/Evarisk/digiquali/compare/23.4.1...23.4.2) et 23.4.2

* [Control] fix: produit parent et catégorie du formulaire de création lus comme des nombres [`f6e79881`](https://github.com/Evarisk/digiquali/commit/f6e79881)
* [Public] fix: entité du lien lue comme un nombre [`593fe153`](https://github.com/Evarisk/digiquali/commit/593fe153)
