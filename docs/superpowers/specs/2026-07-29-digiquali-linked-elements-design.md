# Gestion des éléments liables DigiQuali depuis `admin/sheet.php`

Date : 2026-07-29
Statut : validé, prêt pour le plan d'implémentation
Dépôts concernés : `saturne` (générique), `digiquali` (coquille fine)

## Problème

Le module DigiQuali crée, à l'activation, la totalité de l'infrastructure de liaison pour **tous** les
objets retournés par `saturne_get_objects_metadata()`, sans tenir compte des liens réellement souhaités.

État constaté sur une base de développement :

| Élément | Volume actuel |
|---|---|
| Onglets `+control` / `+survey` (`MAIN_MODULE_DIGIQUALI_TABS_*`) | 63 |
| Extrafields `qc_frequency` | 34 tables |
| Extrafield `control_history_link` | 2 tables |
| Hooks déclarés (`MAIN_MODULE_DIGIQUALI_HOOKS`) | ~30 |
| Constantes `DIGIQUALI_SHEET_LINK_*` à 1 | 9 |

Les onglets et extrafields sont posés sur les objets de tous les modules installés (Digirisk, DoliCar,
DoliMeet, Call), et même sur les objets DigiQuali eux-mêmes (`digiquali_control`, `digiquali_question`,
`digiquali_survey`).

La constante `DIGIQUALI_SHEET_LINK_<TYPE>` filtre **déjà** les vues (fiche modèle, fiche et liste
contrôle/audit, PDF) via la clé `conf` injectée par le hook `saturneMoreObjectsMetadata`
(`class/actions_digiquali.class.php`). Elle ne pilote en revanche ni les extrafields, ni les onglets,
ni les hooks. Le commentaire `//@todo add only wanted keys` de `admin/sheet.php` matérialise ce manque.

## Objectif

1. Faire de `admin/sheet.php` le point de pilotage unique des éléments liables.
2. Un basculement ON/OFF crée ou supprime réellement extrafields, onglets et hooks.
3. Un backward one-shot nettoie l'existant sans jamais casser un lien déjà utilisé.

## Décisions

| Sujet | Décision |
|---|---|
| Périmètre du toggle | extrafield `qc_frequency`, extrafield `control_history_link`, onglets `+control`/`+survey`, hooks `xxxcard`/`xxxlist` |
| Base du backward | constantes déjà à 1 **∪** usage réel constaté en base |
| Perte de données | confirmation explicite avant un OFF qui supprime des valeurs ; aucun confirm si l'extrafield est vide |
| Localisation du code | mécanique générique dans `saturne`, appels dans `digiquali` |
| IHM | section dédiée « Éléments liables » avec colonne Utilisation |
| Déclencheur | action de page (`?action=toggle_link`) + confirm JS, pas d'AJAX |

## Architecture

Source de vérité unique : l'ensemble des objets liés activés

```
enabledTypes = { objectType | getDolGlobalInt('DIGIQUALI_SHEET_LINK_' . strtoupper(objectType)) == 1 }
```

Trois effets de bord en sont dérivés, jamais écrits en dur :

- extrafields : `qc_frequency` sur la table de chaque objet activé ; `control_history_link` sur les
  seuls objets qui le déclarent. Chaque définition d'extrafield porte donc une restriction optionnelle
  de types d'objets : sans restriction elle s'applique à tous les objets activés, avec restriction elle
  ne s'applique qu'aux types listés et est supprimée partout ailleurs. `control_history_link` est
  restreint à `productlot` ; la copie présente sur `dolimeet_session` est un résidu d'une version
  antérieure et sera purgée par le backward ;
