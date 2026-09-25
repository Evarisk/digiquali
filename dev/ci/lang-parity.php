<?php
/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
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
 * \file    dev/ci/lang-parity.php
 * \ingroup digiquali
 * \brief   Fail when a key exists in en_US but not in fr_FR.
 *
 * Dolibarr falls back to en_US when the current language file has no entry for a key. A key
 * that only exists in en_US therefore stays in English, and since a language file is loaded
 * for the whole request, the English wording leaks into every module that translates the same
 * generic key. The consumer always loses that race, so it has to be fixed at the source.
 *
 * The other direction - a French key with no English counterpart - is only a missing
 * translation, never a bug: it is counted and printed, never fatal.
 *
 * Every .lang file of langs/en_US is compared with its fr_FR counterpart, so a file added
 * later is covered without touching this script. A module that ships French only has nothing
 * to compare and passes.
 *
 * Usage: php dev/ci/lang-parity.php
 */

$moduleRoot   = realpath(__DIR__ . '/../..');
$referenceDir = $moduleRoot . '/langs/en_US';
$targetDir    = $moduleRoot . '/langs/fr_FR';

/**
 * Read the keys of a Dolibarr language file.
 *
 * The parser of Dolibarr accepts spaces around the equal sign, and some modules use them for
 * alignment: matching `^KEY=` alone would miss almost every line.
 *
 * @param  string   $file Absolute path of the .lang file
 * @return string[]       Keys, in the order they appear
 */
function langKeys(string $file): array
{
    if (!is_readable($file)) {
        return [];
    }

    $keys = [];
    foreach (file($file) as $line) {
        if (preg_match('/^([A-Za-z0-9_\/-]+)[ \t]*=/', $line, $matches)) {
            $keys[] = $matches[1];
        }
    }

    return $keys;
}

if (!is_dir($referenceDir)) {
    echo "No langs/en_US directory: this module ships French only, nothing to compare.\n";
    exit(0);
}

$files = glob($referenceDir . '/*.lang');
if (empty($files)) {
    echo "No .lang file in langs/en_US, nothing to compare.\n";
    exit(0);
}

$status = 0;
foreach ($files as $reference) {
    $name   = basename($reference);
    $target = $targetDir . '/' . $name;

    $referenceKeys = langKeys($reference);
    $targetKeys    = langKeys($target);

    if (!is_readable($target)) {
        printf("%s: no fr_FR counterpart - every one of its %d key(s) stays in English.\n", $name, count($referenceKeys));
        $status = 1;
        continue;
    }

    $missing      = array_values(array_diff($referenceKeys, $targetKeys));
    $untranslated = array_values(array_diff($targetKeys, $referenceKeys));

    printf("%s - en_US: %d keys, fr_FR: %d keys\n", $name, count($referenceKeys), count($targetKeys));

    if (!empty($untranslated)) {
        printf("  %d key(s) missing from en_US - untranslated, not fatal: %s\n", count($untranslated), implode(', ', array_slice($untranslated, 0, 10)));
    }

    if (empty($missing)) {
        continue;
    }

    printf("\n  %d key(s) exist in en_US but not in fr_FR:\n", count($missing));
    foreach ($missing as $key) {
        echo '    ' . $key . "\n";
    }
    printf("\n  Add them to langs/fr_FR/%s, or remove them from en_US when they are dead.\n\n", $name);
    $status = 1;
}

if ($status === 0) {
    echo "Every en_US key has a fr_FR counterpart.\n";
}

exit($status);
