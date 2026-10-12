<?php
/* Copyright (C) 2022 EVARISK <technique@evarisk.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       core/modules/digiquali/controlequipment/mod_control_equipment_standard.php
 * \ingroup     digiquali
 *	\brief      File containing class for numbering module Standard
 */

// Load Saturne libraries.
dol_include_once('/saturne/core/modules/saturne/modules_saturne.php');

/**
 * 	Class to manage controlequipment numbering rules Standard
 */
class mod_control_equipment_standard extends ModeleNumRefSaturne
{
	/**
	 * @var string Numbering module ref prefix
	 */
	public string $prefix = 'FCE';

	/**
	 * @var string model name
	 */
	public string $name = 'Fornjot';
}
