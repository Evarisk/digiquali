# [DigiQuali] [23.2.2] - Correctif de sécurité des pages publiques

Description : Version corrective de sécurité. Les pages publiques de réponse aux contrôles et aux questionnaires contrôlent désormais les paramètres de leur lien. **Mise à jour recommandée pour toutes les installations**, que l'interface publique soit utilisée ou non : ces pages sont accessibles sans connexion.

Cette version s'utilise, comme la 23.2.1, avec Saturne 23.5.0 ou plus récent.

## Améliorations & corrections

### Interface publique

* **Paramètres du lien vérifiés.** La page publique de réponse et celle des questionnaires utilisaient les paramètres de l'adresse sans les contrôler. Un lien modifié pouvait ouvrir un contrôle ou un questionnaire autre que celui partagé. Les paramètres sont maintenant vérifiés et échappés.
* **Plus d'erreur fatale sur un lien incomplet.** Un lien tronqué, ou sans type d'objet, affichait une page d'erreur PHP. Il affiche désormais « Enregistrement non trouvé ».

## Comparaison des versions [23.2.1](https://github.com/Evarisk/digiquali/compare/23.2.1...23.2.2) et 23.2.2

* #2731 [Public] fix: valider object_type et échapper track_id sur les pages publiques de réponse et d'enquête [`#2732`](https://github.com/Evarisk/digiquali/pull/2732)
