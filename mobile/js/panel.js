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

// Appelée par l'application mobile à l'ouverture du panneau (nom imposé par le core : init + Myskoda + Panel).
// Le journal de bord est commun au desktop et au mobile : voir desktop/js/journal.js
function initMyskodaPanel() {
  if (typeof panel == 'function') {
    panel(false)
  }
  myskodaJournal.mount(document.getElementById('div_myskodaJournal'), { version: 'mobile' })
}
