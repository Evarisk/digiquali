# [DigiQuali] [23.2.0] - QR codes de vos contrôles - Photos multiples - Application mobile

Description : Collez un QR code sur chaque équipement : un scan affiche l'historique de ses contrôles, sans connexion. Cette version apporte aussi un type de question à plusieurs photos et une application mobile repensée, où l'on crée un contrôle depuis le téléphone.

**Cette version s'utilise avec Saturne 23.5.0**, qui fournit les URL courtes des QR codes. Avec un Saturne plus ancien, le module fonctionne mais garde les liens longs.

## 🎬 La nouveauté en vidéo

[![QR codes de vos contrôles en vidéo (42 s)](https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.2.0/video_poster_23.2.0.jpeg)](https://github.com/Evarisk/digiquali/releases/download/23.2.0/digiquali-23.2.0.mp4)

*Cliquez sur l'image pour voir la vidéo (42 s).*

## Nouvelles fonctionnalités et innovations

### QR codes de l'historique des contrôles

* **Une planche de QR codes en un clic.** Sur la liste des lots, cochez les éléments puis choisissez l'action **« Générer les QR codes (PDF) »** : vous choisissez le format d'étiquettes (formats Avery de Dolibarr) et le PDF sort avec, pour chaque élément, son QR code, son numéro de lot et la référence du produit.
* **Pour tout ce que vous contrôlez.** L'action est proposée sur la liste de chaque type d'objet que DigiQuali peut contrôler (lots, produits, tiers…), selon la configuration des modèles.
* **Un scan, tout l'historique.** Le QR code ouvre la page publique de l'équipement — état, dernier contrôle, prochaine échéance — sans connexion. Il porte une URL courte de Saturne, donc un code petit et lisible même sur une étiquette.
* **Le lien sur la fiche du lot.** Le champ « Interface publique contrôle » affiche l'URL courte, copiable en un clic pour un e-mail ou un SMS.

![Action de masse « Générer les QR codes (PDF) » sur la liste des lots](https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.2.0/massaction.png)

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.2.0/labels.png" alt="Planche d'étiquettes : un QR code par lot" width="300"> <img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.2.0/public.png" alt="Page publique ouverte par le QR code" width="260">

### Historique public protégé

* **Un lien qu'on ne devine pas.** Le lien de l'historique public porte désormais une signature propre à votre instance : on ne peut plus afficher l'historique d'un autre équipement en modifiant l'adresse.
* **Rien ne casse.** Les autocollants EasyURL déjà affectés à des lots continuent de fonctionner, et un utilisateur connecté ouvre toujours l'historique depuis Dolibarr (lien du bandeau DoliCar, par exemple).

### Nouveau type de question : Photos multiples

* **Plusieurs photos par question**, chacune avec son commentaire et son statut OK / KO, sous forme de tableau.
* Les photos s'ajoutent depuis l'appareil ou depuis la **bibliothèque de médias**, qui est aussi accessible en page liste.

![Type de question Photos multiples](https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.2.0/question.png)

### Application mobile repensée

* **La page publique de contrôle adopte le design de l'application mobile** : en-tête au logo de votre société, barre de navigation en bas (État, Contrôles, Documentation), cartes claires pour l'objet, son statut et le prochain contrôle.
* **Échéance dépassée** : un dernier contrôle conforme dont la date de prochain contrôle est passée n'est plus présenté comme conforme.
* **Créer un contrôle depuis le téléphone** : depuis la page publique d'un objet ou la liste des contrôles de l'application, on choisit le modèle, on confirme, et le contrôle s'ouvre directement en saisie.

<img src="https://raw.githubusercontent.com/nicolas-eoxia/DoliSMQ/refs/heads/assets/release-23.2.0/pwa.png" alt="Accueil de l'application mobile" width="260">

## Améliorations & corrections

### Pages publiques

* **Bien plus légères.** La page publique chargeait tous les fichiers partagés de l'instance — plusieurs milliers — pour n'en afficher que quelques-uns : sur un véhicule, elle pouvait dépasser la mémoire autorisée et s'arrêter. Seuls les fichiers de l'objet sont désormais chargés.
* **Un lien abîmé ne plante plus la page** : un QR code mal imprimé ou un lien tronqué affiche « introuvable » au lieu d'une erreur, et seuls des objets Dolibarr peuvent être ouverts depuis l'adresse.
* **Un objet supprimé emporte son URL courte**, qui ne mène plus à une page vide.

### Modèles de contrôle

* **Tout ouvrir / tout fermer** l'arborescence des questions et groupes en un clic, avec l'état mémorisé au rechargement. L'icône et le titre du modèle restent alignés, les chevrons sont cohérents.

### Documents

* **Plus d'avertissement PHP** à la génération des documents ODT et PDF d'un contrôle.

## Testez les nouveautés maintenant

**Allez tester les QR codes de vos contrôles sur la démo : [https://demodoli.digirisk.com/](https://demodoli.digirisk.com/)**

## Comparaison des versions [23.1.1](https://github.com/Evarisk/digiquali/compare/23.1.1...23.2.0) et 23.2.0

* #2667 [Public] feat: URL courte et planche PDF de QR codes pour l'historique public des contrôles [`#2668`](https://github.com/Evarisk/digiquali/pull/2668)
* #2669 [Public] fix: suivre le déplacement de l'entrée des URL courtes Saturne (public/s → s) [`#2670`](https://github.com/Evarisk/digiquali/pull/2670)
* #2671 [Public] feat: track_id signé pour l'historique public des contrôles ; planche QR pour tout objet contrôlable [`#2672`](https://github.com/Evarisk/digiquali/pull/2672)
* #2673 [Public] fix: ne charger que les fichiers partagés de l'objet, pas tous ceux de l'instance [`#2674`](https://github.com/Evarisk/digiquali/pull/2674)
* #2675 [Public] fix: supprimer l'URL courte d'un objet contrôlé à sa suppression [`#2676`](https://github.com/Evarisk/digiquali/pull/2676)
* #2663 [Public] fix: lire le track_id defensivement, ne prendre que des objets Dolibarr [`#2664`](https://github.com/Evarisk/digiquali/pull/2664)
* #2453 [PublicControl] rework: interface publique de contrôle au design de la PWA et création de contrôle en PWA [`#2653`](https://github.com/Evarisk/digiquali/pull/2653)
* #2631 [Question] add: type de question Photos multiples (photo, commentaire et statut par ligne) [`#2645`](https://github.com/Evarisk/digiquali/pull/2645)
* #2631 fix: prevent PHP 8 warnings when generating ODT/PDF documents [`#2651`](https://github.com/Evarisk/digiquali/pull/2651)
* #2145 [Sheet] fix: boutons + / - pour tout ouvrir ou fermer l'arborescence et calage de son affichage [`#2656`](https://github.com/Evarisk/digiquali/pull/2656)
* #2642 [Mod] fix: renommer le changelog en ChangeLog.md [`#2643`](https://github.com/Evarisk/digiquali/pull/2643)
* #2626 #2629 #2636 #2639 [CI] chaîne qualité : exclusions de stubs, scan du socle par dossier, baseline élaguée
