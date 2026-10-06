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

    if (init('action') == 'addVehicle') {
        if (!isConnect('admin')) {
            throw new Exception(__('401 - Accès non autorisé', __FILE__));
        }
        $eqLogic = myskoda::createFromVin(init('vin'));
        ajax::success(array('id' => $eqLogic->getId(), 'name' => $eqLogic->getName()));
    }

    if (init('action') == 'getVersion') {
        ajax::success(myskoda::getVersionLabel());
    }

    if (init('action') == 'getVehicles') {
        $vehicles = array();
        foreach (eqLogic::byType('myskoda', true) as $eqLogic) {
            if ($eqLogic->hasRight('r')) {
                $vehicles[] = array('id' => $eqLogic->getId(), 'name' => $eqLogic->getHumanName(), 'kind' => $eqLogic->getKind(), 'energies' => $eqLogic->getEnergies(), 'real' => ($eqLogic->getConfiguration('vin') != ''));
            }
        }
        // Les véhicules réels d'abord : un équipement sans numéro de série n'est pas interrogé
        usort($vehicles, function ($_a, $_b) {
            return (int) $_b['real'] - (int) $_a['real'];
        });
        ajax::success($vehicles);
    }

    if (init('action') == 'getMap' || init('action') == 'renamePlace') {
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('r')) {
            throw new Exception(__('Véhicule inconnu', __FILE__));
        }
        $eqLogic->setEnergy(init('energy'));
        if (init('action') == 'renamePlace') {
            if (!isConnect('admin')) {
                throw new Exception(__('401 - Accès non autorisé', __FILE__));
            }
            $eqLogic->renamePlace((int) init('index'), init('name'), init('price'));
        }
        $map = $eqLogic->getMap();
        $map['canEdit'] = isConnect('admin');
        $map['price'] = $eqLogic->getDefaultPrice();
        ajax::success($map);
    }

    if (init('action') == 'getDay') {
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('r')) {
            throw new Exception(__('Véhicule inconnu', __FILE__));
        }
        $eqLogic->setEnergy(init('energy'));
        ajax::success($eqLogic->getDay(date('Y-m-d', strtotime(init('date')))));
    }

    if (init('action') == 'getPeriod') {
        $eqLogic = myskoda::byId(init('id'));
        if (!is_object($eqLogic) || $eqLogic->getEqType_name() != 'myskoda' || !$eqLogic->hasRight('r')) {
            throw new Exception(__('Véhicule inconnu', __FILE__));
        }
        $eqLogic->setEnergy(init('energy'));
        $start = date('Y-m-d 00:00:00', strtotime(init('start')));
        $end = date('Y-m-d 23:59:59', strtotime(init('end')));
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
