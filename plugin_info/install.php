<?php
/* This file is part of the MySkoda plugin for Jeedom.
 * Copyright (C) 2026 mortyre
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
// La classe est chargée explicitement : le core ne la charge de lui-même que pour un plugin actif, or ces fonctions
// sont aussi appelées pendant une mise à jour ou une désactivation
require_once dirname(__FILE__) . '/../core/class/myskoda.class.php';

// Les réglages par défaut (rythmes de lecture) sont dans core/config/myskoda.config.ini : le core les applique
// lui-même, il n'y a rien à écrire à l'installation.

// Fonction exécutée automatiquement après la mise à jour du plugin : les véhicules sont relus dès la minute suivante
function myskoda_update() {
  myskoda::resetAllSchedules();
}

// Fonction exécutée automatiquement à la désactivation et à la suppression du plugin.
// À la suppression, le core retire lui-même les équipements (avec leurs commandes et leur historique) et toute la
// configuration du plugin, clé API comprise. Il reste à retirer ce que le plugin a laissé ailleurs.
function myskoda_remove() {
  message::removeAll('myskoda');
  config::remove('log::level::myskoda', 'core');
}