- onglets : `+control` et `+survey` par objet activé (`propal` reste sans onglet contrôle, comme
  aujourd'hui) ;
- hooks : `hook_name_card` et `hook_name_list` par objet activé.

La synchronisation procède **par remplacement complet**, ce qui la rend idempotente :
`delete_tabs()` purge tous les `MAIN_MODULE_DIGIQUALI_TABS_%` et `delete_module_parts()` purge
`MAIN_MODULE_DIGIQUALI_HOOKS` (`core/modules/DolibarrModules.class.php`), puis on réinsère depuis un
descripteur fraîchement instancié qui relit les constantes à jour.

La reconstruction des onglets doit se faire en **contexte web** : en CLI, les onglets injectés par les
autres modules via hook ne sont pas présents et seraient perdus. La page admin remplit cette condition.

## Composants

### Saturne

**`lib/linked_object.lib.php`** (nouveau)

- `saturne_get_linked_object_usage(array $objectsMetadata, array $extraFieldNames, array $linkedTables): array`
  Pour chaque type d'objet : nombre de lignes `element_element` le reliant aux tables passées en
  paramètre, et nombre de valeurs non vides pour chaque extrafield. Alimente la colonne « Utilisation »,
  le texte du confirm et le calcul du backward.

- `saturne_sync_linked_object_extrafields(array $definitions, array $objectsMetadata, array $enabledTypes): array`
  Ajoute l'extrafield sur les tables des objets activés, le supprime ailleurs. Retourne
  `['added' => [...], 'deleted' => [...]]`.

- `saturne_refresh_module_registrations(string $moduleName): array`
  Réinstancie le descripteur du module puis enchaîne `delete_tabs()`, `insert_tabs()`,
  `delete_module_parts()`, `insert_module_parts()`. Retourne le nombre d'onglets et de hooks écrits.

**`core/tpl/admin/object/linked_object_view.tpl.php`** (nouveau)
Tableau « Éléments liables » : picto + libellé de l'objet, colonne Utilisation, toggle, en-tête
Tout / Aucun, et bouton « Nettoyer les liens inutilisés ». Le TPL ne fait que rendre : toutes les
données (usage, impact, URLs d'action) sont préparées par la page appelante.

**`js/modules/linkedobject.js`** (nouveau)
Confirmation avant un OFF destructif, à partir des attributs `data-impact-*` rendus côté serveur.
Suit le squelette `init()` / `event()` / handlers, délégation via `$(document).on(...)`.

**`lib/object.lib.php`** (correctif)
Ajouter `alias_of` sur l'entrée `project_task`, qui duplique `task` sans le déclarer — contrairement à
`contrat`/`contract` qui le fait. Sans ce correctif, `projet_task` est traité deux fois par la synchro.

### DigiQuali

**`core/modules/modDigiQuali.class.php`**
- `__construct()` : les boucles qui remplissent `$this->tabs` et `$this->module_parts['hooks']` ne
  retiennent que les objets activés, et sautent les objets DigiQuali (`link_name` en `digiquali_*`).
- `init()` : ne crée plus les extrafields en masse. Joue le backward one-shot (garde
  `DIGIQUALI_LINKED_OBJECT_BACKWARD`) puis la synchronisation.

**`admin/sheet.php`**
- Nouveau bloc Actions : `toggle_link` (set const + synchro + message + redirect) et
  `clean_unused_links` (recalcul de l'ensemble activé + purge).
- Include du TPL `linked_object_view.tpl.php`.
- Suppression du `array_merge($constArray[...], $objectsMetadata)` : les liens ne transitent plus par
  le tableau générique « Config », qui ne garde que `UniqueLinkedElement` et `SheetLinkedObjectSelect2`.
  Le `//@todo add only wanted keys` disparaît.

**`class/actions_digiquali.class.php`** — inchangé : `saturneMoreObjectsMetadata` continue de fournir
`code`, `conf`, `name` et `description` par objet.

**`langs/fr_FR/` et `langs/en_US/`** — libellés de la section, du confirm et des messages de synthèse,
ajoutés dans les deux langues.

## Flux

### Basculer un lien sur OFF

1. Clic sur le toggle. Le JS lit `data-impact-extrafield` ; si la valeur est supérieure à 0, il demande
   confirmation en annonçant le nombre de valeurs `qc_frequency` qui seront supprimées. Sinon il
   poursuit sans question.
2. `GET admin/sheet.php?action=toggle_link&objecttype=product&value=0&token=…`
3. `dolibarr_set_const('DIGIQUALI_SHEET_LINK_PRODUCT', 0, …)`
4. Synchronisation : suppression de l'extrafield sur `product`, reconstruction des onglets (les deux
   entrées `product:+control` et `product:+survey` disparaissent) et des hooks (`productcard`,
   `productservicelist`).
5. `setEventMessage()` récapitulatif, puis redirection sur la page.

### Basculer un lien sur ON

Symétrique et jamais destructif : `addExtraField()`, onglets et hooks réinsérés, aucune confirmation.

### Tout / Aucun

Les deux raccourcis d'en-tête passent par la même action que les toggles unitaires, sur l'ensemble des
objets. « Tout » recrée les extrafields, onglets et hooks de tous les objets disponibles : l'opération
est lourde et contraire à l'objectif de la page, elle demande donc une confirmation. « Aucun » est
destructif et demande une confirmation annonçant le volume total de valeurs supprimées.

### Nettoyer les liens inutilisés

Recalcule l'ensemble activé et purge tout le reste. C'est le backward, rejouable à volonté depuis la
page ; utile après une réactivation de module ou l'installation d'un nouveau module tiers.

## Backward one-shot

Garde : constante `DIGIQUALI_LINKED_OBJECT_BACKWARD`, jouée dans `init()`.

Ensemble cible :

```
cible = { const déjà à 1 }
      ∪ { objets ayant ≥ 1 ligne element_element vers digiquali_control ou digiquali_survey }
      ∪ { objets ayant ≥ 1 valeur qc_frequency non vide }
```

Les constantes manquantes de l'ensemble cible sont créées à 1, puis la synchronisation complète est
jouée. Aucun lien déjà posé ne peut donc disparaître de l'IHM.

Ordonnancement dans `init()` : `$this->tabs` et `$this->module_parts` sont figés par `__construct()`,
donc avant que le backward n'ait écrit la moindre constante. Le backward doit écrire les constantes
**avant** l'appel à `$this->_init()`, et la reconstruction des onglets et hooks doit intervenir
**après** lui, sur un descripteur réinstancié — sinon `_init()` réinsère l'ancien jeu complet.

Résultat mesuré sur la base de développement de référence :

- **conservés** : product, productlot, project, thirdparty, ticket, user, contact, contract ;
- **purgés** : 24 extrafields (33 → 9) et 47 onglets (63 → 16), hooks ramenés de ~30 à 28
  (12 hooks de base du module + 8 objets × 2).

### Règles de périmètre

- **Ensemble géré et ensemble liable sont distincts.** L'ensemble *liable* — ce que la page propose —
  exclut les objets DigiQuali (`link_name` commençant par `digiquali_`). L'ensemble *géré* — sur lequel
  la synchro des extrafields itère — ne les exclut pas : n'étant jamais activables, leur `qc_frequency`
  est supprimé. Sans cette distinction, `digiquali_control`, `digiquali_question` et `digiquali_survey`
  garderaient indéfiniment un extrafield hérité.
- Déduplication par `table_element` avant toute écriture, pour absorber les entrées alias
  (`contrat`/`contract`, `project_task`/`task`).
- **Les objets absents de la métadonnée ne sont pas touchés.** DoliMeet n'implémente pas
  `saturneExtendGetObjectsMetadata` : `dolimeet_trainsess` n'est donc pas un objet liable, et la
  constante `DIGIQUALI_SHEET_LINK_DOLIMEET_TRAINSESS` comme les extrafields sur `dolimeet_session` sont
  des résidus d'une version antérieure. Ils sont délibérément **laissés intacts** : un module peut être
  temporairement désactivé, et supprimer ses colonnes détruirait des données.

## Gestion des erreurs

- La synchronisation est encadrée par `$db->begin()` / `$db->commit()`. Tout échec de `insert_tabs()`,
  `addExtraField()` ou `delete()` déclenche un `rollback()` et un `setEventMessages(…, 'errors')` ;
  la constante n'est pas modifiée.
- La suppression d'un extrafield inexistant et l'ajout d'un extrafield déjà présent sont traités comme
  des non-opérations, pas comme des erreurs : la synchro doit rester rejouable.
- L'état final ne dépend pas du point de départ : réactiver le module, rejouer le nettoyage ou
  basculer plusieurs toggles convergent vers le même résultat.

## Vérification

Les tests PHPUnit de saturne tournent sur un bootstrap sans base de données ; la vérification est donc
manuelle et outillée.

Trois contraintes d'environnement, découvertes à l'implémentation :

- `DOL_DOCUMENT_ROOT` est déduit du chemin du bootstrap (quatre niveaux au-dessus de
  `tests/phpunit`). La suite ne passe donc que depuis `htdocs/custom/saturne`, pas depuis un worktree
  situé ailleurs.
- Le navigateur charge `js/saturne.min.js`, pas les sources : tester le module JS en local impose de
  rejouer `gulp js_backend`. Le fichier minifié ne doit jamais être commité, la CI le régénère.
- Un script CLI qui charge `main.inc.php` doit définir `NOSCANPHPSELFFORINJECTION` : en ligne de
  commande `$_SERVER['PHP_SELF']` vaut le chemin absolu du script, que le WAF de Dolibarr rejette.

Étapes :

1. Script d'inspection SQL joué avant et après (extrafields, `MAIN_MODULE_DIGIQUALI_TABS_*`,
   `MAIN_MODULE_DIGIQUALI_HOOKS`, constantes `DIGIQUALI_SHEET_LINK_*`, volumétrie `element_element`).
2. Playwright : onglets Contrôles et Audits présents sur une fiche produit, absents sur une fiche
   facture ; le tableau « Éléments liables » reflète l'état réel ; le confirm apparaît bien sur un
   objet dont l'extrafield est renseigné.
3. Contrôle de non-régression fonctionnelle : `view/sheet/sheet_card.php` ne propose plus que les
   objets activés, et un contrôle existant lié à un objet conservé reste consultable.
4. PHPCS PSR-12 et JSHint sur les fichiers touchés, dans les deux dépôts.
