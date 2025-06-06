<?php
/* Copyright (C) 2025 EVARISK <technique@evarisk.com>
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
 * \file    lib/digiquali_digiqualielement.lib.php
 * \ingroup digiquali
 * \brief   Library files with common functions for digiquali element
 */

/**
 * Prepare digiquali element pages header
 *
 * @param  DigiQualiElement $object DigiQuali element
 * @return array            $head   Array of tabs
 * @throws Exception
 */
function digiqualielement_prepare_head(DigiQualiElement $object): array
{
    $moreparam['specialName'] = 'Processus';
    $moreparam['handlePhoto'] = true;
    return saturne_object_prepare_head($object, [], $moreparam);
}
