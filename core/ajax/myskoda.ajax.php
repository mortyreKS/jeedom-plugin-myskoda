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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect()) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

    // Plugin désactivé : le core ne charge plus sa classe, et toute action s'arrêterait sur une erreur PHP sans message.
    // Une page du plugin restée ouverte (journal de bord) reçoit à la place une explication.
    if (config::byKey('active', 'myskoda', 0) != 1) {
        throw new Exception(__('Le plugin MySkoda est désactivé. Réactivez-le dans le menu Plugins, Gestion des plugins, MySkoda.', __FILE__));
    }

    if (init('action') == 'addVehicle') {
        if (!isConnect('admin')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        $eqLogic = myskoda::createFromVin(init('vin'));
        ajax::success(array('id' => $eqLogic->getId(), 'name' => $eqLogic->getName()));
    }

    if (init('action') == 'searchAddress') {
        // Recherche d'adresse ouverte à qui peut modifier les lieux du véhicule (voir plus bas)
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('x')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        ajax::success(myskoda::searchAddress(init('address')));
    }

    if (init('action') == 'findAddress') {
        // Recherche d'adresse ouverte à qui peut modifier les lieux du véhicule (voir plus bas)
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('x')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        ajax::success(myskoda::findAddress(init('lat'), init('lng')));
    }

    if (init('action') == 'isAdmin') {
        ajax::success(isConnect('admin'));
    }

    if (init('action') == 'getDiagnostic') {
        if (!isConnect('admin')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        ajax::success(myskodaDiagnostic::generate());
    }

    if (init('action') == 'getVersion') {
        ajax::success(myskoda::getVersionLabel());
    }

    // Réglages d'affichage du journal de bord : unités (« imperial » ou « metric ») et signe de la monnaie
    if (init('action') == 'getUnits') {
        ajax::success(array('units' => myskoda::isImperial() ? 'imperial' : 'metric', 'currency' => myskoda::currency()));
    }

    if (init('action') == 'getVehicles') {
        $vehicles = array();
        foreach (eqLogic::byType('myskoda', true) as $eqLogic) {
            if ($eqLogic->hasRight('r')) {
                $vehicles[] = array('id' => $eqLogic->getId(), 'name' => $eqLogic->getHumanName(), 'kind' => $eqLogic->getKind(), 'energies' => $eqLogic->getEnergies(), 'real' => ($eqLogic->getConfiguration('vin') != ''),
                    // Nom court et image du véhicule, pour le reconnaître sur chaque fiche de trajet ou de recharge
                    'short' => $eqLogic->getName(), 'image' => (strpos((string) $eqLogic->getImage(), 'https://') === 0) ? $eqLogic->getImage() : '',
                    'status' => $eqLogic->panelStatus());
            }
        }
        // Les véhicules réels d'abord : un équipement sans numéro de série n'est pas interrogé
        usort($vehicles, function ($_a, $_b) {
            return (int) $_b['real'] - (int) $_a['real'];
        });
        ajax::success($vehicles);
    }

    if (in_array(init('action'), array('getMap', 'renamePlace', 'deletePlace', 'addHomePlace', 'addPlace', 'movePlace', 'colorPlace', 'reorderPlace', 'rankPlace'))) {
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('r')) {
            throw new Exception(__('Véhicule inconnu', __FILE__));
        }
        $eqLogic->setEnergy(init('energy'));
        // Les lieux sont de l'usage, pas de la configuration : un administrateur ou un utilisateur les modifie, un
        // utilisateur limité seulement s'il a le droit d'action sur ce véhicule (celui qui lance déjà charge et climatisation)
        if (init('action') != 'getMap' && !$eqLogic->hasRight('x')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        if (init('action') == 'renamePlace') {
            // « favorite » et « radius » absents de la demande : le lieu garde son état
            $eqLogic->renamePlace((int) init('index'), init('name'), init('price'),
                (init('favorite', '') === '') ? null : (init('favorite') == 1), (init('favorite', '') === '') ? null : init('radius'),
                (init('withAddress') == 1) ? init('address') : null);
        }
        if (init('action') == 'addPlace') {
            $eqLogic->addPlace(init('lat'), init('lng'), init('name'), init('address'), init('radius'));
        }
        if (init('action') == 'colorPlace') {
            $eqLogic->colorPlace((int) init('index'), init('color'));
        }
        if (init('action') == 'reorderPlace') {
            $eqLogic->reorderPlace((int) init('index'), (int) init('direction'));
        }
        if (init('action') == 'rankPlace') {
            $eqLogic->rankPlace((int) init('index'), (int) init('rank'));
        }
        if (init('action') == 'movePlace') {
            $eqLogic->movePlace((int) init('index'), init('lat'), init('lng'), (init('withAddress') == 1) ? init('address') : null);
        }
        if (init('action') == 'deletePlace') {
            $eqLogic->deletePlace((int) init('index'));
        }
        if (init('action') == 'addHomePlace') {
            $eqLogic->addHomePlace();
        }
        $map = $eqLogic->getMap();
        $map['canEdit'] = $eqLogic->hasRight('x');
        $map['price'] = $eqLogic->getDefaultPrice();
        ajax::success($map);
    }

    if (init('action') == 'getDay') {
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('r')) {
            throw new Exception(__('Véhicule inconnu', __FILE__));
        }
        $eqLogic->setEnergy(init('energy'));
        if (!is_string(init('date')) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', init('date'))) {
            throw new Exception(__('Date invalide', __FILE__));
        }
        ajax::success($eqLogic->getDay(date('Y-m-d', strtotime(init('date')))));
    }

    if (init('action') == 'getPeriod') {
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('r')) {
            throw new Exception(__('Véhicule inconnu', __FILE__));
        }
        $eqLogic->setEnergy(init('energy'));
        // Une date illisible vaudrait 1970 et ferait relire tout l'historique
        foreach (array('start', 'end') as $bound) {
            if (!is_string(init($bound)) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', init($bound))) {
                throw new Exception(__('Date invalide', __FILE__));
            }
        }
        $start = date('Y-m-d 00:00:00', strtotime(init('start')));
        $end = date('Y-m-d 23:59:59', strtotime(init('end')));
        // Chaque trajet coûte plusieurs lectures d'historique : la période est plafonnée à un peu plus d'un an
        if (strtotime($end) - strtotime($start) > 400 * 86400) {
            $start = date('Y-m-d 00:00:00', strtotime($end) - 400 * 86400);
        }
        ajax::success(array(
            'capacity' => $eqLogic->getCapacity(),
            'price' => $eqLogic->getDefaultPrice(),
            'trips' => $eqLogic->getTrips($start, $end),
            'charges' => $eqLogic->getCharges($start, $end),
            'notHistorized' => $eqLogic->getMissingHistory(),
        ));
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}
catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
