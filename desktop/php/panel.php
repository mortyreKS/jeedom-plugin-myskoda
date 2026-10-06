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

if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<div id="div_myskodaJournal"<?php if (isConnect('admin')) { echo ' data-equipments="index.php?v=d&m=myskoda&p=myskoda" data-configuration="index.php?v=d&p=plugin&id=myskoda"'; } ?>></div>

<?php
include_file('desktop', 'journal', 'js', 'myskoda');
include_file('desktop', 'panel', 'js', 'myskoda');
?>
