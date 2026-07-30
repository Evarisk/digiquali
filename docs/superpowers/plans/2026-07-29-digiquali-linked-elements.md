# Éléments liables DigiQuali — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** faire de `digiquali/admin/sheet.php` le point de pilotage unique des éléments liables, de sorte qu'un basculement ON/OFF crée ou supprime réellement les extrafields, onglets et hooks, et nettoyer par un backward one-shot tout ce que l'`init()` avait créé sans être utilisé.

**Architecture:** une source de vérité unique, l'ensemble `{ objectType | DIGIQUALI_SHEET_LINK_<TYPE> == 1 }`, dont trois effets de bord sont dérivés par une synchronisation idempotente : extrafields, onglets `+control`/`+survey`, hooks `xxxcard`/`xxxlist`. La mécanique générique vit dans Saturne (`lib/linked_object.lib.php`, un TPL, un module JS), DigiQuali n'en est qu'une coquille fine (définitions d'extrafields, préfixe de constante, politique de backward).

**Tech Stack:** PHP 7.4+, Dolibarr 23, framework Saturne, PHPUnit 9 (bootstrap stub sans base), jQuery, PHPCS PSR-12, JSHint.

**Spec de référence :** `docs/superpowers/specs/2026-07-29-digiquali-linked-elements-design.md`
**Issue :** [Evarisk/digiquali#2510](https://github.com/Evarisk/digiquali/issues/2510)

## Global Constraints

- Branche unique dans les deux dépôts : `rework/2510-linked-elements-admin`, coupée de `origin/develop`.
- Worktrees de travail : `…/scratchpad/wt/digiquali` et `…/scratchpad/wt/saturne`. Le chemin exact est celui affiché par `git worktree list` dans chaque dépôt.
- Format de commit : `#2510 [{Scope}] {type}: {description}`. Types utilisés ici : `add`, `rework`, `fix`, `docs`.
- Pousser sur le fork `nicolas-eoxia` dans les deux dépôts (les rulesets bloquent la création de branches sur `origin`). PR vers `Evarisk:develop`.
- **La PR Saturne doit être mergée avant la PR DigiQuali** : DigiQuali appelle des fonctions qui n'existent que dans Saturne.
- PHP minimum du module : `7.4` (`$this->phpmin = [7, 4]`). Interdits : `enum`, `readonly`, arguments nommés, unpacking de tableaux à clés string.
- Style : PSR-12, 4 espaces dans Saturne. Dans `modDigiQuali.class.php`, respecter l'indentation de la zone modifiée (le fichier mélange tabulations historiques et espaces).
- Commentaires sur la ligne **au-dessus** du code, jamais en fin de ligne.
- Ne jamais commiter `saturne.min.js`, `saturne.min.css`, `digiquali.min.js` ni `digiquali.min.css` : la CI `build-assets` les régénère.
- Préfixe de constante DigiQuali : `DIGIQUALI_SHEET_LINK_`.
- `dol_strtoupper()` n'est **pas** stubbé par le bootstrap PHPUnit de Saturne : utiliser `strtoupper()` dans `lib/linked_object.lib.php`.
- Les clés de langue `LinkProduct`, `LinkProductDescription`, `LinkProductlot`, … existent déjà dans `digiquali/langs/fr_FR/digiquali.lang` : les réutiliser, ne pas les recréer.
- `$langs->trans('KEY')` applique lui-même `sprintf` : passer les paramètres à `trans()`, ne pas envelopper l'appel dans un `sprintf` externe.
- Toute clé de langue ajoutée doit l'être dans `fr_FR` **et** `en_US`.
- PHP CLI à utiliser : `/c/wamp64/bin/php/php8.2.29/php.exe` (le `php` du PATH est en 7.4).

---

## Structure des fichiers

**Saturne** (worktree `wt/saturne`)

| Fichier | Responsabilité |
|---|---|
| `lib/linked_object.lib.php` *(créé)* | Toute la mécanique générique : sélection des objets liables, mesure d'usage, synchro des extrafields, reconstruction onglets/hooks |
| `lib/saturne_functions.lib.php` *(modifié)* | Ajout du `require_once` de la nouvelle lib, pour qu'elle soit disponible partout où `object.lib.php` l'est déjà |
| `lib/object.lib.php` *(modifié)* | Correctif `alias_of` manquant sur l'entrée `project_task` |
| `core/tpl/admin/object/linked_object_view.tpl.php` *(créé)* | Rendu du tableau « Éléments liables » — aucune logique métier |
| `js/modules/linkedObject.js` *(créé)* | Confirmation avant un basculement destructif |
| `tests/phpunit/unit/LinkedObjectLibTest.php` *(créé)* | Tests unitaires des fonctions pures |

**DigiQuali** (worktree `wt/digiquali`)

| Fichier | Responsabilité |
|---|---|
| `lib/digiquali_linked_object.lib.php` *(créé)* | Coquille fine : définitions d'extrafields, orchestration de la synchro, politique de backward |
| `core/modules/modDigiQuali.class.php` *(modifié)* | `__construct()` ne déclare onglets et hooks que pour les objets activés ; `init()` joue backward puis synchro |
| `admin/sheet.php` *(modifié)* | Actions `toggle_link` et `clean_unused_links`, préparation des données, inclusion du TPL |
| `langs/fr_FR/digiquali.lang`, `langs/en_US/digiquali.lang` *(modifiés)* | Libellés de la section, des confirmations et des messages de synthèse |

**Outillage de vérification** (hors dépôt, dans le scratchpad)

| Fichier | Responsabilité |
|---|---|
| `scratchpad/inspect_links.php` *(existe déjà)* | Photographie SQL de l'état : extrafields, onglets, hooks, constantes, usage `element_element` |

---

## Task 1 : Saturne — sélection des objets liables

**Files:**
- Create: `lib/linked_object.lib.php`
- Create: `tests/phpunit/unit/LinkedObjectLibTest.php`
- Modify: `lib/saturne_functions.lib.php` (bloc de `require_once` en tête, lignes 24-32)

**Interfaces:**
- Consumes: rien (première tâche).
- Produces:
  - `saturne_filter_linkable_objects(array $objectsMetadata, array $excludedLinkNamePrefixes = []): array` — retourne un sous-ensemble de `$objectsMetadata`, clés conservées, sans les entrées alias, sans les doublons de `table_element`, sans les objets dont le `link_name` commence par un des préfixes exclus.
  - `saturne_get_enabled_linked_object_types(array $linkableObjects, string $constPrefix): array` — retourne la liste des `objectType` dont la constante `$constPrefix . strtoupper($objectType)` vaut plus de 0.

- [ ] **Step 1 : installer les dépendances de test dans le worktree Saturne**

Le worktree n'a pas de `vendor/`. Depuis la racine du worktree Saturne :

```bash
composer install --no-interaction
```

Vérifier que `vendor/bin/phpunit` existe ensuite.

- [ ] **Step 2 : écrire les tests qui échouent**

Créer `tests/phpunit/unit/LinkedObjectLibTest.php` :

```php
<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Saturne\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Tests for the pure selection helpers of lib/linked_object.lib.php
 *
 * Only the functions that need neither database nor Dolibarr runtime are covered here.
 * The database-bound helpers are verified by the inspection script described in the plan.
 */
class LinkedObjectLibTest extends TestCase
{
    /**
     * Load linked_object.lib.php once for the entire class.
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../../lib/linked_object.lib.php';
    }

    /**
     * Each test starts from an empty constant set.
     */
    protected function setUp(): void
    {
        global $conf;

        $conf->global = new stdClass();
    }

    // ─── saturne_filter_linkable_objects ──────────────────────────────────────

    public function testFilterDropsAliasEntries(): void
    {
        $objectsMetadata = [
            'contract' => ['link_name' => 'contrat', 'table_element' => 'contrat'],
            'contrat'  => ['link_name' => 'contrat', 'table_element' => 'contrat', 'alias_of' => 'contract'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame(['contract'], array_keys($result));
    }

    public function testFilterDeduplicatesOnTableElement(): void
    {
        $objectsMetadata = [
            'task'         => ['link_name' => 'project_task', 'table_element' => 'projet_task'],
            'project_task' => ['link_name' => 'project_task', 'table_element' => 'projet_task'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame(['task'], array_keys($result));
    }

    public function testFilterDropsExcludedLinkNamePrefixes(): void
    {
        $objectsMetadata = [
            'product'          => ['link_name' => 'product', 'table_element' => 'product'],
            'digiquali_survey' => ['link_name' => 'digiquali_survey', 'table_element' => 'digiquali_survey'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata, ['digiquali_']);

        $this->assertSame(['product'], array_keys($result));
    }

    public function testFilterDropsEntriesWithoutTableElement(): void
    {
        $objectsMetadata = [
            'product' => ['link_name' => 'product', 'table_element' => 'product'],
            'broken'  => ['link_name' => 'broken'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame(['product'], array_keys($result));
    }

    public function testFilterKeepsMetadataUntouched(): void
    {
        $objectsMetadata = [
            'product' => ['link_name' => 'product', 'table_element' => 'product', 'picto' => 'product'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame('product', $result['product']['picto']);
    }

    // ─── saturne_get_enabled_linked_object_types ──────────────────────────────

    public function testEnabledTypesKeepsOnlyConstantsSetToOne(): void
    {
        global $conf;

        $conf->global->DIGIQUALI_SHEET_LINK_PRODUCT = 1;
        $conf->global->DIGIQUALI_SHEET_LINK_TICKET  = 0;

        $linkableObjects = [
            'product' => ['link_name' => 'product', 'table_element' => 'product'],
            'ticket'  => ['link_name' => 'ticket', 'table_element' => 'ticket'],
        ];

        $result = saturne_get_enabled_linked_object_types($linkableObjects, 'DIGIQUALI_SHEET_LINK_');

        $this->assertSame(['product'], $result);
    }

    public function testEnabledTypesUppercasesCompositeObjectType(): void
    {
        global $conf;

        $conf->global->DIGIQUALI_SHEET_LINK_DOLIMEET_TRAINSESS = 1;

        $linkableObjects = [
            'dolimeet_trainsess' => ['link_name' => 'dolimeet_trainsess', 'table_element' => 'dolimeet_session'],
        ];

        $result = saturne_get_enabled_linked_object_types($linkableObjects, 'DIGIQUALI_SHEET_LINK_');

        $this->assertSame(['dolimeet_trainsess'], $result);
    }

    public function testEnabledTypesTreatsMissingConstantAsDisabled(): void
    {
        $linkableObjects = ['bom' => ['link_name' => 'bom', 'table_element' => 'bom']];

        $result = saturne_get_enabled_linked_object_types($linkableObjects, 'DIGIQUALI_SHEET_LINK_');

        $this->assertSame([], $result);
    }
}
```

- [ ] **Step 3 : lancer les tests pour vérifier qu'ils échouent**

```bash
vendor/bin/phpunit --configuration tests/phpunit/phpunittest.xml --filter LinkedObjectLibTest
```

Attendu : ÉCHEC, `failed to open stream` sur `lib/linked_object.lib.php` (le fichier n'existe pas encore).

- [ ] **Step 4 : créer la lib avec l'implémentation minimale**

Créer `lib/linked_object.lib.php` :

```php
<?php
/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/linked_object.lib.php
 * \ingroup saturne
 * \brief   Library files with common functions to drive linked objects from a module admin page
 */

/**
 * Keep only the object types a module may actually link to
 *
 * Alias entries, duplicated table elements and the module's own objects are dropped, so that
 * a caller iterating on the result never processes the same database table twice.
 *
 * @param  array $objectsMetadata            Result of saturne_get_objects_metadata()
 * @param  array $excludedLinkNamePrefixes   Link name prefixes to drop, example ['digiquali_']
 * @return array                             Subset of $objectsMetadata, original keys preserved
 */
function saturne_filter_linkable_objects(array $objectsMetadata, array $excludedLinkNamePrefixes = []): array
{
    $linkableObjects = [];
    $seenTables      = [];

    foreach ($objectsMetadata as $objectType => $objectMetadata) {
        if (!empty($objectMetadata['alias_of'])) {
            continue;
        }

        $tableElement = $objectMetadata['table_element'] ?? '';
        if (empty($tableElement) || isset($seenTables[$tableElement])) {
            continue;
        }

        $linkName = $objectMetadata['link_name'] ?? '';
        foreach ($excludedLinkNamePrefixes as $excludedLinkNamePrefix) {
            if (strpos($linkName, $excludedLinkNamePrefix) === 0) {
                continue 2;
            }
        }

        $seenTables[$tableElement]    = true;
        $linkableObjects[$objectType] = $objectMetadata;
    }

    return $linkableObjects;
}

/**
 * Get the object types whose link is enabled by configuration
 *
 * @param  array  $linkableObjects Result of saturne_filter_linkable_objects()
 * @param  string $constPrefix     Constant prefix, example 'DIGIQUALI_SHEET_LINK_'
 * @return array                   List of enabled object types
 */
function saturne_get_enabled_linked_object_types(array $linkableObjects, string $constPrefix): array
{
    $enabledObjectTypes = [];

    foreach (array_keys($linkableObjects) as $objectType) {
        if (getDolGlobalInt($constPrefix . strtoupper($objectType)) > 0) {
            $enabledObjectTypes[] = $objectType;
        }
    }

    return $enabledObjectTypes;
}
```

- [ ] **Step 5 : lancer les tests pour vérifier qu'ils passent**

```bash
vendor/bin/phpunit --configuration tests/phpunit/phpunittest.xml --filter LinkedObjectLibTest
```

Attendu : 8 tests, 8 assertions minimum, OK.

- [ ] **Step 6 : charger la lib depuis saturne_functions.lib.php**

Dans `lib/saturne_functions.lib.php`, à la suite de la ligne `require_once __DIR__ . '/object.lib.php';` (ligne 29), ajouter :

```php
require_once __DIR__ . '/linked_object.lib.php';
```

- [ ] **Step 7 : vérifier PHPCS sur les fichiers créés**

```bash
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml lib/linked_object.lib.php tests/phpunit/unit/LinkedObjectLibTest.php
```

Attendu : aucune erreur. Si l'exécutable est absent, utiliser `vendor/bin/phpcs`.

- [ ] **Step 8 : commit**

```bash
git add lib/linked_object.lib.php lib/saturne_functions.lib.php tests/phpunit/unit/LinkedObjectLibTest.php
git commit -m "#2510 [LinkedObject] add: selection helpers for linkable objects"
```

---

## Task 2 : Saturne — correctif de l'alias `project_task`

**Files:**
- Modify: `lib/object.lib.php:555`

**Interfaces:**
- Consumes: rien.
- Produces: `saturne_get_objects_metadata()['project_task']['alias_of'] === 'task'`.

`$objectsMetadata['project_task']` duplique `$objectsMetadata['task']` sans se déclarer comme alias, contrairement à `contrat` qui le fait (lignes 633-634). La déduplication par `table_element` de la Task 1 absorbe déjà le cas, mais tous les autres consommateurs de la métadonnée traitent encore l'objet deux fois.

- [ ] **Step 1 : ajouter la déclaration d'alias**

Remplacer, dans `lib/object.lib.php`, la ligne :

```php
        $objectsMetadata['project_task'] = $objectsMetadata['task'];
```

par :

```php
        //@todo backward compatibility
        // Task::$element is 'project_task', so the object is still reachable with that legacy key
        $objectsMetadata['project_task']             = $objectsMetadata['task'];
        $objectsMetadata['project_task']['alias_of'] = 'task';
```

- [ ] **Step 2 : vérifier en CLI que l'alias est bien exposé**

Écrire dans le scratchpad un fichier `check_alias.php` :

```php
<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
define('NOLOGIN', 1);
define('NOSESSION', 1);
require 'C:/wamp64/www/dolibarr/htdocs/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/object.lib.php';

$objectsMetadata = saturne_get_objects_metadata();

echo 'project_task alias_of = ' . var_export($objectsMetadata['project_task']['alias_of'] ?? null, true) . "\n";
echo 'contrat alias_of      = ' . var_export($objectsMetadata['contrat']['alias_of'] ?? null, true) . "\n";
```

Lancer :

```bash
/c/wamp64/bin/php/php8.2.29/php.exe "<scratchpad>/check_alias.php"
```

Attendu :

```
project_task alias_of = 'task'
contrat alias_of      = 'contract'
```

Ce script lit la copie servie par WAMP : appliquer d'abord l'étape de synchronisation worktree → `htdocs` décrite en Task 11, ou pointer le `require_once` sur le chemin du worktree.

- [ ] **Step 3 : commit**

```bash
git add lib/object.lib.php
git commit -m "#2510 [ObjectMetadata] fix: declare project_task as an alias of task"
```

---

## Task 3 : Saturne — mesure de l'usage réel

**Files:**
- Modify: `lib/linked_object.lib.php`

**Interfaces:**
- Consumes: `saturne_filter_linkable_objects()` de la Task 1.
- Produces: `saturne_get_linked_object_usage(array $linkableObjects, array $extraFieldNames, array $linkedElementTypes): array` — retourne, par `objectType`, `['links' => int, 'extrafields' => ['<nom>' => int, …]]`.

`links` compte les lignes de `llx_element_element` reliant le `link_name` de l'objet à l'un des `$linkedElementTypes` (côté source ou côté cible). `extrafields` compte, pour chaque nom passé, les valeurs non nulles et non vides dans `llx_<table_element>_extrafields`.

- [ ] **Step 1 : ajouter la fonction à la lib**

Ajouter à la fin de `lib/linked_object.lib.php` :

```php
/**
 * Measure how much each linkable object is actually used
 *
 * Used to fill the usage column of the admin page, to size the confirmation message shown before a
 * destructive toggle, and to compute the initial set of a module backward.
 *
 * @param  array $linkableObjects    Result of saturne_filter_linkable_objects()
 * @param  array $extraFieldNames    Extrafield names to count, example ['qc_frequency']
 * @param  array $linkedElementTypes Element types on the module side, example ['digiquali_control']
 * @return array                     objectType => ['links' => int, 'extrafields' => [name => int]]
 */
function saturne_get_linked_object_usage(array $linkableObjects, array $extraFieldNames, array $linkedElementTypes): array
{
    global $db;

    $usage             = [];
    $objectTypeByLink  = [];
    $tableByObjectType = [];

    foreach ($linkableObjects as $objectType => $objectMetadata) {
        $usage[$objectType]             = ['links' => 0, 'extrafields' => []];
        $objectTypeByLink[$objectMetadata['link_name']] = $objectType;
        $tableByObjectType[$objectType] = $objectMetadata['table_element'];

        foreach ($extraFieldNames as $extraFieldName) {
            $usage[$objectType]['extrafields'][$extraFieldName] = 0;
        }
    }

    if (!empty($linkedElementTypes)) {
        $escapedElementTypes = [];
        foreach ($linkedElementTypes as $linkedElementType) {
            $escapedElementTypes[] = "'" . $db->escape($linkedElementType) . "'";
        }
        $inClause = implode(', ', $escapedElementTypes);

        $sql  = 'SELECT sourcetype, targettype, COUNT(*) as nb';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'element_element';
        $sql .= ' WHERE sourcetype IN (' . $inClause . ') OR targettype IN (' . $inClause . ')';
        $sql .= ' GROUP BY sourcetype, targettype';

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $linkedSide = in_array($obj->sourcetype, $linkedElementTypes, true) ? $obj->targettype : $obj->sourcetype;
                if (isset($objectTypeByLink[$linkedSide])) {
                    $usage[$objectTypeByLink[$linkedSide]]['links'] += (int) $obj->nb;
                }
            }
            $db->free($resql);
        }
    }

    // Only tables that really carry the extrafield are counted, so that a missing column is not an error.
    $existingExtraFields = saturne_get_existing_extrafields($extraFieldNames);

    foreach ($tableByObjectType as $objectType => $tableElement) {
        foreach ($extraFieldNames as $extraFieldName) {
            if (!isset($existingExtraFields[$extraFieldName][$tableElement])) {
                continue;
            }

            $sql  = 'SELECT COUNT(*) as nb FROM ' . MAIN_DB_PREFIX . $tableElement . '_extrafields';
            $sql .= " WHERE " . $extraFieldName . " IS NOT NULL AND " . $extraFieldName . " <> ''";

            $resql = $db->query($sql);
            if ($resql) {
                $obj = $db->fetch_object($resql);
                $usage[$objectType]['extrafields'][$extraFieldName] = (int) $obj->nb;
                $db->free($resql);
            }
        }
    }

    return $usage;
}

/**
 * Get the tables on which the given extrafields are currently declared
 *
 * @param  array $extraFieldNames Extrafield names to look for
 * @return array                  name => [elementtype => true]
 */
function saturne_get_existing_extrafields(array $extraFieldNames): array
{
    global $db;

    $existingExtraFields = [];

    if (empty($extraFieldNames)) {
        return $existingExtraFields;
    }

    $escapedNames = [];
    foreach ($extraFieldNames as $extraFieldName) {
        $escapedNames[] = "'" . $db->escape($extraFieldName) . "'";
    }

    $sql  = 'SELECT name, elementtype FROM ' . MAIN_DB_PREFIX . 'extrafields';
    $sql .= ' WHERE name IN (' . implode(', ', $escapedNames) . ')';

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $existingExtraFields[$obj->name][$obj->elementtype] = true;
        }
        $db->free($resql);
    }

    return $existingExtraFields;
}
```

- [ ] **Step 2 : vérifier les compteurs contre l'état connu de la base**

Écrire `check_usage.php` dans le scratchpad :

```php
<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
define('NOLOGIN', 1);
define('NOSESSION', 1);
require 'C:/wamp64/www/dolibarr/htdocs/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/object.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/linked_object.lib.php';

$linkableObjects = saturne_filter_linkable_objects(saturne_get_objects_metadata(), ['digiquali_']);
$usage           = saturne_get_linked_object_usage($linkableObjects, ['qc_frequency'], ['digiquali_control', 'digiquali_survey']);

foreach ($usage as $objectType => $objectUsage) {
    if ($objectUsage['links'] > 0 || $objectUsage['extrafields']['qc_frequency'] > 0) {
        printf("%-24s links=%-5d qc_frequency=%d\n", $objectType, $objectUsage['links'], $objectUsage['extrafields']['qc_frequency']);
    }
}
```

Lancer :

```bash
/c/wamp64/bin/php/php8.2.29/php.exe "<scratchpad>/check_usage.php"
```

Attendu, sur la base de développement de référence : `project` à 48 liens, `contract` à 1054, `productlot` à 14, `ticket` à 6, `product` à 5, `thirdparty` à 3, `user` à 1 ; et `qc_frequency` à 3 pour `product`, 5 pour `productlot`, 2 pour `project`, 1 pour `thirdparty`, 2 pour `ticket`. Les totaux `links` doivent correspondre à la sortie de `inspect_links.php`, section « liens element_element digiquali ».

- [ ] **Step 3 : vérifier PHPCS**

```bash
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml lib/linked_object.lib.php
```

Attendu : aucune erreur.

- [ ] **Step 4 : commit**

```bash
git add lib/linked_object.lib.php
git commit -m "#2510 [LinkedObject] add: real usage measurement for linkable objects"
```

---

## Task 4 : Saturne — synchronisation des extrafields

**Files:**
- Modify: `lib/linked_object.lib.php`

**Interfaces:**
- Consumes: `saturne_filter_linkable_objects()`, `saturne_get_existing_extrafields()`.
- Produces: `saturne_sync_linked_object_extrafields(array $definitions, array $linkableObjects, array $enabledObjectTypes): array` — retourne `['added' => [], 'deleted' => [], 'errors' => int]`, les deux listes contenant des chaînes `'<nom> @ <table_element>'`.

Forme d'une définition :

```php
[
    'name'           => 'qc_frequency',
    'label'          => 'QcFrequency',
    'type'           => 'int',
    'pos'            => 100,
    'size'           => 10,
    'default_value'  => '',
    'param'          => 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',
    'alwayseditable' => 1,
    'list'           => 1,
    'langfile'       => 'digiquali@digiquali',
    'enabled'        => '$conf->digiquali->enabled',
    'object_types'   => [],
]
```

`object_types` vide signifie « tous les objets activés ». Rempli, il restreint la définition aux types listés, et l'extrafield est supprimé partout ailleurs.

- [ ] **Step 1 : ajouter la fonction à la lib**

Ajouter à la fin de `lib/linked_object.lib.php` :

```php
/**
 * Add or remove the extrafields carried by the linked objects
 *
 * The function is idempotent : adding an already present extrafield and deleting an absent one are
 * both no-ops, so it can be replayed at will.
 *
 * @param  array $definitions        Extrafield definitions, see the plan for the expected keys
 * @param  array $linkableObjects    Result of saturne_filter_linkable_objects()
 * @param  array $enabledObjectTypes Result of saturne_get_enabled_linked_object_types()
 * @return array                     ['added' => string[], 'deleted' => string[], 'errors' => int]
 */
function saturne_sync_linked_object_extrafields(array $definitions, array $linkableObjects, array $enabledObjectTypes): array
{
    global $db;

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

    $extraFields = new ExtraFields($db);
    $report      = ['added' => [], 'deleted' => [], 'errors' => 0];

    $extraFieldNames = [];
    foreach ($definitions as $definition) {
        $extraFieldNames[] = $definition['name'];
    }
    $existingExtraFields = saturne_get_existing_extrafields($extraFieldNames);

    foreach ($definitions as $definition) {
        foreach ($linkableObjects as $objectType => $objectMetadata) {
            $tableElement = $objectMetadata['table_element'];

            $isEnabled   = in_array($objectType, $enabledObjectTypes, true);
            $isInScope   = empty($definition['object_types']) || in_array($objectType, $definition['object_types'], true);
            $isWanted    = $isEnabled && $isInScope;
            $isDeclared  = isset($existingExtraFields[$definition['name']][$tableElement]);

            if ($isWanted && !$isDeclared) {
                $result = $extraFields->addExtraField(
                    $definition['name'],
                    $definition['label'],
                    $definition['type'],
                    $definition['pos'],
                    $definition['size'],
                    $tableElement,
                    0,
                    0,
                    $definition['default_value'],
                    $definition['param'],
                    $definition['alwayseditable'],
                    '',
                    $definition['list'],
                    '',
                    '',
                    0,
                    $definition['langfile'],
                    $definition['enabled']
                );

                if ($result > 0) {
                    $report['added'][] = $definition['name'] . ' @ ' . $tableElement;
                } else {
                    $report['errors']++;
                }
            } elseif (!$isWanted && $isDeclared) {
                $result = $extraFields->delete($definition['name'], $tableElement);

                if ($result >= 0) {
                    $report['deleted'][] = $definition['name'] . ' @ ' . $tableElement;
                } else {
                    $report['errors']++;
                }
            }
        }
    }

    return $report;
}
```

- [ ] **Step 2 : vérifier le comportement sur une table de test**

Écrire `check_extrafield_sync.php` dans le scratchpad :

```php
<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
define('NOLOGIN', 1);
define('NOSESSION', 1);
require 'C:/wamp64/www/dolibarr/htdocs/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/object.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/saturne/lib/linked_object.lib.php';

$definitions = [[
    'name'           => 'qc_frequency',
    'label'          => 'QcFrequency',
    'type'           => 'int',
    'pos'            => 100,
    'size'           => 10,
    'default_value'  => '',
    'param'          => 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',
    'alwayseditable' => 1,
    'list'           => 1,
    'langfile'       => 'digiquali@digiquali',
    'enabled'        => '$conf->digiquali->enabled',
    'object_types'   => [],
]];

$linkableObjects = saturne_filter_linkable_objects(saturne_get_objects_metadata(), ['digiquali_']);

// Only bom is enabled : every other table must lose the extrafield.
$report = saturne_sync_linked_object_extrafields($definitions, $linkableObjects, ['bom']);
print_r($report);

// Replay : nothing must move.
$report = saturne_sync_linked_object_extrafields($definitions, $linkableObjects, ['bom']);
print_r($report);
```

**AVERTISSEMENT — ne pas exécuter tel quel sur une base porteuse de données.**

Supprimer un extrafield exécute un `DROP COLUMN` : les valeurs sont perdues définitivement. Restaurer
la *liste* des tables ne restaure pas leur *contenu*. Lors de la première exécution de ce plan, cette
étape a détruit 13 valeurs `qc_frequency` sur la base de développement (product 3, product_lot 5,
projet 2, societe 1, ticket 2), sans possibilité de récupération faute de sauvegarde et de binlog.

Deux façons sûres de vérifier l'idempotence :

- **Sur un objet sans données.** Choisir un type d'objet dont l'extrafield est vide — le vérifier
  d'abord avec `saturne_get_linked_object_usage()` — et ne faire porter le test que sur celui-là.
- **Avec sauvegarde préalable.** `mysqldump` des tables `*_extrafields` concernées avant l'exécution,
  restauration après.

Attendu : au premier appel, `added` contient l'objet activé et `deleted` les autres ; au second appel,
`added` et `deleted` sont vides et `errors` vaut 0 — c'est la preuve d'idempotence.

- [ ] **Step 3 : vérifier PHPCS**

```bash
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml lib/linked_object.lib.php
```

- [ ] **Step 4 : commit**

```bash
git add lib/linked_object.lib.php
git commit -m "#2510 [LinkedObject] add: idempotent extrafield synchronisation"
```

---

## Task 5 : Saturne — reconstruction des onglets et des hooks

**Files:**
- Modify: `lib/linked_object.lib.php`

**Interfaces:**
- Consumes: rien des tâches précédentes.
- Produces: `saturne_refresh_module_registrations(string $moduleDirectory, string $moduleClassName): array` — retourne `['tabs' => int, 'hooks' => int, 'errors' => int]`.

La fonction réinstancie le descripteur du module — donc relit les constantes à jour — puis remplace intégralement `MAIN_MODULE_<MODULE>_TABS_*` et les `module_parts`. `delete_tabs()` supprime toutes les entrées `_TABS_%` et `delete_module_parts()` supprime les constantes correspondant aux clés déclarées : la reconstruction est un remplacement, pas un patch.

**Doit être appelée en contexte web.** En CLI, les onglets injectés par les autres modules via hook ne sont pas chargés et seraient perdus.

- [ ] **Step 1 : ajouter la fonction à la lib**

Ajouter à la fin de `lib/linked_object.lib.php` :

```php
/**
 * Rebuild the tabs and module parts a module registers into the database
 *
 * The module descriptor is instantiated again on purpose : it reads the configuration constants in its
 * constructor, so a fresh instance is the only way to pick up a constant changed in the same request.
 *
 * Must be called from a web request : in CLI the tabs injected by other modules through hooks are not
 * loaded, and rebuilding would silently drop them.
 *
 * @param  string $moduleDirectory Module directory under htdocs/custom, example 'digiquali'
 * @param  string $moduleClassName Descriptor class name, example 'modDigiQuali'
 * @return array                   ['tabs' => int, 'hooks' => int, 'errors' => int]
 */
function saturne_refresh_module_registrations(string $moduleDirectory, string $moduleClassName): array
{
    global $db;

    $classPath = dol_buildpath('/' . $moduleDirectory . '/core/modules/' . $moduleClassName . '.class.php', 0);
    if (!file_exists($classPath)) {
        return ['tabs' => 0, 'hooks' => 0, 'errors' => 1];
    }

    require_once $classPath;

    $module = new $moduleClassName($db);

    $errors  = $module->delete_tabs();
    $errors += $module->insert_tabs();
    $errors += $module->delete_module_parts();
    $errors += $module->insert_module_parts();

    return [
        'tabs'   => is_array($module->tabs) ? count($module->tabs) : 0,
        'hooks'  => isset($module->module_parts['hooks']) ? count($module->module_parts['hooks']) : 0,
        'errors' => $errors
    ];
}
```

- [ ] **Step 2 : vérifier PHPCS**

```bash
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml lib/linked_object.lib.php
```

- [ ] **Step 3 : commit**

```bash
git add lib/linked_object.lib.php
git commit -m "#2510 [LinkedObject] add: module tabs and hooks rebuild helper"
```

La vérification fonctionnelle de cette fonction se fait en Task 7, quand DigiQuali l'appelle avec un descripteur filtré : seule l'exécution en contexte web permet de constater le passage de 63 à 18 onglets.

---

## Task 6 : DigiQuali — coquille fine et descripteur filtré

**Files:**
- Create: `lib/digiquali_linked_object.lib.php`
- Modify: `core/modules/modDigiQuali.class.php:314-343` (bloc onglets et hooks du constructeur)

**Interfaces:**
- Consumes: `saturne_filter_linkable_objects()`, `saturne_get_enabled_linked_object_types()`, `saturne_sync_linked_object_extrafields()`, `saturne_refresh_module_registrations()`, `saturne_get_linked_object_usage()`.
- Produces:
  - `digiquali_get_linked_object_extrafield_definitions(): array`
  - `digiquali_get_linkable_objects(): array`
  - `digiquali_get_enabled_linked_object_types(): array`
  - `digiquali_sync_linked_objects(): array` — retourne `['tabs' => int, 'hooks' => int, 'added' => string[], 'deleted' => string[], 'errors' => int]`
  - Constantes de cadrage : `DIGIQUALI_LINKED_OBJECT_CONST_PREFIX`, `DIGIQUALI_LINKED_OBJECT_EXCLUDED_PREFIX` — définies comme constantes PHP dans la lib.

- [ ] **Step 1 : créer la coquille DigiQuali**

Créer `lib/digiquali_linked_object.lib.php` :

```php
<?php
/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/digiquali_linked_object.lib.php
 * \ingroup digiquali
 * \brief   Library files with functions for DigiQuali linked objects
 */

require_once __DIR__ . '/../../saturne/lib/object.lib.php';
require_once __DIR__ . '/../../saturne/lib/linked_object.lib.php';

// Configuration constant prefix driving every link of the module.
define('DIGIQUALI_LINKED_OBJECT_CONST_PREFIX', 'DIGIQUALI_SHEET_LINK_');

// DigiQuali objects are never linkable to themselves.
define('DIGIQUALI_LINKED_OBJECT_EXCLUDED_PREFIX', 'digiquali_');

/**
 * Get the extrafield definitions carried by DigiQuali linked objects
 *
 * @return array Definitions accepted by saturne_sync_linked_object_extrafields()
 */
function digiquali_get_linked_object_extrafield_definitions(): array
{
    return [
        [
            'name'           => 'qc_frequency',
            'label'          => 'QcFrequency',
            'type'           => 'int',
            'pos'            => 100,
            'size'           => 10,
            'default_value'  => '',
            'param'          => 'a:1:{s:7:"options";a:1:{s:0:"";N;}}',
            'alwayseditable' => 1,
            'list'           => 1,
            'langfile'       => 'digiquali@digiquali',
            'enabled'        => '$conf->digiquali->enabled',
            'object_types'   => []
        ],
        [
            'name'           => 'control_history_link',
            'label'          => 'ControlHistoryLink',
            'type'           => 'varchar',
            'pos'            => 110,
            'size'           => 255,
            'default_value'  => '',
            'param'          => '',
            'alwayseditable' => 0,
            'list'           => 5,
            'langfile'       => 'digiquali@digiquali',
            'enabled'        => '$conf->digiquali->enabled',
            'object_types'   => ['productlot']
        ]
    ];
}

/**
 * Get the objects DigiQuali may link a sheet to
 *
 * @return array Subset of saturne_get_objects_metadata()
 */
function digiquali_get_linkable_objects(): array
{
    return saturne_filter_linkable_objects(saturne_get_objects_metadata(), [DIGIQUALI_LINKED_OBJECT_EXCLUDED_PREFIX]);
}

/**
 * Get the object types whose link is enabled
 *
 * @return array List of enabled object types
 */
function digiquali_get_enabled_linked_object_types(): array
{
    return saturne_get_enabled_linked_object_types(digiquali_get_linkable_objects(), DIGIQUALI_LINKED_OBJECT_CONST_PREFIX);
}

/**
 * Align extrafields, tabs and hooks on the enabled links
 *
 * Idempotent : replaying it converges to the same state whatever the starting point.
 * Must be called from a web request, see saturne_refresh_module_registrations().
 *
 * @return array ['tabs' => int, 'hooks' => int, 'added' => string[], 'deleted' => string[], 'errors' => int]
 */
function digiquali_sync_linked_objects(): array
{
    $linkableObjects    = digiquali_get_linkable_objects();
    $enabledObjectTypes = saturne_get_enabled_linked_object_types($linkableObjects, DIGIQUALI_LINKED_OBJECT_CONST_PREFIX);

    $extraFieldReport   = saturne_sync_linked_object_extrafields(digiquali_get_linked_object_extrafield_definitions(), $linkableObjects, $enabledObjectTypes);
    $registrationReport = saturne_refresh_module_registrations('digiquali', 'modDigiQuali');

    return [
        'tabs'    => $registrationReport['tabs'],
        'hooks'   => $registrationReport['hooks'],
        'added'   => $extraFieldReport['added'],
        'deleted' => $extraFieldReport['deleted'],
        'errors'  => $extraFieldReport['errors'] + $registrationReport['errors']
    ];
}
```

- [ ] **Step 2 : filtrer les onglets et les hooks du descripteur**

Dans `core/modules/modDigiQuali.class.php`, remplacer le bloc actuel (de `$objectsMetadata = saturne_get_objects_metadata();` jusqu'à la fermeture de la boucle `foreach`, lignes 320 à 342) par :

```php
        $linkableObjects    = saturne_filter_linkable_objects(saturne_get_objects_metadata(), ['digiquali_']);
        $enabledObjectTypes = saturne_get_enabled_linked_object_types($linkableObjects, 'DIGIQUALI_SHEET_LINK_');

        foreach ($enabledObjectTypes as $objectType) {
            $objectMetadata = $linkableObjects[$objectType];

            if (preg_match('/_/', $objectType)) {
                $splittedElementType = explode('_', $objectType);
                $moduleName          = $splittedElementType[0];
                $objectName          = dol_strtolower($objectMetadata['class_name']);
                $tabType             = $objectName . '@' . $moduleName;
            } else {
                $tabType = $objectMetadata['tab_type'];
            }

            if ($objectMetadata['link_name'] !== 'propal') {
                $this->tabs[] = ['data' => $tabType . ':+control:' . $pictoDigiQuali . $langs->trans('Controls') . ':digiquali@digiquali:$user->rights->digiquali->control->read:/custom/digiquali/view/control/control_list.php?fromid=__ID__&fromtype=' . $objectMetadata['link_name']];
            }
            $this->tabs[] = ['data' => $tabType . ':+survey:' . $pictoDigiQuali . $langs->trans('Surveys') . ':digiquali@digiquali:$user->rights->digiquali->survey->read:/custom/digiquali/view/survey/survey_list.php?fromid=__ID__&fromtype=' . $objectMetadata['link_name']];

            $this->module_parts['hooks'][] = $objectMetadata['hook_name_list'];
            $this->module_parts['hooks'][] = $objectMetadata['hook_name_card'];
        }
```

Trois points de vigilance :

1. La variable de boucle qui portait le type d'onglet s'appelait `$objectType` et était réassignée à l'intérieur. Elle est maintenant nommée `$tabType`, pour que la clé d'origine reste disponible.
2. Le test `alias_of` de l'ancienne boucle disparaît : `saturne_filter_linkable_objects()` s'en charge.
3. Les fonctions Saturne sont disponibles car `lib/saturne_functions.lib.php`, requis en tête du constructeur ligne 45, charge `object.lib.php` puis `linked_object.lib.php` (Task 1, Step 6). Ne pas ajouter de `require_once` supplémentaire ici.

- [ ] **Step 3 : vérifier que la page des modules reste affichable**

Le descripteur est instancié par `admin/modules.php` : une erreur y blanchit toute la page.

```bash
curl -s -o /dev/null -w "%{http_code}\n" "http://localhost/dolibarr/htdocs/admin/modules.php"
```

Attendu : `200`. Puis vérifier l'absence de nouvelle erreur dans le log PHP :

```bash
tail -20 /c/wamp64/logs/php_error.log
```

Attendu : aucune ligne mentionnant `modDigiQuali` ou `linked_object`.

- [ ] **Step 4 : vérifier PHPCS**

```bash
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml lib/digiquali_linked_object.lib.php
```

- [ ] **Step 5 : commit**

```bash
git add lib/digiquali_linked_object.lib.php core/modules/modDigiQuali.class.php
git commit -m "#2510 [Admin] rework: declare tabs and hooks only for enabled links"
```

---

## Task 7 : DigiQuali — backward one-shot et synchro à l'activation

**Files:**
- Modify: `lib/digiquali_linked_object.lib.php`
- Modify: `core/modules/modDigiQuali.class.php:203` (liste des constantes), `:900-913` (création des extrafields), `:993-1002` (fin de `init()`)

**Interfaces:**
- Consumes: `digiquali_get_linkable_objects()`, `digiquali_sync_linked_objects()`, `saturne_get_linked_object_usage()`.
- Produces: `digiquali_run_linked_object_backward(): array` — retourne la liste des `objectType` activés par le backward.

- [ ] **Step 1 : ajouter la politique de backward à la coquille**

Ajouter à la fin de `lib/digiquali_linked_object.lib.php` :

```php
/**
 * Enable every link that is already in use, so that the cleanup can never hide existing data
 *
 * The target set is the union of the links already enabled, the objects carrying at least one
 * element_element row towards a control or a survey, and the objects carrying at least one
 * qc_frequency value. Only missing constants are written, an explicitly disabled link that is
 * unused stays disabled.
 *
 * @return array List of object types enabled by this call
 */
function digiquali_run_linked_object_backward(): array
{
    global $conf, $db;

    $linkableObjects = digiquali_get_linkable_objects();
    $usage           = saturne_get_linked_object_usage($linkableObjects, ['qc_frequency'], ['digiquali_control', 'digiquali_survey']);

    $enabledObjectTypes = [];

    foreach ($linkableObjects as $objectType => $objectMetadata) {
        $constName = DIGIQUALI_LINKED_OBJECT_CONST_PREFIX . strtoupper($objectType);

        if (getDolGlobalInt($constName) > 0) {
            continue;
        }

        $isUsed = $usage[$objectType]['links'] > 0 || $usage[$objectType]['extrafields']['qc_frequency'] > 0;
        if (!$isUsed) {
            continue;
        }

        dolibarr_set_const($db, $constName, 1, 'integer', 0, '', $conf->entity);
        $enabledObjectTypes[] = $objectType;
    }

    return $enabledObjectTypes;
}
```

- [ ] **Step 2 : déclarer la constante de garde**

Dans `core/modules/modDigiQuali.class.php`, dans le tableau `$this->const`, juste après la ligne `DIGIQUALI_SHEET_BACKWARD_COMPATIBILITY` (ligne 203), ajouter :

```php
            $i++ => ['DIGIQUALI_LINKED_OBJECT_BACKWARD', 'integer', 0, '', 0, 'current'],
```

- [ ] **Step 3 : supprimer la création massive d'extrafields**

Supprimer intégralement le bloc suivant de `init()` (lignes 900 à 913) :

```php
        // Create extrafields during init.
        include_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $extraFields = new ExtraFields($this->db);

        $objectsMetadata = saturne_get_objects_metadata();
        foreach($objectsMetadata as $objectMetadataType => $objectMetadata) {
            $extraFields->addExtraField('qc_frequency', ...);
            if ($objectMetadataType == 'productlot') {
                $extraFields->update('control_history_link', ...);
                $extraFields->addExtraField('control_history_link', ...);
            } else {
                $extraFields->delete('control_history_link', $objectMetadata['table_element']);
            }
        }
```

La synchro de la Task 4 remplace ce bloc.

- [ ] **Step 4 : jouer le backward avant `_init()` et la synchro après**

Le constructeur fige `$this->tabs` et `$this->module_parts` avant que le backward n'ait écrit la moindre constante. L'ordre est donc contraint : écrire les constantes **avant** `$this->_init()`, reconstruire onglets et hooks **après**, sur un descripteur réinstancié.

Dans `init()`, remplacer le bloc existant :

```php
        if (getDolGlobalInt('DIGIQUALI_SHEET_LINK_PROJECT_DEFAULT') == 0) {
            dolibarr_set_const($this->db, 'DIGIQUALI_SHEET_LINK_PROJECT', 1, 'integer', 0, '', $conf->entity);
            dolibarr_set_const($this->db, 'DIGIQUALI_SHEET_LINK_PROJECT_DEFAULT', 1, 'integer', 0, '', $conf->entity);
        }

		// Permissions
		$this->remove($options);

		$result = $this->_init($sql, $options);
```

par :

```php
        if (getDolGlobalInt('DIGIQUALI_SHEET_LINK_PROJECT_DEFAULT') == 0) {
            dolibarr_set_const($this->db, 'DIGIQUALI_SHEET_LINK_PROJECT', 1, 'integer', 0, '', $conf->entity);
            dolibarr_set_const($this->db, 'DIGIQUALI_SHEET_LINK_PROJECT_DEFAULT', 1, 'integer', 0, '', $conf->entity);
        }

        require_once __DIR__ . '/../../lib/digiquali_linked_object.lib.php';

        // The constants must be written before _init(), which inserts the tabs read by the constructor.
        if (getDolGlobalInt('DIGIQUALI_LINKED_OBJECT_BACKWARD') == 0) {
            digiquali_run_linked_object_backward();
            dolibarr_set_const($this->db, 'DIGIQUALI_LINKED_OBJECT_BACKWARD', 1, 'integer', 0, '', $conf->entity);
        }

		// Permissions
		$this->remove($options);

		$result = $this->_init($sql, $options);

        // Replayed on a fresh descriptor, so that the constants written above are taken into account.
        digiquali_sync_linked_objects();
```

- [ ] **Step 5 : photographier l'état avant réactivation**

```bash
/c/wamp64/bin/php/php8.2.29/php.exe "<scratchpad>/inspect_links.php" > "<scratchpad>/before.txt"
grep -c "^qc_frequency" "<scratchpad>/before.txt"
```

Noter le nombre d'extrafields, et le plus grand indice `MAIN_MODULE_DIGIQUALI_TABS_n`.

- [ ] **Step 6 : réactiver le module depuis l'interface**

Aller sur `http://localhost/dolibarr/htdocs/admin/modules.php`, désactiver puis réactiver DigiQuali. La réactivation doit se faire par le navigateur : `insert_tabs()` a besoin du contexte web pour voir les onglets injectés par les autres modules.

- [ ] **Step 7 : vérifier le résultat**

```bash
/c/wamp64/bin/php/php8.2.29/php.exe "<scratchpad>/inspect_links.php" > "<scratchpad>/after.txt"
diff "<scratchpad>/before.txt" "<scratchpad>/after.txt"
```

Attendu :
- les extrafields `qc_frequency` ne subsistent que sur les tables des objets activés — `product`, `product_lot`, `projet`, `societe`, `socpeople`, `ticket`, `user`, `contrat`, `dolimeet_session` — soit 9 lignes au lieu de 34 ;
- `control_history_link` ne subsiste que sur `product_lot` ;
- plus aucun `qc_frequency` sur `digiquali_control`, `digiquali_question`, `digiquali_survey` ni sur les tables `digiriskdolibarr_*` et `dolicar_*` ;
- les `MAIN_MODULE_DIGIQUALI_TABS_*` s'arrêtent autour de l'indice 17 au lieu de 62 ;
- aucune constante `DIGIQUALI_SHEET_LINK_*` déjà à 1 n'est repassée à 0.

- [ ] **Step 8 : commit**

```bash
git add lib/digiquali_linked_object.lib.php core/modules/modDigiQuali.class.php
git commit -m "#2510 [Admin] rework: replace init-time link creation with backward and sync"
```

---

## Task 8 : Saturne — section « Éléments liables »

**Files:**
- Create: `core/tpl/admin/object/linked_object_view.tpl.php`

**Interfaces:**
- Consumes: variables préparées par la page appelante — `$linkableObjects`, `$enabledObjectTypes`, `$linkedObjectUsage`, `$linkedObjectExtraFieldName`, `$linkedObjectConstPrefix`.
- Produces: le rendu HTML de la section. Aucune variable retournée.

Le TPL ne fait que rendre : toute donnée est calculée par l'appelant, conformément à la règle Saturne « zéro logique métier dans un TPL ».

- [ ] **Step 1 : créer le TPL**

Créer `core/tpl/admin/object/linked_object_view.tpl.php` :

```php
<?php
/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * \file    core/tpl/admin/object/linked_object_view.tpl.php
 * \ingroup saturne
 * \brief   Template page to manage the objects a module may link to
 *
 * Expected variables, all prepared by the calling page :
 * $langs                       - Translate object
 * $user                        - Current user
 * $linkableObjects             - Result of saturne_filter_linkable_objects()
 * $enabledObjectTypes          - Result of saturne_get_enabled_linked_object_types()
 * $linkedObjectUsage           - Result of saturne_get_linked_object_usage()
 * $linkedObjectExtraFieldName  - Extrafield name whose deletion must be confirmed
 * $linkedObjectConstPrefix     - Configuration constant prefix
 */

print load_fiche_titre($langs->trans('LinkableElements'), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans('Element') . '</td>';
print '<td>' . $langs->trans('LinkedObjectUsage') . '</td>';
print '<td class="center nowrap">';
print $langs->trans('Status') . '<br>';
if ($user->admin) {
    print '<a class="reposition commonlink linked-object-toggle-all" href="' . $_SERVER['PHP_SELF'] . '?action=toggle_all_links&value=1&token=' . newToken() . '" data-confirm-message="' . dol_escape_htmltag($langs->trans('EnableAllLinksConfirm')) . '"> <u>' . $langs->trans('All') . '</u> </a>';
    print ' / ';
    print '<a class="reposition commonlink linked-object-toggle-all" href="' . $_SERVER['PHP_SELF'] . '?action=toggle_all_links&value=0&token=' . newToken() . '" data-confirm-message="' . dol_escape_htmltag($langs->trans('DisableAllLinksConfirm')) . '"> <u>' . $langs->trans('None') . '</u> </a>';
}
print '</td></tr>';

foreach ($linkableObjects as $objectType => $objectMetadata) {
    $isEnabled     = in_array($objectType, $enabledObjectTypes, true);
    $objectLabel   = $langs->trans($objectMetadata['langs']);
    $linkCount     = $linkedObjectUsage[$objectType]['links'];
    $valueCount    = $linkedObjectUsage[$objectType]['extrafields'][$linkedObjectExtraFieldName];
    $toggleUrl     = $_SERVER['PHP_SELF'] . '?action=toggle_link&objecttype=' . urlencode($objectType) . '&value=' . ($isEnabled ? 0 : 1) . '&token=' . newToken();

    print '<tr class="oddeven">';

    print '<td>';
    print img_picto('', $objectMetadata['picto'], 'class="pictofixedwidth"');
    print $objectLabel;
    print '</td>';

    print '<td>';
    if ($linkCount > 0 || $valueCount > 0) {
        print $langs->trans('LinkedObjectUsageDetail', $linkCount, $valueCount);
    } else {
        print '<span class="opacitymedium">' . $langs->trans('NoLinkedObjectUsage') . '</span>';
    }
    print '</td>';

    print '<td class="center">';
    if ($user->admin) {
        // The confirmation is only rendered when switching off would really destroy values.
        $confirmMessage = ($isEnabled && $valueCount > 0) ? $langs->trans('DisableLinkConfirm', $objectLabel, $valueCount) : '';

        print '<a class="linked-object-toggle" href="' . $toggleUrl . '" data-confirm-message="' . dol_escape_htmltag($confirmMessage) . '">';
        print $isEnabled ? img_picto($langs->trans('Enabled'), 'switch_on') : img_picto($langs->trans('Disabled'), 'switch_off');
        print '</a>';
    } else {
        print $isEnabled ? img_picto($langs->trans('Enabled'), 'switch_on') : img_picto($langs->trans('Disabled'), 'switch_off');
    }
    print '</td>';

    print '</tr>';
}

print '</table>';

if ($user->admin) {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction">';
    print '<a class="butAction linked-object-toggle" href="' . $_SERVER['PHP_SELF'] . '?action=clean_unused_links&token=' . newToken() . '" data-confirm-message="' . dol_escape_htmltag($langs->trans('CleanUnusedLinksConfirm')) . '">' . $langs->trans('CleanUnusedLinks') . '</a>';
    print '</div>';
    print '</div>';
}
```

- [ ] **Step 2 : vérifier PHPCS**

```bash
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml core/tpl/admin/object/linked_object_view.tpl.php
```

Le TPL n'est pas encore inclus par une page : le rendu est vérifié en Task 10.

- [ ] **Step 3 : commit**

```bash
git add core/tpl/admin/object/linked_object_view.tpl.php
git commit -m "#2510 [LinkedObject] add: linkable elements admin section template"
```

---

## Task 9 : Saturne — confirmation JavaScript

**Files:**
- Create: `js/modules/linkedObject.js`

**Interfaces:**
- Consumes: les attributs `data-confirm-message` rendus par le TPL de la Task 8.
- Produces: `window.saturne.linkedObject` avec `init()`, `event()` et `confirmToggle()`.

`js/modules/*.js` est concaténé automatiquement par `gulpfile-shared.js`, il n'y a rien à déclarer. `saturne.js` appelle `init()` de chaque `window.saturne.*` au `document.ready` : ne jamais appeler `init()` en fin de fichier.

- [ ] **Step 1 : créer le module JS**

Créer `js/modules/linkedObject.js` :

```javascript
/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    js/modules/linkedObject.js
 * \ingroup saturne
 * \brief   JavaScript linkedObject file for module Saturne
 */

/**
 * Init linkedObject JS
 *
 * @memberof Saturne_Framework_Linkedobject
 */
window.saturne.linkedObject = {};

/**
 * LinkedObject init
 *
 * @memberof Saturne_Framework_Linkedobject
 */
window.saturne.linkedObject.init = function() {
    window.saturne.linkedObject.event();
};

/**
 * LinkedObject event
 *
 * @memberof Saturne_Framework_Linkedobject
 */
window.saturne.linkedObject.event = function() {
    $(document).on('click', '.linked-object-toggle', window.saturne.linkedObject.confirmToggle);
    $(document).on('click', '.linked-object-toggle-all', window.saturne.linkedObject.confirmToggle);
};

/**
 * Ask for confirmation before a destructive link change
 *
 * @memberof Saturne_Framework_Linkedobject
 *
 * @param {Object} event Triggered event
 *
 * @returns {boolean} False when the user cancels, true otherwise
 */
window.saturne.linkedObject.confirmToggle = function(event) {
    var confirmMessage = $(this).attr('data-confirm-message');

    if (!confirmMessage) {
        return true;
    }

    if (!window.confirm(confirmMessage)) {
        event.preventDefault();
        return false;
    }

    return true;
};
```

- [ ] **Step 2 : valider avec JSHint**

```bash
jshint js/modules/linkedObject.js
```

Attendu : aucune sortie. Si `jshint` n'est pas installé globalement : `npx jshint js/modules/linkedObject.js`.

- [ ] **Step 3 : commit**

```bash
git add js/modules/linkedObject.js
git commit -m "#2510 [JS] add: confirmation before a destructive link change"
```

Ne pas commiter `js/saturne.min.js` : la CI `build-assets` le régénère.

---

## Task 10 : DigiQuali — actions, intégration et traductions

**Files:**
- Modify: `admin/sheet.php:73` (contexte de hook), `:88` (bloc Actions), `:201-218` (tableau de constantes)
- Modify: `langs/fr_FR/digiquali.lang`
- Modify: `langs/en_US/digiquali.lang`

**Interfaces:**
- Consumes: `digiquali_get_linkable_objects()`, `digiquali_get_enabled_linked_object_types()`, `digiquali_sync_linked_objects()`, `saturne_get_linked_object_usage()`, le TPL de la Task 8.
- Produces: les actions `toggle_link`, `toggle_all_links` et `clean_unused_links` sur la page de configuration Modèle.

`clean_unused_links` **resynchronise sur l'état des constantes** : elle ne réactive rien. Le backward, lui, ne tourne qu'une fois à l'activation du module.

- [ ] **Step 1 : charger la coquille sur la page**

Dans `admin/sheet.php`, à la suite du bloc de `require_once` des libs DigiQuali (après la ligne `require_once __DIR__ . '/../class/sheet.class.php';`), ajouter :

```php
require_once __DIR__ . '/../lib/digiquali_linked_object.lib.php';
```

- [ ] **Step 2 : ajouter les actions**

Dans `admin/sheet.php`, juste après la ligne `require DOL_DOCUMENT_ROOT.'/core/actions_extrafields.inc.php';`, insérer :

```php
// Linked objects actions.
if (in_array($action, ['toggle_link', 'toggle_all_links', 'clean_unused_links']) && $user->admin) {
    $db->begin();

    if ($action == 'toggle_link') {
        $objectType = GETPOST('objecttype', 'aZ09');
        $value      = GETPOSTINT('value');

        $linkableObjects = digiquali_get_linkable_objects();
        if (isset($linkableObjects[$objectType])) {
            dolibarr_set_const($db, DIGIQUALI_LINKED_OBJECT_CONST_PREFIX . strtoupper($objectType), $value, 'integer', 0, '', $conf->entity);
        }
    } elseif ($action == 'toggle_all_links') {
        $value = GETPOSTINT('value');

        foreach (array_keys(digiquali_get_linkable_objects()) as $objectType) {
            dolibarr_set_const($db, DIGIQUALI_LINKED_OBJECT_CONST_PREFIX . strtoupper($objectType), $value, 'integer', 0, '', $conf->entity);
        }
    }

    $report = digiquali_sync_linked_objects();

    if ($report['errors'] > 0) {
        $db->rollback();
        setEventMessages($langs->trans('LinkedObjectSyncError'), [], 'errors');
    } else {
        $db->commit();
        setEventMessage($langs->trans('LinkedObjectSyncDone', $report['tabs'], $report['hooks'], count($report['added']), count($report['deleted'])));
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
```

`clean_unused_links` n'a pas de branche propre : elle se contente de la synchro finale, ce qui est exactement sa définition.

- [ ] **Step 3 : retirer les liens du tableau de configuration générique**

Dans `admin/sheet.php`, supprimer les trois lignes suivantes :

```php
//@todo add only wanted keys
$objectsMetadata                  = saturne_get_objects_metadata();
$constArray[$moduleNameLowerCase] = array_merge($constArray[$moduleNameLowerCase], $objectsMetadata);
```

Le tableau « Config » ne conserve ainsi que `UniqueLinkedElement` et `SheetLinkedObjectSelect2`.

- [ ] **Step 4 : préparer les données et inclure le TPL**

Dans `admin/sheet.php`, juste après la ligne `require_once __DIR__ . '/../../saturne/core/tpl/admin/object/object_const_view.tpl.php';`, insérer :

```php
$linkableObjects            = digiquali_get_linkable_objects();
$enabledObjectTypes         = digiquali_get_enabled_linked_object_types();
$linkedObjectExtraFieldName = 'qc_frequency';
$linkedObjectUsage          = saturne_get_linked_object_usage($linkableObjects, [$linkedObjectExtraFieldName], ['digiquali_control', 'digiquali_survey']);
$linkedObjectConstPrefix    = DIGIQUALI_LINKED_OBJECT_CONST_PREFIX;

require_once __DIR__ . '/../../saturne/core/tpl/admin/object/linked_object_view.tpl.php';
```

- [ ] **Step 5 : ajouter les traductions françaises**

Dans `langs/fr_FR/digiquali.lang`, à la suite du bloc des clés `Link*` (après `LinkMouvementDescription`), ajouter :

```
LinkableElements                               = Éléments liables
LinkedObjectUsage                              = Utilisation
LinkedObjectUsageDetail                        = %d lien(s), %d fréquence(s) de contrôle renseignée(s)
NoLinkedObjectUsage                            = Aucune utilisation
DisableLinkConfirm                             = Le lien avec « %s » va être désactivé. %d valeur(s) de fréquence de contrôle seront définitivement supprimées. Confirmer ?
EnableAllLinksConfirm                          = Tous les liens vont être activés : les champs complémentaires, onglets et hooks seront créés sur tous les objets disponibles. Confirmer ?
DisableAllLinksConfirm                         = Tous les liens vont être désactivés : les champs complémentaires seront supprimés et leurs valeurs perdues. Confirmer ?
CleanUnusedLinks                               = Nettoyer les liens inutilisés
CleanUnusedLinksConfirm                        = Les champs complémentaires, onglets et hooks des liens désactivés vont être supprimés. Confirmer ?
LinkedObjectSyncDone                           = Synchronisation effectuée : %d onglet(s), %d hook(s), %d champ(s) complémentaire(s) ajouté(s), %d supprimé(s)
LinkedObjectSyncError                          = La synchronisation des liens a échoué, aucune modification n'a été enregistrée
```

Respecter l'alignement en colonne du fichier : la valeur commence à la colonne 48.

- [ ] **Step 6 : ajouter les traductions anglaises**

Dans `langs/en_US/digiquali.lang`, au même emplacement relatif, ajouter :

```
LinkableElements                               = Linkable elements
LinkedObjectUsage                              = Usage
LinkedObjectUsageDetail                        = %d link(s), %d control frequency value(s)
NoLinkedObjectUsage                            = Not used
DisableLinkConfirm                             = The link with "%s" is about to be disabled. %d control frequency value(s) will be permanently deleted. Confirm?
EnableAllLinksConfirm                          = Every link is about to be enabled: extrafields, tabs and hooks will be created on all available objects. Confirm?
DisableAllLinksConfirm                         = Every link is about to be disabled: extrafields will be deleted and their values lost. Confirm?
CleanUnusedLinks                               = Clean unused links
CleanUnusedLinksConfirm                        = Extrafields, tabs and hooks of the disabled links are about to be deleted. Confirm?
LinkedObjectSyncDone                           = Synchronisation done: %d tab(s), %d hook(s), %d extrafield(s) added, %d deleted
LinkedObjectSyncError                          = Link synchronisation failed, no change has been saved
```

- [ ] **Step 7 : vérifier le rendu de la page**

Ouvrir `http://localhost/dolibarr/htdocs/custom/digiquali/admin/sheet.php`.

Attendu :
- la section « Éléments liables » apparaît avec une ligne par objet liable, aucune ligne `digiquali_*` ;
- la colonne Utilisation affiche des compteurs cohérents avec `inspect_links.php` ;
- le tableau « Config » ne contient plus que deux lignes ;
- aucun warning dans `/c/wamp64/logs/php_error.log`.

- [ ] **Step 8 : vérifier un basculement non destructif**

Activer le lien avec les factures depuis la page.

Attendu : message de synthèse, puis dans `inspect_links.php` un `qc_frequency` sur `facture` et deux nouveaux onglets `invoice:+control` / `invoice:+survey`. La fiche d'une facture existante affiche alors les onglets Contrôles et Audits.

- [ ] **Step 9 : vérifier un basculement destructif**

Désactiver le lien avec les produits, qui porte 3 valeurs.

Attendu : une boîte de confirmation annonçant 3 valeurs supprimées ; après validation, plus de `qc_frequency` sur `product` et plus d'onglets DigiQuali sur la fiche produit. Réactiver ensuite le lien pour restaurer l'état de travail.

- [ ] **Step 10 : vérifier PHPCS**

```bash
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml admin/sheet.php lib/digiquali_linked_object.lib.php
```

- [ ] **Step 11 : commit**

```bash
git add admin/sheet.php langs/fr_FR/digiquali.lang langs/en_US/digiquali.lang
git commit -m "#2510 [Admin] add: linkable elements management on sheet config page"
```

---

## Task 11 : vérification de bout en bout et pull requests

**Files:** aucun fichier de production modifié.

**Interfaces:**
- Consumes: l'ensemble des tâches précédentes.
- Produces: deux pull requests liées.

- [ ] **Step 1 : trancher la question du répertoire servi par WAMP**

Les worktrees ne sont pas servis par Apache. Deux options, à arbitrer avec l'utilisateur avant de commencer les tests navigateur :

- copier les fichiers modifiés des worktrees vers `htdocs/custom/saturne` et `htdocs/custom/digiquali` le temps des tests, puis restaurer par `git checkout -- .` dans chaque dépôt ;
- ou basculer les deux checkouts principaux sur `rework/2510-linked-elements-admin`, après s'être assuré qu'aucune autre session ne travaille dessus.

La seconde option est plus simple mais déplace la branche courante sous une éventuelle session concurrente. Ne pas la choisir sans validation explicite.

- [ ] **Step 2 : rejouer la suite de tests unitaires Saturne**

Depuis le worktree Saturne :

```bash
vendor/bin/phpunit --configuration tests/phpunit/phpunittest.xml --testdox
```

Attendu : toute la suite passe, y compris `LinkedObjectLibTest` et les deux classes préexistantes.

- [ ] **Step 3 : rejouer PHPCS et JSHint sur les deux dépôts**

```bash
# Saturne
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml lib/linked_object.lib.php lib/object.lib.php lib/saturne_functions.lib.php core/tpl/admin/object/linked_object_view.tpl.php tests/phpunit/unit/LinkedObjectLibTest.php
jshint js/modules/linkedObject.js

# DigiQuali
~/.composer/vendor/bin/phpcs --standard=.phpcs.xml admin/sheet.php lib/digiquali_linked_object.lib.php core/modules/modDigiQuali.class.php
```

Attendu : aucune erreur sur les fichiers touchés. Les erreurs préexistantes sur d'autres fichiers ne sont pas du ressort de cette PR.

- [ ] **Step 4 : vérification navigateur avec Playwright**

Depuis `C:\...\Temp\digirisk-test\`, écrire un script qui se connecte avec `DOLI_LOGIN` / `DOLI_PWD` puis capture :

1. `custom/digiquali/admin/sheet.php` — la section « Éléments liables » complète ;
2. la fiche d'un produit — les onglets Contrôles et Audits sont présents ;
3. la fiche d'une facture, lien désactivé — les onglets sont absents ;
4. `custom/digiquali/view/sheet/sheet_card.php` sur un modèle existant — seuls les objets activés sont proposés.

Attendu : les quatre captures confirment le comportement. Les joindre en commentaire de l'issue #2510, en poussant les PNG sur une branche du fork public et en référençant l'URL brute.

- [ ] **Step 5 : contrôle de non-régression sur les données existantes**

Ouvrir un contrôle rattaché à un contrat et un contrôle rattaché à un projet.

Attendu : l'objet lié reste affiché sur la fiche et dans le PDF généré. C'est la vérification directe de la promesse du backward.

- [ ] **Step 6 : ouvrir la pull request Saturne**

Depuis le worktree Saturne :

```bash
git push -u nicolas-eoxia rework/2510-linked-elements-admin
gh pr create --repo Evarisk/Saturne --base develop --head nicolas-eoxia:rework/2510-linked-elements-admin \
  --title "#2510 [LinkedObject] add: generic linkable objects management" \
  --body "..."
```

Le corps décrit les quatre fonctions ajoutées, le TPL, le module JS, le correctif `alias_of`, et précise que la PR DigiQuali associée en dépend.

- [ ] **Step 7 : ouvrir la pull request DigiQuali**

Depuis le worktree DigiQuali :

```bash
git push nicolas-eoxia rework/2510-linked-elements-admin
gh pr create --repo Evarisk/digiquali --base develop --head nicolas-eoxia:rework/2510-linked-elements-admin \
  --title "#2510 [Admin] rework: manage linkable elements from the sheet config page" \
  --body "Closes #2510 ..."
```

Le corps rappelle les volumes avant/après, renvoie vers la PR Saturne, et signale qu'elle doit être mergée en premier.

- [ ] **Step 8 : signaler l'ordre de merge**

Commenter la PR DigiQuali pour indiquer explicitement qu'elle ne doit pas être mergée avant la PR Saturne, sous peine de fatal error sur `saturne_filter_linkable_objects()`.

---

## Auto-revue du plan

**Couverture de la spec**

| Exigence de la spec | Tâche |
|---|---|
| Toggle pilote `qc_frequency` | 4, 6, 7, 10 |
| Toggle pilote `control_history_link` | 4, 6 (définition restreinte à `productlot`) |
| Toggle pilote les onglets | 5, 6, 7 |
| Toggle pilote les hooks | 5, 6, 7 |
| Backward = consts ∪ usage réel | 7 |
| Confirmation avant perte de données | 8, 9, 10 |
| Mécanique générique dans Saturne | 1, 3, 4, 5, 8, 9 |
| Coquille fine DigiQuali | 6, 7, 10 |
| Section dédiée avec colonne Utilisation | 8, 10 |
| Raccourcis Tout / Aucun avec confirmation | 8, 9, 10 |
| Bouton « Nettoyer les liens inutilisés » | 8, 10 |
| Déclencheur action de page sans AJAX | 10 |
| Exclusion des objets DigiQuali | 1, 6 |
| Déduplication par `table_element` | 1 |
| Correctif `alias_of` sur `project_task` | 2 |
| Transaction et rollback | 10 |
| Idempotence | 4, 5, 7 |
| Traductions fr_FR et en_US | 10 |
| Vérification par script SQL et Playwright | 7, 10, 11 |

Aucune exigence sans tâche.

**Cohérence des signatures** — `saturne_filter_linkable_objects`, `saturne_get_enabled_linked_object_types`, `saturne_get_linked_object_usage`, `saturne_get_existing_extrafields`, `saturne_sync_linked_object_extrafields`, `saturne_refresh_module_registrations` sont définies en Tasks 1, 3, 4 et 5 et appelées avec le même nom et le même ordre d'arguments en Tasks 6, 7 et 10. Les clés du rapport (`added`, `deleted`, `errors`, `tabs`, `hooks`) sont identiques entre la production en Tasks 4 et 5, l'agrégation en Task 6 et la consommation en Task 10. Les clés de l'usage (`links`, `extrafields`) sont identiques entre la Task 3, le TPL de la Task 8 et le backward de la Task 7.
