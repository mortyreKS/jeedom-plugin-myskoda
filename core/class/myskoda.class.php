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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/myskodaApi.class.php';
require_once __DIR__  . '/myskodaDiagnostic.class.php';

class myskoda extends eqLogic {
  /*     * *************************Attributs****************************** */

  public static $_encryptConfigKey = array('apikey', 'spin');

  // Intervalle de lecture par défaut et minimal, en minutes (quota de l'API : 20 requêtes par heure et par véhicule)
  const DEFAULT_INTERVAL = 10;
  const MIN_INTERVAL = 3;
  // Intervalle par défaut quand le véhicule roule ou charge, en minutes, et durée pendant laquelle ce rythme est gardé après l'arrêt, en secondes
  const DEFAULT_ACTIVE_INTERVAL = 5;
  const ACTIVE_HOLD = 600;
  // Requêtes gardées en réserve pour les commandes : en dessous, la lecture automatique attend le renouvellement du quota
  const QUOTA_RESERVE = 3;
  // Délai entre une commande et la relecture de l'état, en secondes
  const REFRESH_AFTER_COMMAND = 60;
  // Pause après une erreur de clé ou de VIN, en secondes
  const PAUSE_AFTER_DENIED = 3600;
  // Pause par défaut quand le serveur demande d'attendre sans dire combien, en secondes
  const DEFAULT_RETRY_AFTER = 300;

  // Commandes info : identifiant => nom, sous-type, unité, historisée, visible
  private static $_infos = array(
    'name' => array('Nom', 'string', '', 0, 1),
    'license_plate' => array('Immatriculation', 'string', '', 0, 1),
    'car_type' => array('Motorisation', 'string', '', 0, 1),
    'image' => array('Image', 'string', '', 0, 1),
    'mileage' => array('Kilométrage', 'numeric', 'km', 1, 1),
    'battery_level' => array('Batterie', 'numeric', '%', 1, 1),
    'range' => array('Autonomie électrique', 'numeric', 'km', 1, 1),
    'charging' => array('En charge', 'binary', '', 1, 1),
    'charging_state' => array('État de la charge', 'string', '', 0, 1),
    'charge_power' => array('Puissance de charge', 'numeric', 'kW', 1, 1),
    'charge_rate' => array('Vitesse de charge', 'numeric', 'km/h', 0, 1),
    'charge_time_left' => array('Temps de charge restant', 'numeric', 'min', 0, 1),
    'fully_charged_at' => array('Fin de charge prévue', 'string', '', 0, 1),
    'charge_type' => array('Type de charge', 'string', '', 0, 1),
    'plug_connected' => array('Câble branché', 'binary', '', 1, 1),
    'plug_locked' => array('Câble verrouillé', 'binary', '', 0, 0),
    'charge_limit' => array('Limite de charge', 'numeric', '%', 0, 1),
    'charge_mode' => array('Mode de charge', 'string', '', 0, 0),
    'battery_care' => array('Soin de la batterie', 'binary', '', 0, 0),
    'max_charge_current' => array('Courant de charge', 'string', '', 0, 0),
    'max_charge_current_ampere' => array('Courant de charge maximal', 'numeric', 'A', 0, 0),
    'in_saved_location' => array('Dans un lieu enregistré', 'binary', '', 0, 0),
    'charge_profile' => array('Point de recharge actuel', 'string', '', 0, 1),
    'charge_profile_target' => array('Point de recharge : limite', 'numeric', '%', 0, 0),
    'charge_profile_min' => array('Point de recharge : charge minimale', 'numeric', '%', 0, 0),
    'charge_profile_next' => array('Point de recharge : prochaine charge', 'string', '', 0, 0),
    'climate_on' => array('Climatisation active', 'binary', '', 1, 1),
    'climate_state' => array('État de la climatisation', 'string', '', 0, 1),
    'target_temperature' => array('Température de consigne', 'numeric', '°C', 0, 1),
    'climate_ready_at' => array('Température atteinte prévue', 'string', '', 0, 1),
    'climate_without_power' => array('Climatisation sans branchement autorisée', 'binary', '', 0, 0),
    'window_heating_front' => array('Dégivrage avant', 'binary', '', 0, 0),
    'window_heating_rear' => array('Dégivrage arrière', 'binary', '', 0, 0),
    'aux_heating_state' => array('État du chauffage auxiliaire', 'string', '', 0, 0),
    'aux_heating_on' => array('Chauffage auxiliaire actif', 'binary', '', 0, 1),
    'ventilation_state' => array('État de la ventilation', 'string', '', 0, 0),
    'locked' => array('Verrouillé', 'binary', '', 1, 1),
    'doors_closed' => array('Portes fermées', 'binary', '', 0, 1),
    'windows_closed' => array('Vitres fermées', 'binary', '', 0, 1),
    'trunk_closed' => array('Coffre fermé', 'binary', '', 0, 0),
    'bonnet_closed' => array('Capot fermé', 'binary', '', 0, 0),
    'sunroof_closed' => array('Toit ouvrant fermé', 'binary', '', 0, 0),
    'lights_on' => array('Feux allumés', 'binary', '', 0, 0),
    'moving' => array('En déplacement', 'binary', '', 1, 1),
    'latitude' => array('Latitude', 'numeric', '', 1, 0),
    'longitude' => array('Longitude', 'numeric', '', 1, 0),
    'position' => array('Position', 'string', '', 0, 0),
    'address' => array('Adresse de stationnement', 'string', '', 0, 1),
    'place' => array('Lieu', 'string', '', 0, 1),
    'fuel_level' => array('Carburant', 'numeric', '%', 1, 1),
    'fuel_range' => array('Autonomie carburant', 'numeric', 'km', 1, 1),
    'total_range' => array('Autonomie totale', 'numeric', 'km', 1, 1),
    'adblue_range' => array('Autonomie AdBlue', 'numeric', 'km', 0, 0),
    'status_timestamp' => array('Relevé du verrouillage et des ouvrants', 'string', '', 0, 1),
    'data_timestamp' => array('Dernière remontée du véhicule', 'string', '', 0, 1),
    'service_inspection_km' => array('Révision : kilomètres restants', 'numeric', 'km', 0, 1),
    'service_inspection_days' => array('Révision : jours restants', 'numeric', 'j', 0, 1),
    'service_oil_km' => array('Vidange : kilomètres restants', 'numeric', 'km', 0, 1),
    'service_oil_days' => array('Vidange : jours restants', 'numeric', 'j', 0, 1),
    'service_mot_days' => array('Contrôle technique : jours restants', 'numeric', 'j', 0, 1),
    'service_alert' => array('Entretien à prévoir', 'string', '', 0, 1),
    'services_disabled' => array('Services désactivés', 'string', '', 0, 1),
    'unavailable_since' => array('Données Škoda indisponibles depuis', 'string', '', 0, 1),
    'api_quota_remaining' => array('Requêtes restantes', 'numeric', '', 0, 1),
    'api_key_expires' => array('Expiration de la clé', 'string', '', 0, 0),
  );

  // Commandes action, créées seulement si le véhicule annonce l'opération correspondante :
  // opération de l'API => identifiant => nom, sous-type, info liée
  private static $_actions = array(
    'startCharging' => array('charge_start' => array('Démarrer la charge', 'other', '')),
    'stopCharging' => array('charge_stop' => array('Arrêter la charge', 'other', '')),
    'setChargingLimit' => array('charge_limit_set' => array('Régler la limite de charge', 'slider', 'charge_limit')),
    'setChargeMode' => array('charge_mode_set' => array('Choisir le mode de charge', 'select', 'charge_mode')),
    'startAirConditioning' => array(
      'climate_start' => array('Démarrer la climatisation', 'other', ''),
      'climate_start_temp' => array('Démarrer la climatisation à', 'slider', 'target_temperature'),
      'climate_temp_set' => array('Régler la consigne de climatisation', 'slider', 'target_temperature'),
    ),
    'stopAirConditioning' => array('climate_stop' => array('Arrêter la climatisation', 'other', '')),
    'startAuxiliaryHeating' => array('aux_heating_start' => array('Démarrer le chauffage auxiliaire', 'other', '')),
    'stopAuxiliaryHeating' => array('aux_heating_stop' => array('Arrêter le chauffage auxiliaire', 'other', '')),
    'startActiveVentilation' => array('ventilation_start' => array('Démarrer la ventilation', 'other', '')),
    'stopActiveVentilation' => array('ventilation_stop' => array('Arrêter la ventilation', 'other', '')),
  );

  // Opérations de l'API connues mais volontairement non proposées par le plugin : elles ne sont pas signalées comme nouvelles
  private static $_ignoredOperations = array('updateChargingProfile');

  private static $_chargeModeLabels = array(
    'MANUAL' => 'Manuel',
    'TIMER' => 'Minuterie',
    'TIMER_CHARGING_WITH_CLIMATISATION' => 'Minuterie avec climatisation',
    'PREFERRED_CHARGING_TIMES' => 'Plages préférées',
    'ONLY_OWN_CURRENT' => 'Production solaire seule',
    'IMMEDIATE_DISCHARGING' => 'Décharge immédiate',
    'HOME_STORAGE_CHARGING' => 'Stockage domestique',
  );

  // Échéances d'entretien saisies à la main sur l'équipement : Škoda ne les fournit pas dans son API
  private static $_services = array(
    'inspection' => 'Révision',
    'oil' => 'Vidange',
    'mot' => 'Contrôle technique',
  );
  // Seuils d'alerte par défaut, modifiables sur l'équipement
  const SERVICE_WARN_KM = 1000;
  const SERVICE_WARN_DAYS = 30;

  /*     * ***********************Methode static*************************** */

  // Ajoute un véhicule à partir de son seul numéro de série : vérifie auprès de Škoda que la clé le couvre,
  // puis crée l'équipement au nom donné par Škoda et lance sa première lecture.
  // (L'API n'a aucune demande pour lister les véhicules d'une clé : vérifié le 2026-10-05, toutes les adresses essayées rendent 404.)
  public static function createFromVin($_vin) {
    $vin = strtoupper(trim((string) $_vin));
    if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
      throw new Exception(__('Le numéro de série (VIN) doit comporter 17 lettres et chiffres, sans I, O ni Q', __FILE__));
    }
    if (is_object(eqLogic::byLogicalId($vin, __CLASS__))) {
      throw new Exception(__('Ce véhicule est déjà ajouté', __FILE__));
    }
    $key = config::byKey('apikey', __CLASS__);
    if ($key == '') {
      throw new Exception(__('La clé API n\'est pas renseignée dans la configuration du plugin', __FILE__));
    }
    $api = new myskodaApi($key);
    try {
      $response = $api->getVehicle($vin, array('info'));
    } catch (myskodaApiException $e) {
      throw new Exception(self::describeError($e));
    }
    // Jeedom retire la barre oblique des noms d'équipement : « TS110/1.5A7F » deviendrait « TS1101.5A7F ».
    // Elle est remplacée par une espace pour que le nom reste lisible.
    $name = trim(preg_replace('/\s+/', ' ', str_replace('/', ' ', (string) self::dig($response, 'vehicle.name'))));
    $eqLogic = new self();
    $eqLogic->setEqType_name(__CLASS__);
    $eqLogic->setName(($name != '') ? $name : 'Škoda');
    $eqLogic->setIsEnable(1);
    $eqLogic->setIsVisible(1);
    $eqLogic->setConfiguration('vin', $vin);
    try {
      $eqLogic->save();
    } catch (Exception $e) {
      // Nom déjà pris par un autre équipement sans objet parent : on le distingue par la fin du numéro de série
      $eqLogic->setName($eqLogic->getName() . ' ' . substr($vin, -6));
      $eqLogic->save();
    }
    try {
      $eqLogic->refreshFromApi();
    } catch (\Throwable $e) {
      log::add(__CLASS__, 'warning', $eqLogic->getHumanName() . ' : ' . $e->getMessage());
    }
    return $eqLogic;
  }

  // Chaque heure : les jours restants avant une échéance avancent même si la voiture ne roule pas
  public static function cronHourly() {
    foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
      try {
        $eqLogic->updateServices();
      } catch (\Throwable $e) {
        log::add(__CLASS__, 'warning', $eqLogic->getHumanName() . ' : ' . $e->getMessage());
      }
    }
  }

  // Toutes les minutes : lit les véhicules dont l'échéance est atteinte
  public static function cron() {
    if (config::byKey('apikey', __CLASS__) == '') {
      return;
    }
    foreach (eqLogic::byType(__CLASS__, true) as $eqLogic) {
      if ($eqLogic->getConfiguration('vin') == '' || time() < $eqLogic->getNextRefresh()) {
        continue;
      }
      // \Throwable : une donnée inattendue de Škoda ne doit pas empêcher la lecture des véhicules suivants
      try {
        $eqLogic->refreshFromApi();
      } catch (\Throwable $e) {
        log::add(__CLASS__, 'warning', $eqLogic->getHumanName() . ' : ' . $e->getMessage());
      }
      // Serveur de Škoda injoignable : inutile de faire attendre la tâche planifiée de Jeedom, commune à tous
      // les plugins, une fois par véhicule. Les autres seront lus à la minute suivante.
      if (self::$_unreachable) {
        break;
      }
    }
  }

  // Vrai quand la dernière demande à Škoda n'a reçu aucune réponse (réseau, délai dépassé)
  private static $_unreachable = false;

  // Une nouvelle clé ou un nouvel intervalle s'applique dès la minute suivante
  public static function postConfig_apikey($_value) {
    self::resetAllSchedules();
  }

  public static function postConfig_interval($_value) {
    self::resetAllSchedules();
  }

  public static function resetAllSchedules() {
    foreach (eqLogic::byType(__CLASS__) as $eqLogic) {
      $eqLogic->setNextRefresh(0);
    }
  }

  public static function postConfig_interval_active($_value) {
    self::resetAllSchedules();
  }

  // Valeur d'un tableau imbriqué par chemin « a.b.c » ; null si un niveau manque
  private static function dig($_array, $_path) {
    foreach (explode('.', $_path) as $key) {
      if (!is_array($_array) || !array_key_exists($key, $_array)) {
        return null;
      }
      $_array = $_array[$key];
    }
    return $_array;
  }

  // Traduit une valeur de l'API en 0 / 1 ; null (pas de mise à jour) pour toute valeur hors table : UNKNOWN, UNSUPPORTED, valeur future
  private static function toBinary($_value, $_on, $_off) {
    if ($_value === null) {
      return null;
    }
    if (in_array($_value, (array) $_on, true)) {
      return 1;
    }
    if (in_array($_value, (array) $_off, true)) {
      return 0;
    }
    return null;
  }

  private static function toLocalDate($_iso) {
    if ($_iso === null) {
      return null;
    }
    $time = strtotime($_iso);
    return ($time === false) ? null : date('Y-m-d H:i:s', $time);
  }

  // Met à plat la réponse de l'API : identifiant de commande => valeur. Une valeur absente ou inconnue n'apparaît pas.
  public static function extractValues($_vehicle) {
    $v = array();
    $v['name'] = self::dig($_vehicle, 'name');
    $v['license_plate'] = self::dig($_vehicle, 'licensePlate');
    $v['image'] = self::dig($_vehicle, 'renderUrl');
    $v['mileage'] = self::dig($_vehicle, 'odometer.mileageInKm');

    $v['battery_level'] = self::dig($_vehicle, 'charging.status.battery.stateOfChargeInPercent');
    $range = self::dig($_vehicle, 'charging.status.battery.remainingCruisingRangeInMeters');
    $v['range'] = ($range === null) ? null : round($range / 1000, 1);
    $chargingState = self::dig($_vehicle, 'charging.status.state');
    $v['charging_state'] = $chargingState;
    // L'état est omis par l'API quand il est indéterminé : dans ce cas « En charge » n'est pas modifiée
    if ($chargingState !== null) {
      $v['charging'] = ($chargingState == 'CHARGING') ? 1 : 0;
    }
    $v['charge_power'] = self::dig($_vehicle, 'charging.status.chargePowerInKw');
    $v['charge_rate'] = self::dig($_vehicle, 'charging.status.chargingRateInKilometersPerHour');
    $v['charge_time_left'] = self::dig($_vehicle, 'charging.status.remainingTimeToFullyChargedInMinutes');
    $v['fully_charged_at'] = self::toLocalDate(self::dig($_vehicle, 'charging.status.fullyChargedAt'));
    $v['charge_type'] = self::dig($_vehicle, 'charging.status.chargeType');
    $v['plug_connected'] = self::toBinary(self::dig($_vehicle, 'charging.status.plugConnectionState'), 'CONNECTED', 'DISCONNECTED');
    $v['plug_locked'] = self::toBinary(self::dig($_vehicle, 'charging.status.plugLockState'), 'LOCKED', 'UNLOCKED');
    $v['charge_limit'] = self::dig($_vehicle, 'charging.settings.targetStateOfChargeInPercent');
    $v['charge_mode'] = self::dig($_vehicle, 'charging.settings.preferredChargeMode');
    $v['battery_care'] = self::toBinary(self::dig($_vehicle, 'charging.settings.chargingCareMode'), 'ACTIVATED', 'DEACTIVATED');
    $v['max_charge_current'] = self::dig($_vehicle, 'charging.settings.maxChargeCurrentAc');
    $v['max_charge_current_ampere'] = self::dig($_vehicle, 'charging.settings.maxChargeCurrentAcAmpere');
    $inSavedLocation = self::dig($_vehicle, 'charging.isVehicleInSavedLocation');
    $v['in_saved_location'] = ($inSavedLocation === null) ? null : (int) $inSavedLocation;

    $climateState = self::dig($_vehicle, 'airConditioning.state');
    $v['climate_state'] = $climateState;
    $v['climate_on'] = self::toBinary($climateState, array('COOLING', 'HEATING', 'HEATING_AUXILIARY', 'VENTILATION'), array('OFF', 'COMPLETED'));
    $temperature = self::dig($_vehicle, 'airConditioning.targetTemperature.value');
    if ($temperature !== null && self::dig($_vehicle, 'airConditioning.targetTemperature.unit') == 'FAHRENHEIT') {
      $temperature = round(($temperature - 32) / 1.8, 1);
    }
    $v['target_temperature'] = $temperature;
    $v['climate_ready_at'] = self::toLocalDate(self::dig($_vehicle, 'airConditioning.estimatedReachOfTargetTemperatureAt'));
    $v['window_heating_front'] = self::toBinary(self::dig($_vehicle, 'airConditioning.windowHeating.front'), 'ON', 'OFF');
    $v['window_heating_rear'] = self::toBinary(self::dig($_vehicle, 'airConditioning.windowHeating.rear'), 'ON', 'OFF');
    $withoutPower = self::dig($_vehicle, 'airConditioning.airConditioningWithoutExternalPower');
    $v['climate_without_power'] = ($withoutPower === null) ? null : (int) $withoutPower;
    $v['aux_heating_state'] = self::dig($_vehicle, 'auxiliaryHeating.state');
    $v['aux_heating_on'] = self::toBinary($v['aux_heating_state'], array('PREHEATING', 'HEATING_AUXILIARY', 'VENTILATION'), array('OFF'));
    $v['ventilation_state'] = self::dig($_vehicle, 'activeVentilation.state');

    $v['locked'] = self::toBinary(self::dig($_vehicle, 'status.overall.locked'), 'YES', 'NO');
    $v['doors_closed'] = self::toBinary(self::dig($_vehicle, 'status.overall.doors'), 'CLOSED', 'OPEN');
    $v['windows_closed'] = self::toBinary(self::dig($_vehicle, 'status.overall.windows'), 'CLOSED', 'OPEN');
    $v['lights_on'] = self::toBinary(self::dig($_vehicle, 'status.overall.lights'), 'ON', 'OFF');
    $v['trunk_closed'] = self::toBinary(self::dig($_vehicle, 'status.detail.trunk'), 'CLOSED', 'OPEN');
    $v['bonnet_closed'] = self::toBinary(self::dig($_vehicle, 'status.detail.bonnet'), 'CLOSED', 'OPEN');
    $v['sunroof_closed'] = self::toBinary(self::dig($_vehicle, 'status.detail.sunroof'), 'CLOSED', 'OPEN');

    $v['status_timestamp'] = self::toLocalDate(self::dig($_vehicle, 'status.carCapturedTimestamp'));

    $v['moving'] = self::toBinary(self::dig($_vehicle, 'parkingPosition.state'), 'IN_MOTION', 'PARKED');
    $latitude = self::dig($_vehicle, 'parkingPosition.gpsCoordinates.latitude');
    $longitude = self::dig($_vehicle, 'parkingPosition.gpsCoordinates.longitude');
    if ($latitude !== null && $longitude !== null) {
      $v['latitude'] = $latitude;
      $v['longitude'] = $longitude;
      $v['position'] = $latitude . ',' . $longitude;
    }
    $v['address'] = self::dig($_vehicle, 'parkingPosition.formattedAddress');

    // Un hybride rechargeable a deux moteurs dans « fuelStatus » : le carburant se lit sur celui qui n'est pas électrique,
    // qu'il soit donné en premier ou en second (la batterie, elle, se lit dans « charging »)
    $engine = 'primaryEngineRange';
    if (self::dig($_vehicle, 'fuelStatus.primaryEngineRange.engineType') == 'ELECTRIC' && is_array(self::dig($_vehicle, 'fuelStatus.secondaryEngineRange'))) {
      $engine = 'secondaryEngineRange';
    }
    $v['fuel_level'] = self::dig($_vehicle, 'fuelStatus.' . $engine . '.currentFuelLevelInPercent');
    $v['fuel_range'] = self::dig($_vehicle, 'fuelStatus.' . $engine . '.remainingRangeInKm');
    // Motorisation : donnée par Škoda pour tout véhicule qui a un moteur thermique (GASOLINE, DIESEL, HYBRID, CNG, LPG) ;
    // un véhicule sans « fuelStatus » mais avec « charging » est un électrique
    $carType = self::dig($_vehicle, 'fuelStatus.carType');
    if ($carType === null && is_array(self::dig($_vehicle, 'charging'))) {
      $carType = 'ELECTRIC';
    }
    $v['car_type'] = $carType;
    $v['total_range'] = self::dig($_vehicle, 'fuelStatus.totalRangeInKm');
    $v['adblue_range'] = self::dig($_vehicle, 'fuelStatus.adBlueRange');

    // Date de la remontée la plus récente, toutes parties confondues
    $latest = 0;
    foreach (array('charging', 'status', 'odometer', 'airConditioning', 'fuelStatus') as $part) {
      $time = strtotime((string) self::dig($_vehicle, $part . '.carCapturedTimestamp'));
      if ($time !== false && $time > $latest) {
        $latest = $time;
      }
    }
    $v['data_timestamp'] = ($latest > 0) ? date('Y-m-d H:i:s', $latest) : null;

    return array_filter($v, function ($_value) {
      return $_value !== null;
    });
  }

  /*     * *********************Méthodes d'instance************************* */

  // Catégorie par défaut d'un nouveau véhicule ; l'utilisateur la change à sa guise
  public function preInsert() {
    $this->setCategory('energy', 1);
  }

  // Vignette de l'équipement dans la page du plugin : l'image du véhicule fournie par Škoda, à défaut le logo du plugin
  public function getImage() {
    $cmd = $this->getCmd('info', 'image');
    $image = is_object($cmd) ? $cmd->execCmd() : '';
    return (is_string($image) && strpos($image, 'https://') === 0) ? $image : parent::getImage();
  }

  // Vrai quand l'enregistrement en cours demande une nouvelle lecture (véhicule nouveau, numéro de série ou activation modifiés)
  private $_refreshNeeded = false;

  public function preSave() {
    $vin = strtoupper(trim($this->getConfiguration('vin')));
    if ($vin != '' && !preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
      throw new Exception(__('Le numéro de série (VIN) doit comporter 17 lettres et chiffres, sans I, O ni Q', __FILE__));
    }
    // Un véhicule ne peut être porté que par un seul équipement : deux équipements le liraient deux fois et
    // consommeraient deux fois le quota de Škoda
    if ($vin != '') {
      foreach (eqLogic::byType(__CLASS__) as $other) {
        if ($other->getId() != $this->getId() && $other->getConfiguration('vin') == $vin) {
          throw new Exception(__('Ce numéro de série est déjà celui d\'un autre équipement', __FILE__) . ' : ' . $other->getHumanName());
        }
      }
    }
    $previous = ($this->getId() != '') ? eqLogic::byId($this->getId()) : null;
    $this->_refreshNeeded = !is_object($previous) || $previous->getConfiguration('vin') != $vin || $previous->getIsEnable() != $this->getIsEnable();
    $this->setConfiguration('vin', $vin);
    $this->setLogicalId($vin);

    // Entretien : « dans tant de km » et « dans tant de jours » (ce qu'affiche l'application MyŠkoda) sont convertis
    // en kilométrage et en date d'échéance, puis vidés
    $mileageCmd = ($this->getId() != '') ? $this->getCmd('info', 'mileage') : null;
    $mileage = is_object($mileageCmd) ? $mileageCmd->execCmd() : '';
    foreach (self::$_services as $key => $label) {
      $inKm = str_replace(array(' ', ','), array('', '.'), (string) $this->getConfiguration('service_' . $key . '_in_km'));
      if ($inKm !== '') {
        if (!is_numeric($inKm) || !is_numeric($mileage)) {
          throw new Exception(__('Entretien : le kilométrage du véhicule n\'est pas encore connu, saisissez directement le kilométrage de l\'échéance', __FILE__));
        }
        $this->setConfiguration('service_' . $key . '_km', (int) round($mileage + $inKm));
      }
      $inDays = str_replace(' ', '', (string) $this->getConfiguration('service_' . $key . '_in_days'));
      if ($inDays !== '') {
        if (!is_numeric($inDays)) {
          throw new Exception(__('Entretien : le nombre de jours doit être un nombre', __FILE__));
        }
        $this->setConfiguration('service_' . $key . '_date', date('Y-m-d', strtotime('+' . (int) $inDays . ' days')));
      }
      $this->setConfiguration('service_' . $key . '_in_km', '');
      $this->setConfiguration('service_' . $key . '_in_days', '');
    }
  }

  public function postSave() {
    $cmd = $this->getCmd(null, 'refresh');
    if (!is_object($cmd)) {
      $cmd = new myskodaCmd();
      $cmd->setName(__('Rafraîchir', __FILE__));
    }
    $cmd->setEqLogic_id($this->getId());
    $cmd->setLogicalId('refresh');
    $cmd->setType('action');
    $cmd->setSubType('other');
    $cmd->save();
    $this->updateServices();
    // Lecture dès la minute suivante, seulement si l'enregistrement le justifie : renommer l'équipement ou le changer
    // d'objet ne doit pas coûter une requête du quota
    if ($this->_refreshNeeded) {
      $this->setNextRefresh(0);
    }
  }

  // Vrai quand la lecture doit passer au rythme rapide : le véhicule roule, ou vient de s'arrêter, ou une recharge
  // vient de commencer ou de finir. Pendant toute la durée d'une recharge, en revanche, le rythme reste celui de
  // l'arrêt : une recharge dure des heures, et la lire toutes les cinq minutes prenait douze des vingt requêtes de
  // l'heure, sans rien laisser aux commandes (constaté chez un testeur le 2026-10-07). Les heures de début et de fin
  // restent exactes, puisqu'elles viennent des relevés datés par la voiture.
  public function isBusy() {
    foreach (array('moving', 'charging') as $logicalId) {
      $cmd = $this->getCmd('info', $logicalId);
      if (!is_object($cmd)) {
        continue;
      }
      $value = $cmd->execCmd();
      // Sans valeur connue, la date rendue par le core est l'heure courante : elle ne dit rien d'un changement récent
      $recent = ($value !== '' && $value !== null && (time() - strtotime((string) $cmd->getValueDate())) < self::ACTIVE_HOLD);
      if (($logicalId == 'moving' && $value == 1) || $recent) {
        return true;
      }
    }
    return false;
  }

  public function getIntervalSeconds() {
    $interval = $this->isBusy()
      ? (int) config::byKey('interval_active', __CLASS__, self::DEFAULT_ACTIVE_INTERVAL)
      : (int) config::byKey('interval', __CLASS__, self::DEFAULT_INTERVAL);
    return max(self::MIN_INTERVAL, $interval) * 60;
  }

  public function preRemove() {
    cache::delete($this->scheduleKey());
    config::remove($this->placesKey(), __CLASS__);
    message::removeAll(__CLASS__, $this->messageKey());
    message::removeAll(__CLASS__, $this->disabledKey());
    message::removeAll(__CLASS__, $this->serviceKey());
    message::removeAll(__CLASS__, 'myskoda::operations::' . $this->getId());
  }

  private function scheduleKey() {
    return 'myskoda::next::' . $this->getId();
  }

  private function messageKey() {
    return 'myskoda::error::' . $this->getId();
  }

  public function getNextRefresh() {
    return (int) cache::byKey($this->scheduleKey())->getValue(0);
  }

  public function setNextRefresh($_timestamp) {
    cache::set($this->scheduleKey(), (int) $_timestamp);
  }

  private function getApi() {
    $apiKey = config::byKey('apikey', __CLASS__);
    if ($apiKey == '') {
      throw new Exception(__('La clé API n\'est pas renseignée dans la configuration du plugin', __FILE__));
    }
    if ($this->getConfiguration('vin') == '') {
      throw new Exception(__('Le numéro de série (VIN) n\'est pas renseigné', __FILE__));
    }
    return new myskodaApi($apiKey);
  }

  // Réponse de Škoda telle qu'elle est écrite dans le journal au niveau debug : sans ce qui identifie ou localise
  // le véhicule, pour qu'un utilisateur puisse transmettre son journal sans rien avoir à masquer
  public static function maskPersonal($_data) {
    if (!is_array($_data)) {
      return $_data;
    }
    foreach ($_data as $key => $value) {
      if (in_array((string) $key, array('vin', 'licensePlate', 'formattedAddress', 'gpsCoordinates', 'latitude', 'longitude'), true)) {
        $_data[$key] = ($value === null) ? null : '***';
      } elseif ((string) $key == 'chargingProfiles' && is_array($value)) {
        // Un profil de charge porte le nom que l'utilisateur lui a donné, souvent celui d'un lieu (constaté dans le
        // journal d'un testeur le 2026-10-09) ; le nom du véhicule, lui, reste lisible ailleurs dans la réponse
        $_data[$key] = self::maskPersonal($value);
        foreach (isset($_data[$key]['profiles']) && is_array($_data[$key]['profiles']) ? array_keys($_data[$key]['profiles']) : array() as $index) {
          if (isset($_data[$key]['profiles'][$index]['name'])) {
            $_data[$key]['profiles'][$index]['name'] = '***';
          }
        }
        if (isset($_data[$key]['currentVehiclePositionProfile']['name'])) {
          $_data[$key]['currentVehiclePositionProfile']['name'] = '***';
        }
      } else {
        $_data[$key] = self::maskPersonal($value);
      }
    }
    return $_data;
  }

  // Lit l'état du véhicule et met à jour les commandes. Lève une exception en cas d'échec.
  public function refreshFromApi() {
    $api = $this->getApi();
    $next = time() + $this->getIntervalSeconds();
    self::$_unreachable = false;
    try {
      $response = $api->getVehicle($this->getConfiguration('vin'));
    } catch (myskodaApiException $e) {
      self::$_unreachable = ($e->getHttpStatus() == 0);
      $this->applyQuotaHeaders($api);
      $this->setNextRefresh($this->nextAfterError($e, $next));
      throw new Exception(self::describeError($e));
    }
    message::removeAll(__CLASS__, $this->messageKey());
    log::add(__CLASS__, 'debug', $this->getHumanName() . ' : ' . json_encode(self::maskPersonal($response), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $vehicle = isset($response['vehicle']) && is_array($response['vehicle']) ? $response['vehicle'] : array();
    $this->applyVehicle($vehicle);
    $this->applyChargingProfiles($vehicle);
    // Le rythme dépend de l'état qui vient d'être lu
    $next = time() + $this->getIntervalSeconds();
    if (isset($vehicle['operations']) && is_array($vehicle['operations'])) {
      $this->syncActions($vehicle);
    }
    $errors = (isset($response['errors']) && is_array($response['errors'])) ? $response['errors'] : array();
    $this->applyErrors($errors);
    $this->updateAvailability($vehicle, $errors);

    // Quota presque épuisé : garder le reste pour les commandes et attendre le renouvellement
    $remaining = $this->applyQuotaHeaders($api);
    $reset = $api->getLastHeader('RateLimit-Reset');
    if ($remaining !== null && $remaining <= self::QUOTA_RESERVE && is_numeric($reset)) {
      $next = max($next, time() + (int) $reset);
    }
    $this->setNextRefresh($next);
    // Une seule régénération de la tuile pour toutes les valeurs reçues
    $this->refreshWidget();
  }

  // Profils de charge (« charging profiles » dans l'API, « Points de recharge » dans l'application MyŠkoda et donc à
  // l'écran) d'un électrique ou d'un hybride rechargeable, tels que Škoda les rend, mis en forme pour
  // l'affichage. Un profil est un réglage de recharge rattaché à un lieu enregistré dans l'application MyŠkoda :
  // limite de charge, charge minimale (en dessous, la voiture charge tout de suite), plages horaires préférées,
  // heures de départ (« timers » dans l'API ; l'application MyŠkoda les nomme « Heure de départ 1, 2, 3 », indiqué par
  // un testeur le 2026-10-10 ; les plages y sont les « Heures de charge préférées », capture d'un second testeur le
  // 2026-10-11). Rend null si le véhicule n'en fournit pas ; « current » est le profil du lieu où il est garé, ou null.
  public static function extractChargingProfiles($_vehicle) {
    $source = self::dig($_vehicle, 'chargingProfiles');
    if (!is_array($source) || !isset($source['profiles']) || !is_array($source['profiles'])) {
      return null;
    }
    $currentId = self::dig($source, 'currentVehiclePositionProfile.id');
    $profiles = array();
    $current = null;
    foreach ($source['profiles'] as $item) {
      if (!is_array($item)) {
        continue;
      }
      $profile = array(
        'id' => self::dig($item, 'id'),
        'name' => (string) self::dig($item, 'name'),
        'target' => self::dig($item, 'settings.targetStateOfChargeInPercent'),
        // La charge minimale ne vaut que si elle n'est pas explicitement désactivée
        'min' => (self::dig($item, 'settings.minBatteryStateOfCharge.enabled') === false) ? null : self::dig($item, 'settings.minBatteryStateOfCharge.minimumBatteryStateOfChargeInPercent'),
        'current' => ($currentId !== null && self::dig($item, 'id') == $currentId),
        'times' => array(),
        'timers' => array(),
      );
      foreach (is_array(self::dig($item, 'preferredChargingTimes')) ? $item['preferredChargingTimes'] : array() as $time) {
        if (self::dig($time, 'enabled') === true) {
          $profile['times'][] = self::dig($time, 'startTime') . ' – ' . self::dig($time, 'endTime');
        }
      }
      foreach (is_array(self::dig($item, 'timers')) ? $item['timers'] : array() as $timer) {
        if (self::dig($timer, 'enabled') !== true) {
          continue;
        }
        $days = (self::dig($timer, 'type') == 'ONE_OFF') ? array(self::dig($timer, 'oneOffDay')) : self::dig($timer, 'recurringOn');
        $profile['timers'][] = trim(self::dig($timer, 'time') . ' ' . self::dayList(is_array($days) ? $days : array()));
      }
      // Deux plages identiques (constaté sur un Elroq) ne sont affichées qu'une fois
      $profile['times'] = array_values(array_unique($profile['times']));
      if ($profile['current']) {
        $profile['next'] = self::dig($source, 'currentVehiclePositionProfile.nextChargingTime');
        // La limite donnée pour le lieu actuel prime sur celle de la liste, si les deux diffèrent
        $target = self::dig($source, 'currentVehiclePositionProfile.targetStateOfChargeInPercent');
        if ($target !== null) {
          $profile['target'] = $target;
        }
        $current = $profile;
      }
      $profiles[] = $profile;
    }
    return array('profiles' => $profiles, 'current' => $current, 'time' => self::toLocalDate(self::dig($source, 'carCapturedTimestamp')));
  }

  private static $_dayLabels = array('MONDAY' => 'lun.', 'TUESDAY' => 'mar.', 'WEDNESDAY' => 'mer.', 'THURSDAY' => 'jeu.', 'FRIDAY' => 'ven.', 'SATURDAY' => 'sam.', 'SUNDAY' => 'dim.');

  // Jours d'une heure de départ, en clair : « tous les jours », « lun. à ven. », ou la liste
  private static function dayList($_days) {
    $order = array_keys(self::$_dayLabels);
    $ranks = array();
    foreach ($_days as $day) {
      $rank = array_search($day, $order, true);
      if ($rank !== false) {
        $ranks[$rank] = true;
      }
    }
    ksort($ranks);
    $ranks = array_keys($ranks);
    if (count($ranks) == 0) {
      return '';
    }
    if (count($ranks) == 7) {
      return __('tous les jours', __FILE__);
    }
    $labels = array();
    foreach ($ranks as $rank) {
      $labels[] = __(self::$_dayLabels[$order[$rank]], __FILE__);
    }
    // Jours qui se suivent : le premier et le dernier suffisent
    if (count($ranks) > 2 && $ranks[count($ranks) - 1] - $ranks[0] == count($ranks) - 1) {
      return $labels[0] . ' ' . __('à', __FILE__) . ' ' . $labels[count($labels) - 1];
    }
    return implode(' ', $labels);
  }

  // Profils de charge : le profil du lieu où le véhicule est garé va dans des commandes (utilisables dans un scénario),
  // la liste complète est gardée pour la tuile. Rien n'est créé pour un véhicule qui n'a pas de profils.
  // Ces valeurs ne passent pas par applyVehicle : l'heure de leur relevé ne doit pas dater les autres commandes.
  public function applyChargingProfiles($_vehicle) {
    $data = self::extractChargingProfiles($_vehicle);
    if ($data === null) {
      return;
    }
    $this->setCache('chargingProfiles', $data['profiles']);
    $time = ($data['time'] !== null && strtotime($data['time']) <= time()) ? $data['time'] : null;
    $current = $data['current'];
    // Hors de tout lieu enregistré, le nom est vidé : c'est lui qui dit si un profil s'applique
    $this->updateInfo('charge_profile', ($current === null) ? '' : $current['name'], $time);
    if ($current === null) {
      if (is_object($this->getCmd('info', 'charge_profile_next'))) {
        $this->updateInfo('charge_profile_next', '', $time);
      }
      return;
    }
    if ($current['target'] !== null) {
      $this->updateInfo('charge_profile_target', $current['target'], $time);
    }
    if ($current['min'] !== null) {
      $this->updateInfo('charge_profile_min', $current['min'], $time);
    }
    if ($current['next'] !== null || is_object($this->getCmd('info', 'charge_profile_next'))) {
      $this->updateInfo('charge_profile_next', (string) $current['next'], $time);
    }
  }

  private static $_partLabels = array(
    'VEHICLE_STATUS' => 'verrouillage et ouvrants',
    'FUEL_STATUS' => 'carburant',
    'ODOMETER' => 'kilométrage',
    'PARKING_POSITION' => 'position de stationnement',
    'AIR_CONDITIONING' => 'climatisation',
    'AUXILIARY_HEATING' => 'chauffage auxiliaire',
    'ACTIVE_VENTILATION' => 'ventilation',
    'CHARGING_PROFILES' => 'points de recharge',
    'CHARGING' => 'charge',
  );

  private function disabledKey() {
    return 'myskoda::disabled::' . $this->getId();
  }

  // Škoda répond parfois sans aucune donnée du véhicule, chaque partie étant « UNAVAILABLE » (constaté le 2026-10-07
  // pendant un trajet : cinq lectures sur six pendant une demi-heure). Les valeurs affichées sont alors les dernières
  // reçues et un trajet en cours ne peut pas se terminer : l'heure de la première réponse vide est gardée pour le dire.
  public function updateAvailability($_vehicle, $_errors) {
    $unavailable = 0;
    foreach ($_errors as $error) {
      if (substr((string) self::dig($error, 'type'), -12) == '_UNAVAILABLE') {
        $unavailable++;
      }
    }
    $empty = ($unavailable > 0 && !isset($_vehicle['status']) && !isset($_vehicle['odometer']));
    $cmd = $this->getCmd('info', 'unavailable_since');
    if (!$empty) {
      if (is_object($cmd) && $cmd->execCmd() != '') {
        $this->updateInfo('unavailable_since', '');
      }
      return;
    }
    if (!is_object($cmd) || $cmd->execCmd() == '') {
      $this->updateInfo('unavailable_since', date('Y-m-d H:i:s'));
    }
  }

  // Erreurs partielles de la réponse. Une partie « DISABLED » existe sur le véhicule mais Škoda ne la délivre pas
  // (licence expirée, consentement retiré, mode atelier) : seul l'utilisateur peut y remédier, il est donc prévenu.
  private function applyErrors($_errors) {
    $disabled = array();
    foreach ($_errors as $error) {
      $type = (string) self::dig($error, 'type');
      log::add(__CLASS__, 'debug', $this->getHumanName() . ' : ' . $type . ' ' . self::dig($error, 'description'));
      if (substr($type, -9) == '_DISABLED') {
        $part = substr($type, 0, -9);
        $disabled[] = isset(self::$_partLabels[$part]) ? __(self::$_partLabels[$part], __FILE__) : $part;
      }
    }
    $value = implode(', ', $disabled);
    $cmd = $this->getCmd('info', 'services_disabled');
    if ($value == '' && !is_object($cmd)) {
      return;
    }
    $previous = is_object($cmd) ? (string) $cmd->execCmd() : '';
    $this->updateInfo('services_disabled', $value);
    if ($value != $previous) {
      message::removeAll(__CLASS__, $this->disabledKey());
      if ($value != '') {
        message::add(__CLASS__, $this->getHumanName() . ' : ' . sprintf(__('Škoda indique que ces données sont désactivées sur le véhicule : %s. À vérifier dans l\'application MyŠkoda : services en ligne (licence), consentement, mode privé du véhicule.', __FILE__), $value), '', $this->disabledKey());
      }
    }
  }

  // Commandes de chaque partie de la réponse qui porte sa propre heure de relevé (carCapturedTimestamp)
  private static $_partOf = array(
    'status' => array('locked', 'doors_closed', 'windows_closed', 'lights_on', 'trunk_closed', 'bonnet_closed', 'sunroof_closed'),
    'odometer' => array('mileage'),
    'fuelStatus' => array('fuel_level', 'fuel_range', 'total_range', 'adblue_range'),
    'charging' => array('battery_level', 'range', 'charging', 'charging_state', 'charge_power', 'charge_rate', 'charge_time_left', 'fully_charged_at',
      'charge_type', 'plug_connected', 'plug_locked', 'charge_limit', 'charge_mode', 'battery_care', 'max_charge_current', 'max_charge_current_ampere', 'in_saved_location'),
    'airConditioning' => array('climate_on', 'climate_state', 'target_temperature', 'climate_ready_at', 'climate_without_power', 'window_heating_front', 'window_heating_rear'),
    'auxiliaryHeating' => array('aux_heating_state', 'aux_heating_on'),
  );

  // Reporte dans les commandes les données d'un véhicule telles que Škoda les rend.
  // Chaque valeur est datée de l'heure à laquelle la voiture l'a relevée, pas de l'heure de la lecture : la voiture envoie
  // ses données au départ et à l'arrivée (constaté le 2026-10-05), ce qui donne les heures exactes des trajets
  // quel que soit le rythme de lecture.
  public function applyVehicle($_vehicle) {
    $values = self::extractValues($_vehicle);
    $times = array();
    $latest = null;
    foreach (self::$_partOf as $part => $logicalIds) {
      $time = self::toLocalDate(self::dig($_vehicle, $part . '.carCapturedTimestamp'));
      if ($time === null || strtotime($time) > time()) {
        continue;
      }
      foreach ($logicalIds as $logicalId) {
        $times[$logicalId] = $time;
      }
      if ($latest === null || $time > $latest) {
        $latest = $time;
      }
    }
    $timeOf = function ($_logicalId) use ($times, $latest) {
      return isset($times[$_logicalId]) ? $times[$_logicalId] : $latest;
    };

    // Škoda répond « IN_MOTION » quand il n'a pas de position de stationnement, ce qui arrive aussi pour un véhicule garé
    // dont il n'a encore jamais reçu la position (constaté le 2026-10-04 à l'activation des services d'une Karoq).
    // Tant qu'aucun stationnement n'a été vu pour ce véhicule, cet état est tenu pour inconnu.
    if (isset($values['moving']) && $values['moving'] == 1 && !is_object($this->getCmd('info', 'latitude'))) {
      unset($values['moving']);
    }

    // Trajet fait entre deux lectures sans avoir été vu en cours de route : le véhicule est stationné avant et après,
    // mais le compteur a avancé. Il est inscrit comme un trajet d'une seconde finissant à l'heure du relevé :
    // sa distance est juste, sa durée est inconnue.
    $moving = $this->getCmd('info', 'moving');
    $mileage = $this->getCmd('info', 'mileage');
    if ($latest !== null && isset($values['moving']) && $values['moving'] == 0 && isset($values['mileage'])
      && is_object($moving) && is_object($mileage) && $moving->execCmd() !== '' && $moving->execCmd() == 0
      && is_numeric($mileage->execCmd()) && $values['mileage'] > $mileage->execCmd()) {
      $moving->event(1, date('Y-m-d H:i:s', strtotime($timeOf('mileage')) - 1));
      // La fin de ce trajet est datée du même relevé que son début, pour qu'il dure bien une seconde
      $times['moving'] = $timeOf('mileage');
    }

    foreach ($values as $logicalId => $value) {
      $this->updateInfo($logicalId, $value, isset($times[$logicalId]) ? $times[$logicalId] : $latest);
    }
    if (isset($values['latitude']) && isset($values['address'])) {
      $this->rememberPlace($values['latitude'], $values['longitude'], $values['address']);
      $this->updateInfo('place', $this->placeLabel($values['latitude'], $values['longitude'], $values['address']), $latest);
    }
    $this->updateServices();
  }

  private function serviceKey() {
    return 'myskoda::service::' . $this->getId();
  }

  private static function serviceText($_value, $_unit, $_late) {
    return ($_value < 0) ? sprintf($_late, number_format(-$_value, 0, ',', ' ')) : number_format($_value, 0, ',', ' ') . ' ' . $_unit;
  }

  // Met à jour les kilomètres et les jours restants avant chaque échéance d'entretien saisie sur l'équipement,
  // et prévient une fois quand une échéance passe sous le seuil d'alerte
  public function updateServices() {
    $mileageCmd = $this->getCmd('info', 'mileage');
    $mileage = is_object($mileageCmd) ? $mileageCmd->execCmd() : '';
    $warnKm = $this->getConfiguration('service_warn_km');
    $warnKm = is_numeric($warnKm) ? (float) $warnKm : self::SERVICE_WARN_KM;
    $warnDays = $this->getConfiguration('service_warn_days');
    $warnDays = is_numeric($warnDays) ? (float) $warnDays : self::SERVICE_WARN_DAYS;
    $alerts = array();
    $configured = false;
    foreach (self::$_services as $key => $label) {
      $dueKm = $this->getConfiguration('service_' . $key . '_km');
      $dueTime = strtotime((string) $this->getConfiguration('service_' . $key . '_date'));
      $kmLeft = (is_numeric($dueKm) && is_numeric($mileage)) ? (int) round($dueKm - $mileage) : null;
      $daysLeft = ($this->getConfiguration('service_' . $key . '_date') != '' && $dueTime !== false) ? (int) round(($dueTime - strtotime(date('Y-m-d'))) / 86400) : null;
      // Une échéance effacée sur l'équipement emporte sa commande : la tuile ne doit pas garder un ancien décompte
      foreach (array('km' => $kmLeft, 'days' => $daysLeft) as $suffix => $left) {
        $old = $this->getCmd('info', 'service_' . $key . '_' . $suffix);
        if ($left === null && is_object($old)) {
          $old->remove();
        }
      }
      if ($kmLeft === null && $daysLeft === null) {
        continue;
      }
      $configured = true;
      $parts = array();
      if ($kmLeft !== null) {
        $this->updateInfo('service_' . $key . '_km', $kmLeft);
        $parts[] = self::serviceText($kmLeft, 'km', __('en retard de %s km', __FILE__));
      }
      if ($daysLeft !== null) {
        $this->updateInfo('service_' . $key . '_days', $daysLeft);
        $parts[] = self::serviceText($daysLeft, __('jours', __FILE__), __('en retard de %s jours', __FILE__));
      }
      if (($kmLeft !== null && $kmLeft <= $warnKm) || ($daysLeft !== null && $daysLeft <= $warnDays)) {
        $alerts[] = __($label, __FILE__) . ' : ' . implode(', ', $parts);
      }
    }
    $cmd = $this->getCmd('info', 'service_alert');
    if (!$configured && !is_object($cmd)) {
      return;
    }
    $value = implode(' ; ', $alerts);
    $previous = is_object($cmd) ? (string) $cmd->execCmd() : '';
    $this->updateInfo('service_alert', $value);
    if ($value != $previous) {
      message::removeAll(__CLASS__, $this->serviceKey());
      if ($value != '') {
        message::add(__CLASS__, $this->getHumanName() . ' : ' . __('entretien à prévoir', __FILE__) . ' — ' . $value, '', $this->serviceKey());
      }
      $this->refreshWidget();
    }
  }

  // Reporte dans les commandes le quota restant et l'expiration de la clé ; retourne le quota restant ou null
  private function applyQuotaHeaders($_api) {
    $expires = self::toLocalDate($_api->getLastHeader('X-API-Key-Expires-At'));
    if ($expires !== null) {
      $this->updateInfo('api_key_expires', $expires);
    }
    $remaining = $_api->getLastHeader('RateLimit-Remaining');
    if (!is_numeric($remaining)) {
      return null;
    }
    $this->updateInfo('api_quota_remaining', (int) $remaining);
    return (int) $remaining;
  }

  // Prochaine échéance après un échec ; signale par un message Jeedom ce qui demande une action de l'utilisateur
  private function nextAfterError($_e, $_default) {
    $status = $_e->getHttpStatus();
    if (in_array($status, array(401, 403, 404))) {
      message::add(__CLASS__, $this->getHumanName() . ' : ' . self::describeError($_e), '', $this->messageKey());
      return time() + self::PAUSE_AFTER_DENIED;
    }
    if (in_array($status, array(429, 503))) {
      $retryAfter = $_e->getRetryAfter();
      return time() + max(60, ($retryAfter === null) ? self::DEFAULT_RETRY_AFTER : $retryAfter);
    }
    return $_default;
  }

  public static function describeError($_e) {
    switch ($_e->getProblemType()) {
      case 'api-key-expired':
        return __('La clé API a expiré : en créer une nouvelle dans l\'application MyŠkoda', __FILE__);
      case 'api-key-not-authorized':
        return __('La clé API ne couvre pas ce véhicule : vérifier les véhicules choisis à la création de la clé', __FILE__);
      case 'operation-not-authorized':
        return __('Le véhicule a refusé la commande pour cet utilisateur', __FILE__);
      case 'operation-not-supported':
        return __('Le véhicule ne dispose pas de cette fonction', __FILE__);
      case 'operation-disabled':
        return __('Cette fonction est désactivée sur le véhicule (consentement, licence ou mode atelier)', __FILE__);
      case 'rate-limit-exceeded':
        return __('Quota de requêtes Škoda épuisé pour cette heure', __FILE__);
      case 'vehicle-not-accepting-requests':
        return __('Le véhicule n\'accepte pas de commande pour le moment', __FILE__);
    }
    switch ($_e->getHttpStatus()) {
      case 401:
        return __('Clé API refusée : vérifier la clé dans la configuration du plugin', __FILE__);
      case 404:
        return __('Véhicule inconnu : vérifier le numéro de série (VIN)', __FILE__);
    }
    return __('Erreur de l\'API Škoda', __FILE__) . ' (' . $_e->getMessage() . ')';
  }

  // Met à jour une commande info, en la créant à la première valeur reçue
  private function updateInfo($_logicalId, $_value, $_time = null) {
    if (!isset(self::$_infos[$_logicalId])) {
      return;
    }
    $cmd = $this->getCmd('info', $_logicalId);
    if (!is_object($cmd)) {
      list($name, $subType, $unit, $historized, $visible) = self::$_infos[$_logicalId];
      $cmd = new myskodaCmd();
      $cmd->setName(__($name, __FILE__));
      $cmd->setEqLogic_id($this->getId());
      $cmd->setLogicalId($_logicalId);
      $cmd->setType('info');
      $cmd->setSubType($subType);
      $cmd->setUnite($unit);
      $cmd->setIsHistorized($historized);
      if ($historized == 1) {
        // Historique brut, sans moyenne à l'archivage : le panneau a besoin des changements d'état exacts
        $cmd->setConfiguration('historizeMode', 'none');
      }
      $cmd->setIsVisible($visible);
      $cmd->save();
    }
    $this->checkAndUpdateCmd($cmd, $_value, $_time);
  }

  // Crée les commandes action des opérations annoncées par le véhicule ; ne supprime rien
  private function syncActions($_vehicle) {
    $supported = array();
    foreach ($_vehicle['operations'] as $operation) {
      if (isset($operation['name'])) {
        $supported[] = $operation['name'];
      }
    }
    // L'API de Škoda est jeune et peut s'enrichir (verrouillage, réveil du véhicule…). Une opération annoncée par le
    // véhicule que cette version du plugin ne connaît pas est signalée une fois, pour qu'elle puisse être ajoutée.
    $unknown = array_values(array_diff($supported, array_keys(self::$_actions), self::$_ignoredOperations));
    sort($unknown);
    $known = (string) $this->getCache('unknownOperations', '');
    if (implode(',', $unknown) != $known) {
      $this->setCache('unknownOperations', implode(',', $unknown));
      message::removeAll(__CLASS__, 'myskoda::operations::' . $this->getId());
      if (count($unknown) > 0) {
        log::add(__CLASS__, 'info', $this->getHumanName() . ' : ' . 'opérations annoncées par Škoda et non gérées par cette version : ' . implode(', ', $unknown));
        message::add(__CLASS__, $this->getHumanName() . ' : ' . sprintf(__('Škoda propose pour ce véhicule une ou plusieurs commandes que cette version du plugin ne gère pas encore : %s. Vérifiez s\'il existe une version plus récente du plugin, ou signalez-le à son auteur.', __FILE__), implode(', ', $unknown)), '', 'myskoda::operations::' . $this->getId());
      }
    }
    foreach (self::$_actions as $operation => $commands) {
      if (!in_array($operation, $supported)) {
        continue;
      }
      foreach ($commands as $logicalId => $definition) {
        list($name, $subType, $infoLogicalId) = $definition;
        $cmd = $this->getCmd('action', $logicalId);
        $changed = false;
        if (!is_object($cmd)) {
          $cmd = new myskodaCmd();
          $cmd->setName(__($name, __FILE__));
          $cmd->setEqLogic_id($this->getId());
          $cmd->setLogicalId($logicalId);
          $cmd->setType('action');
          $cmd->setSubType($subType);
          if ($logicalId == 'charge_limit_set') {
            // Bornes d'après la spécification : « les véhicules acceptent en général 50 à 100 par pas de 10 »
            $cmd->setConfiguration('minValue', 50);
            $cmd->setConfiguration('maxValue', 100);
            $cmd->setDisplay('parameters', array('step' => 10));
          }
          if ($logicalId == 'climate_start_temp' || $logicalId == 'climate_temp_set') {
            // Bornes supposées, la spécification n'en donne pas : à vérifier à l'essai réel
            $cmd->setConfiguration('minValue', 16);
            $cmd->setConfiguration('maxValue', 30);
            $cmd->setDisplay('parameters', array('step' => 0.5));
            $cmd->setUnite('°C');
          }
          $changed = true;
        }
        $info = ($infoLogicalId != '') ? $this->getCmd('info', $infoLogicalId) : null;
        if (is_object($info) && $cmd->getValue() != $info->getId()) {
          $cmd->setValue($info->getId());
          $changed = true;
        }
        if ($logicalId == 'charge_mode_set') {
          $listValue = self::buildChargeModeList(self::dig($_vehicle, 'charging.settings.availableChargeModes'));
          if ($listValue != '' && $cmd->getConfiguration('listValue') != $listValue) {
            $cmd->setConfiguration('listValue', $listValue);
            $changed = true;
          }
        }
        if ($changed) {
          $cmd->save();
        }
      }
    }
  }

  private static function buildChargeModeList($_modes) {
    if (!is_array($_modes)) {
      return '';
    }
    $items = array();
    foreach ($_modes as $mode) {
      $items[] = $mode . '|' . (isset(self::$_chargeModeLabels[$mode]) ? self::$_chargeModeLabels[$mode] : $mode);
    }
    return implode(';', $items);
  }

  // Version du plugin telle qu'elle est affichée (configuration, journal de bord) : le numéro de info.json,
  // suivi de « beta » si le plugin est installé dans cette version
  public static function getVersionLabel() {
    $info = json_decode((string) @file_get_contents(dirname(__FILE__) . '/../../plugin_info/info.json'), true);
    $label = (is_array($info) && isset($info['pluginVersion'])) ? $info['pluginVersion'] : '?';
    $update = update::byTypeAndLogicalId('plugin', __CLASS__);
    if (!is_object($update) || $update->getConfiguration('version', 'beta') != 'stable') {
      $label .= ' beta';
    }
    return $label;
  }

  // Contenu d'une commande tel qu'il est écrit dans le journal : le code S-PIN n'y figure jamais
  public static function describeBody($_body) {
    if ($_body === null) {
      return '';
    }
    if (is_array($_body) && isset($_body['spin'])) {
      $_body['spin'] = '****';
    }
    return json_encode($_body);
  }

  // Corps de la demande de climatisation. Le préchauffage voiture non branchée n'est demandé que si l'utilisateur a coché
  // la case sur l'équipement ; sinon rien n'est envoyé à ce sujet et la voiture applique son propre réglage.
  public function climateStartBody($_temperature = null) {
    $body = array();
    // Sans température donnée, c'est la consigne choisie dans Jeedom qui est demandée ; s'il n'y en a pas, la voiture garde la sienne
    if ($_temperature === null && is_numeric($this->getConfiguration('climate_setpoint'))) {
      $_temperature = (float) $this->getConfiguration('climate_setpoint');
    }
    if ($_temperature !== null) {
      $body['targetTemperature'] = array('value' => (float) $_temperature, 'unit' => 'CELSIUS');
    }
    if ($this->getConfiguration('climate_without_power', 0) == 1) {
      $body['airConditioningWithoutExternalPower'] = true;
    }
    return empty($body) ? new stdClass() : $body;
  }

  // Consigne de climatisation choisie dans Jeedom. Škoda n'a pas de demande pour régler la température seule : elle
  // n'est transmise qu'au démarrage. La consigne est donc mémorisée pour le prochain démarrage ; si la climatisation
  // tourne déjà, elle est relancée avec la nouvelle température.
  public function setClimateSetpoint($_temperature) {
    if (!is_numeric($_temperature)) {
      throw new Exception(__('La consigne de climatisation doit être un nombre', __FILE__));
    }
    $temperature = round(max(16, min(30, (float) $_temperature)) * 2) / 2;
    $this->setConfiguration('climate_setpoint', $temperature);
    $this->save(true);
    $running = $this->getCmd('info', 'climate_on');
    if (is_object($running) && $running->execCmd() == 1) {
      $this->sendCommand('POST', 'air-conditioning/start', $this->climateStartBody($temperature));
    }
    $this->refreshWidget();
  }

  // Le code S-PIN d'un véhicule est enregistré chiffré, comme le veut le core pour un réglage d'équipement :
  // ces deux méthodes sont appelées par le core à chaque lecture et à chaque écriture de l'équipement en base.
  public function decrypt() {
    $this->setConfiguration('spin', utils::decrypt($this->getConfiguration('spin')));
  }

  public function encrypt() {
    $this->setConfiguration('spin', utils::encrypt($this->getConfiguration('spin')));
  }

  // Le chauffage auxiliaire est la seule commande pour laquelle Škoda exige le code S-PIN. Ce code est propre au
  // véhicule et à son utilisateur principal (il se redéfinit dans la voiture, avec ses deux clés) : il se saisit donc
  // sur l'équipement. Un code saisi autrefois dans la configuration générale du plugin sert encore à défaut.
  public function startAuxiliaryHeating() {
    $spin = trim((string) $this->getConfiguration('spin'));
    if ($spin == '') {
      $spin = trim((string) config::byKey('spin', __CLASS__));
    }
    if ($spin == '') {
      throw new Exception(__('Le chauffage auxiliaire demande le code S-PIN du véhicule : le saisir sur la page de l\'équipement', __FILE__));
    }
    $this->sendCommand('POST', 'auxiliary-heating/start', array('spin' => $spin, 'startMode' => 'HEATING'));
  }

  // Envoie une commande au véhicule puis avance la prochaine lecture pour en voir l'effet
  public function sendCommand($_method, $_operation, $_body = null) {
    $api = $this->getApi();
    try {
      $api->command($_method, $this->getConfiguration('vin'), $_operation, $_body);
    } catch (myskodaApiException $e) {
      $this->applyQuotaHeaders($api);
      throw new Exception(self::describeError($e));
    }
    $this->applyQuotaHeaders($api);
    log::add(__CLASS__, 'info', $this->getHumanName() . ' : ' . $_method . ' ' . $_operation . ' ' . self::describeBody($_body));
    // Délai avant la relecture qui montre l'effet de la commande : réglable, entre 15 secondes et 5 minutes
    $delay = config::byKey('refresh_after_command', __CLASS__, self::REFRESH_AFTER_COMMAND);
    $delay = is_numeric($delay) ? max(15, min(300, (int) $delay)) : self::REFRESH_AFTER_COMMAND;
    $this->setNextRefresh(time() + $delay);
  }
  /*     * **************************Panneau****************************** */

  // Nombre de lieux de stationnement mémorisés par véhicule, et distance en mètres en dessous de laquelle deux positions sont le même lieu
  const PLACES_MAX = 300;
  const PLACE_RADIUS = 60;

  private function placesKey() {
    return 'places::' . $this->getId();
  }

  // Lieux de stationnement connus : liste de (lat, lng, address). L'API ne donne l'adresse que du stationnement en cours,
  // et Jeedom n'historise pas les textes : le plugin garde donc lui-même l'adresse de chaque lieu rencontré.
  public function getPlaces() {
    $places = config::byKey($this->placesKey(), __CLASS__, array());
    if (is_string($places)) {
      $places = json_decode($places, true);
    }
    return is_array($places) ? $places : array();
  }

  private static function distanceMeters($_lat1, $_lng1, $_lat2, $_lng2) {
    $dLat = deg2rad($_lat2 - $_lat1);
    $dLng = deg2rad($_lng2 - $_lng1);
    $a = pow(sin($dLat / 2), 2) + cos(deg2rad($_lat1)) * cos(deg2rad($_lat2)) * pow(sin($dLng / 2), 2);
    return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
  }

  // Indice du lieu connu le plus proche dans le rayon ; null si aucun
  // Rayon, en mètres, dans lequel un stationnement est rattaché à un lieu favori qui n'a pas son propre rayon
  const FAVORITE_RADIUS = 200;

  public static function favoriteRadius() {
    $radius = config::byKey('favorite_radius', __CLASS__, self::FAVORITE_RADIUS);
    return (is_numeric($radius) && $radius > 0) ? (float) $radius : (float) self::FAVORITE_RADIUS;
  }

  // Indice du lieu auquel appartient une position ; null si aucun. Les lieux favoris passent d'abord, chacun avec son
  // rayon (le sien, à défaut celui de la configuration du plugin) : la position transmise par la voiture peut s'écarter
  // de plus de cent mètres d'un stationnement à l'autre au même endroit. Les autres lieux gardent un rayon court.
  private static function nearestPlace($_places, $_lat, $_lng) {
    $nearest = null;
    $best = null;
    $default = self::favoriteRadius();
    foreach ($_places as $index => $place) {
      if (empty($place['favorite'])) {
        continue;
      }
      $radius = (isset($place['radius']) && is_numeric($place['radius']) && $place['radius'] > 0) ? (float) $place['radius'] : $default;
      $distance = self::distanceMeters($_lat, $_lng, $place['lat'], $place['lng']);
      if ($distance <= $radius && ($best === null || $distance < $best)) {
        $best = $distance;
        $nearest = $index;
      }
    }
    if ($nearest !== null) {
      return $nearest;
    }
    $best = self::PLACE_RADIUS;
    foreach ($_places as $index => $place) {
      // Un favori n'est rattaché que dans son propre rayon, qui peut être plus serré que le rayon court
      if (!empty($place['favorite'])) {
        continue;
      }
      $distance = self::distanceMeters($_lat, $_lng, $place['lat'], $place['lng']);
      if ($distance <= $best) {
        $best = $distance;
        $nearest = $index;
      }
    }
    return $nearest;
  }

  public function rememberPlace($_lat, $_lng, $_address) {
    if (!is_numeric($_lat) || !is_numeric($_lng) || $_address == '') {
      return;
    }
    $places = $this->getPlaces();
    $index = self::nearestPlace($places, $_lat, $_lng);
    if ($index !== null) {
      // L'adresse d'un favori ne suit pas chaque stationnement fait dans son rayon : elle reste celle de son point
      if ($places[$index]['address'] == $_address || !empty($places[$index]['addressFixed']) || (!empty($places[$index]['favorite']) && $places[$index]['address'] != '')) {
        return;
      }
      $places[$index]['address'] = $_address;
    } else {
      $places[] = array('lat' => (float) $_lat, 'lng' => (float) $_lng, 'address' => $_address);
    }
    config::save($this->placesKey(), json_encode(self::capPlaces($places), JSON_UNESCAPED_UNICODE), __CLASS__);
  }

  // Libellé d'une position : nom donné au lieu par l'utilisateur, à défaut son adresse, à défaut l'adresse ou les coordonnées fournies
  private function placeLabel($_lat, $_lng, $_default = null) {
    $places = $this->getPlaces();
    $index = self::nearestPlace($places, $_lat, $_lng);
    if ($index !== null) {
      return (isset($places[$index]['name']) && $places[$index]['name'] != '') ? $places[$index]['name'] : $places[$index]['address'];
    }
    return ($_default !== null) ? $_default : round($_lat, 5) . ', ' . round($_lng, 5);
  }

  // Prix du kWh à une position : celui du lieu s'il en a un, sinon celui de la configuration du plugin ; 0 si aucun
  private function priceAt($_position) {
    if ($_position !== null) {
      $places = $this->getPlaces();
      $index = self::nearestPlace($places, $_position[0], $_position[1]);
      $key = $this->placePriceKey();
      if ($index !== null && isset($places[$index][$key]) && $places[$index][$key] > 0) {
        return (float) $places[$index][$key];
      }
    }
    return $this->getDefaultPrice();
  }

  // Position du véhicule stationné à un instant donné : (latitude, longitude), null si inconnue
  private function positionAt($_datetime) {
    $lat = $this->valueAt('latitude', $_datetime);
    $lng = $this->valueAt('longitude', $_datetime);
    return ($lat === null || $lng === null) ? null : array($lat, $lng);
  }

  // Lieu où le véhicule était stationné à un instant donné ; null si inconnu
  private function placeAt($_datetime) {
    $position = $this->positionAt($_datetime);
    return ($position === null) ? null : $this->placeLabel($position[0], $position[1]);
  }

  // Donne ou retire le nom et le prix du kWh d'un lieu mémorisé ; la commande « Lieu » suit si le véhicule y est stationné
  // $_favorite et $_radius : null pour ne pas y toucher. Un favori rattache à lui tout stationnement fait dans son rayon.
  // $_address : null pour garder l'adresse ; sinon l'adresse affichée, corrigée par l'utilisateur.
  public function renamePlace($_index, $_name, $_price = '', $_favorite = null, $_radius = null, $_address = null) {
    $places = $this->getPlaces();
    if (!isset($places[$_index])) {
      throw new Exception(__('Lieu inconnu', __FILE__));
    }
    $places[$_index]['name'] = trim($_name);
    if ($_address !== null && trim($_address) != $places[$_index]['address']) {
      $places[$_index]['address'] = trim($_address);
      // Une adresse corrigée à la main n'est plus remplacée par celle que Škoda donne à chaque stationnement
      $places[$_index]['addressFixed'] = true;
    }
    $price = (float) str_replace(',', '.', $_price);
    $key = $this->placePriceKey();
    if ($price > 0) {
      $places[$_index][$key] = $price;
    } else {
      unset($places[$_index][$key]);
    }
    if ($_favorite !== null) {
      if ($_favorite && $places[$_index]['name'] == '') {
        throw new Exception(__('Donnez d\'abord un nom à ce lieu pour en faire un favori', __FILE__));
      }
      if ($_favorite) {
        $places[$_index]['favorite'] = true;
      } else {
        unset($places[$_index]['favorite'], $places[$_index]['radius']);
      }
    }
    if ($_radius !== null && !empty($places[$_index]['favorite'])) {
      $radius = (float) str_replace(',', '.', $_radius);
      if ($radius > 0) {
        $places[$_index]['radius'] = round(max(10, min(5000, $radius)));
      } else {
        unset($places[$_index]['radius']);
      }
    }
    $this->savePlaces($places);
  }

  // Situe une adresse écrite par l'utilisateur : (lat, lng, label). La recherche est faite par la box auprès du
  // service Nominatim d'OpenStreetMap, à la demande seulement : le navigateur ne peut pas le faire lui-même, la règle
  // de sécurité que Jeedom installe dans Apache (Content-Security-Policy, default-src 'self') l'interdit.
  public static function searchAddress($_address) {
    $address = trim((string) $_address);
    if ($address == '') {
      throw new Exception(__('Saisissez une adresse, ou cliquez sur la carte pour placer le point', __FILE__));
    }
    $found = self::askNominatim('search?format=jsonv2&limit=1&q=' . rawurlencode($address));
    if (!is_array($found)) {
      throw new Exception(__('La recherche d\'adresse n\'a pas abouti. Cliquez sur la carte pour placer le point vous-même', __FILE__));
    }
    if (count($found) == 0 || !isset($found[0]['lat']) || !isset($found[0]['lon'])) {
      throw new Exception(__('Adresse introuvable. Vérifiez-la, ou cliquez sur la carte pour placer le point vous-même', __FILE__));
    }
    return array('lat' => (float) $found[0]['lat'], 'lng' => (float) $found[0]['lon'], 'label' => isset($found[0]['display_name']) ? (string) $found[0]['display_name'] : $address);
  }

  // Question posée au service Nominatim d'OpenStreetMap ; réponse décodée, null si le service n'a pas répondu
  private static function askNominatim($_query) {
    $ch = curl_init('https://nominatim.openstreetmap.org/' . $_query);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
    curl_setopt($ch, CURLOPT_TIMEOUT, 12);
    // Le service demande que chaque application s'identifie
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Jeedom-plugin-MySkoda/' . self::getVersionLabel(), 'Accept-Language: ' . str_replace('_', '-', (string) config::byKey('language', 'core', 'fr_FR'))));
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($raw === false || $status != 200) ? null : json_decode($raw, true);
  }

  // Adresse d'un point de la carte, pour la proposer quand l'utilisateur déplace un lieu ; même service et mêmes
  // précautions que searchAddress. Écrite comme les adresses que donne Škoda : « rue numéro, code postal ville, pays ».
  public static function findAddress($_lat, $_lng) {
    if (!is_numeric($_lat) || !is_numeric($_lng) || abs((float) $_lat) > 90 || abs((float) $_lng) > 180) {
      throw new Exception(__('Placez d\'abord le point sur la carte', __FILE__));
    }
    $found = self::askNominatim('reverse?format=jsonv2&addressdetails=1&zoom=18&lat=' . rawurlencode((float) $_lat) . '&lon=' . rawurlencode((float) $_lng));
    if (!is_array($found) || !isset($found['address']) || !is_array($found['address'])) {
      throw new Exception(__('L\'adresse de ce point n\'a pas été trouvée', __FILE__));
    }
    $parts = $found['address'];
    $pick = function ($_keys) use ($parts) {
      foreach ($_keys as $key) {
        if (isset($parts[$key]) && trim((string) $parts[$key]) != '') {
          return trim((string) $parts[$key]);
        }
      }
      return '';
    };
    $street = trim($pick(array('road', 'pedestrian', 'footway', 'path', 'square', 'hamlet')) . ' ' . $pick(array('house_number')));
    $city = trim($pick(array('postcode')) . ' ' . $pick(array('city', 'town', 'village', 'municipality')));
    $label = implode(', ', array_filter(array($street, $city, $pick(array('country')))));
    if ($label == '') {
      throw new Exception(__('L\'adresse de ce point n\'a pas été trouvée', __FILE__));
    }
    return array('label' => $label);
  }

  // Crée un lieu favori à un point choisi par l'utilisateur (adresse cherchée ou clic sur la carte), avec l'adresse
  // qu'il a écrite : utile quand celle que Škoda donne pour un stationnement est fausse
  public function addPlace($_lat, $_lng, $_name, $_address = '', $_radius = '') {
    if (!is_numeric($_lat) || !is_numeric($_lng) || abs((float) $_lat) > 90 || abs((float) $_lng) > 180) {
      throw new Exception(__('Placez d\'abord le point sur la carte', __FILE__));
    }
    if (trim($_name) == '') {
      throw new Exception(__('Donnez un nom à ce favori (Maison, Travail…)', __FILE__));
    }
    $place = array('lat' => (float) $_lat, 'lng' => (float) $_lng, 'address' => trim($_address), 'name' => trim($_name), 'favorite' => true);
    if (trim($_address) != '') {
      $place['addressFixed'] = true;
    }
    $radius = (float) str_replace(',', '.', $_radius);
    if ($radius > 0) {
      $place['radius'] = round(max(10, min(5000, $radius)));
    }
    $places = $this->getPlaces();
    $places[] = $place;
    $this->savePlaces($places);
  }

  // Couleurs proposées pour un favori sur les cartes : code => nom. La première est celle d'un favori sans couleur choisie.
  private static $_placeColors = array(
    '#554E5E' => 'Gris violet',
    '#3a9d3a' => 'Vert',
    '#d9534f' => 'Rouge',
    '#2a7ab9' => 'Bleu',
    '#f0932b' => 'Orange',
    '#8e44ad' => 'Violet',
    '#e6b800' => 'Jaune',
    '#e84393' => 'Rose',
    '#16a085' => 'Turquoise',
    '#34495e' => 'Bleu nuit',
  );

  public static function placeColors() {
    $colors = array();
    foreach (self::$_placeColors as $code => $name) {
      $colors[] = array($code, __($name, __FILE__));
    }
    return $colors;
  }

  // Donne sa couleur à un favori
  public function colorPlace($_index, $_color) {
    $places = $this->getPlaces();
    if (!isset($places[$_index])) {
      throw new Exception(__('Lieu inconnu', __FILE__));
    }
    if (!isset(self::$_placeColors[$_color])) {
      throw new Exception(__('Couleur inconnue', __FILE__));
    }
    $places[$_index]['color'] = $_color;
    $this->savePlaces($places);
  }

  // Monte (-1) ou descend (+1) un favori d'un rang parmi les favoris : l'ordre des favoris est celui de la liste
  public function reorderPlace($_index, $_direction) {
    $places = array_values($this->getPlaces());
    if (!isset($places[$_index]) || empty($places[$_index]['favorite'])) {
      throw new Exception(__('Lieu inconnu', __FILE__));
    }
    $step = ($_direction < 0) ? -1 : 1;
    for ($other = $_index + $step; isset($places[$other]); $other += $step) {
      if (!empty($places[$other]['favorite'])) {
        $moved = $places[$_index];
        $places[$_index] = $places[$other];
        $places[$other] = $moved;
        $this->savePlaces($places);
        return;
      }
    }
  }

  // Place un favori au rang voulu parmi les favoris (0 = premier), les autres se décalant : c'est le glisser-déposer
  // de la liste. Les lieux ordinaires gardent leur place dans le tableau enregistré.
  public function rankPlace($_index, $_rank) {
    $places = array_values($this->getPlaces());
    if (!isset($places[$_index]) || empty($places[$_index]['favorite'])) {
      throw new Exception(__('Lieu inconnu', __FILE__));
    }
    $slots = array();
    foreach ($places as $index => $place) {
      if (!empty($place['favorite'])) {
        $slots[] = $index;
      }
    }
    $order = $slots;
    $from = array_search($_index, $order);
    $rank = max(0, min(count($order) - 1, (int) $_rank));
    array_splice($order, $from, 1);
    array_splice($order, $rank, 0, array($_index));
    $result = $places;
    foreach ($slots as $position => $slot) {
      $result[$slot] = $places[$order[$position]];
    }
    $this->savePlaces($result);
  }

  // Déplace le point d'un lieu existant, affiné par l'utilisateur sur la carte
  // $_address : null pour garder l'adresse ; sinon l'adresse du nouveau point, acceptée par l'utilisateur
  public function movePlace($_index, $_lat, $_lng, $_address = null) {
    $places = $this->getPlaces();
    if (!isset($places[$_index])) {
      throw new Exception(__('Lieu inconnu', __FILE__));
    }
    if (!is_numeric($_lat) || !is_numeric($_lng) || abs((float) $_lat) > 90 || abs((float) $_lng) > 180) {
      throw new Exception(__('Placez d\'abord le point sur la carte', __FILE__));
    }
    $places[$_index]['lat'] = (float) $_lat;
    $places[$_index]['lng'] = (float) $_lng;
    if ($_address !== null && trim($_address) != '') {
      $places[$_index]['address'] = trim($_address);
      $places[$_index]['addressFixed'] = true;
    }
    $this->savePlaces($places);
  }

  // Retire un lieu mémorisé (créé par une position erronée, ou devenu inutile)
  public function deletePlace($_index) {
    $places = $this->getPlaces();
    if (!isset($places[$_index])) {
      throw new Exception(__('Lieu inconnu', __FILE__));
    }
    unset($places[$_index]);
    $this->savePlaces($places);
  }

  // Coordonnées du domicile saisies dans la configuration générale de Jeedom ; null si elles ne le sont pas
  public static function jeedomHome() {
    $lat = config::byKey('info::latitude');
    $lng = config::byKey('info::longitude');
    return (is_numeric($lat) && is_numeric($lng) && ((float) $lat != 0 || (float) $lng != 0)) ? array((float) $lat, (float) $lng) : null;
  }

  // Crée le favori « Maison » aux coordonnées du domicile connues de Jeedom, sans adresse à saisir
  public function addHomePlace() {
    $home = self::jeedomHome();
    if ($home === null) {
      throw new Exception(__('Les coordonnées du domicile ne sont pas renseignées dans la configuration générale de Jeedom', __FILE__));
    }
    $places = $this->getPlaces();
    foreach ($places as $place) {
      if (!empty($place['favorite']) && self::distanceMeters($home[0], $home[1], $place['lat'], $place['lng']) <= self::PLACE_RADIUS) {
        throw new Exception(__('Un favori existe déjà à cet endroit', __FILE__));
      }
    }
    $places[] = array('lat' => $home[0], 'lng' => $home[1], 'address' => '', 'name' => __('Maison', __FILE__), 'favorite' => true);
    $this->savePlaces($places);
  }

  // Ramène la liste à PLACES_MAX lieux en retirant les lieux ordinaires les plus anciens ; un favori n'est jamais retiré
  private static function capPlaces($_places) {
    $_places = array_values($_places);
    foreach (array_keys($_places) as $index) {
      if (count($_places) <= self::PLACES_MAX) {
        break;
      }
      if (empty($_places[$index]['favorite'])) {
        unset($_places[$index]);
      }
    }
    return array_values($_places);
  }

  // Indice du favori dont le rayon contient un lieu ordinaire ; null si aucun, ou si le lieu est lui-même un favori
  private static function coveringFavorite($_places, $_index) {
    if (!isset($_places[$_index]) || !empty($_places[$_index]['favorite'])) {
      return null;
    }
    $default = self::favoriteRadius();
    foreach ($_places as $index => $place) {
      if (empty($place['favorite'])) {
        continue;
      }
      $radius = (isset($place['radius']) && is_numeric($place['radius']) && $place['radius'] > 0) ? (float) $place['radius'] : $default;
      if (self::distanceMeters($_places[$_index]['lat'], $_places[$_index]['lng'], $place['lat'], $place['lng']) <= $radius) {
        return $index;
      }
    }
    return null;
  }

  // Enregistre les lieux, puis remet à jour la commande « Lieu » et la tuile si le véhicule est stationné
  private function savePlaces($_places) {
    // Un lieu ordinaire tombé dans le rayon d'un favori ne sert plus : le favori passe toujours avant lui. S'il n'a
    // reçu ni nom, ni prix, ni adresse corrigée, il est retiré (il se recréera seul si le favori rétrécit) ; sinon il
    // reste, et la liste le signale comme couvert, pour ne rien effacer de ce que l'utilisateur a saisi.
    foreach (array_keys($_places) as $index) {
      $place = $_places[$index];
      $touched = (isset($place['name']) && $place['name'] != '') || !empty($place['price']) || !empty($place['priceFuel']) || !empty($place['addressFixed']);
      if (!$touched && self::coveringFavorite($_places, $index) !== null) {
        unset($_places[$index]);
      }
    }
    config::save($this->placesKey(), json_encode(self::capPlaces($_places), JSON_UNESCAPED_UNICODE), __CLASS__);
    $lat = $this->getCmd('info', 'latitude');
    $lng = $this->getCmd('info', 'longitude');
    if (is_object($lat) && is_object($lng) && is_numeric($lat->execCmd()) && is_numeric($lng->execCmd())) {
      $this->updateInfo('place', $this->placeLabel($lat->execCmd(), $lng->execCmd()));
      $this->refreshWidget();
    }
  }

  // Lieux et position actuelle pour la carte
  public function getMap() {
    $lat = $this->getCmd('info', 'latitude');
    $lng = $this->getCmd('info', 'longitude');
    $now = $this->getNow();
    $now['position'] = (is_object($lat) && is_object($lng) && is_numeric($lat->execCmd()) && is_numeric($lng->execCmd()))
      ? array((float) $lat->execCmd(), (float) $lng->execCmd()) : null;
    // Le prix montré pour chaque lieu est celui de l'énergie regardée
    $places = $this->getPlaces();
    $key = $this->placePriceKey();
    foreach ($places as $index => $place) {
      $places[$index]['price'] = isset($place[$key]) ? $place[$key] : null;
      unset($places[$index]['priceFuel']);
      // Lieu ordinaire couvert par un favori : la liste le dit, il n'apparaît plus sur les trajets
      $covering = self::coveringFavorite($places, $index);
      $places[$index]['coveredBy'] = ($covering === null) ? null : $places[$covering]['name'];
    }
    // Dernier jour où le véhicule a roulé (sur 90 jours) : la carte s'y ouvre quand le jour courant n'a aucun trajet
    $trips = $this->periods('moving', date('Y-m-d 00:00:00', time() - 90 * 86400), date('Y-m-d H:i:s'));
    $last = end($trips);
    // Le favori « Maison » peut être créé d'un clic si Jeedom connaît le domicile et qu'aucun favori n'y est déjà
    $home = self::jeedomHome();
    $homeAvailable = ($home !== null);
    foreach ($places as $place) {
      if ($home !== null && !empty($place['favorite']) && self::distanceMeters($home[0], $home[1], $place['lat'], $place['lng']) <= self::favoriteRadius()) {
        $homeAvailable = false;
      }
    }
    return array('places' => $places, 'now' => $now, 'lastTripDay' => ($last === false) ? null : substr($last[0], 0, 10),
      'defaultRadius' => self::favoriteRadius(), 'homeAvailable' => $homeAvailable,
      'colors' => self::placeColors(), 'defaultColor' => array_keys(self::$_placeColors)[0]);
  }

  // Valeurs d'une info historisée sur un intervalle, précédées de la valeur au début : liste de (date, valeur)
  private function curve($_logicalId, $_start, $_end) {
    $cmd = $this->getCmd('info', $_logicalId);
    if (!is_object($cmd)) {
      return array();
    }
    $points = array();
    $first = $this->valueAt($_logicalId, $_start);
    if ($first !== null) {
      $points[] = array($_start, $first);
    }
    foreach (history::all($cmd->getId(), $_start, $_end) as $history) {
      $points[] = array($history->getDatetime(), (float) $history->getValue());
    }
    return $points;
  }

  // Valeur d'une info historisée à un instant donné (dernière valeur connue avant) ; null si aucune
  private function valueAt($_logicalId, $_datetime) {
    $cmd = $this->getCmd('info', $_logicalId);
    if (!is_object($cmd)) {
      return null;
    }
    $history = history::byCmdIdAtDatetime($cmd->getId(), $_datetime);
    return is_object($history) ? (float) $history->getValue() : null;
  }

  // Périodes pendant lesquelles une info binaire historisée vaut 1 : liste de (début, fin, en cours).
  // Une période commencée avant l'intervalle part du début de l'intervalle ; une période non terminée s'arrête à sa fin ou à maintenant.
  private function periods($_logicalId, $_start, $_end) {
    $cmd = $this->getCmd('info', $_logicalId);
    if (!is_object($cmd)) {
      return array();
    }
    $periods = array();
    $open = null;
    $before = history::byCmdIdAtDatetime($cmd->getId(), $_start);
    if (is_object($before) && (float) $before->getValue() >= 0.5) {
      $open = $_start;
    }
    foreach (history::all($cmd->getId(), $_start, $_end) as $history) {
      $on = ((float) $history->getValue() >= 0.5);
      if ($on && $open === null) {
        $open = $history->getDatetime();
      } elseif (!$on && $open !== null) {
        if ($history->getDatetime() > $open) {
          $periods[] = array($open, $history->getDatetime(), false);
        }
        $open = null;
      }
    }
    if ($open !== null) {
      $periods[] = array($open, min($_end, date('Y-m-d H:i:s')), true);
    }
    return $periods;
  }

  private static function difference($_from, $_to, $_precision = 1) {
    return ($_from === null || $_to === null) ? null : round($_from - $_to, $_precision);
  }

  private static function minutesBetween($_start, $_end) {
    return max(0, (int) round((strtotime($_end) - strtotime($_start)) / 60));
  }

  // Énergie suivie par le journal : « fuel » pour un véhicule qui ne remonte que du carburant, « electric » sinon
  // (un hybride rechargeable est suivi par sa batterie)
  public function getKind() {
    if ($this->_energy !== null) {
      return $this->_energy;
    }
    return (!is_object($this->getCmd('info', 'battery_level')) && is_object($this->getCmd('info', 'fuel_level'))) ? 'fuel' : 'electric';
  }

  // Énergies que le véhicule remonte : une seule en général, les deux pour un hybride rechargeable
  public function getEnergies() {
    $energies = array();
    if (is_object($this->getCmd('info', 'battery_level'))) {
      $energies[] = 'electric';
    }
    if (is_object($this->getCmd('info', 'fuel_level'))) {
      $energies[] = 'fuel';
    }
    return (count($energies) > 0) ? $energies : array('electric');
  }

  // Pour un hybride, choisit l'énergie sous laquelle le journal est calculé (batterie et recharges, ou carburant et pleins)
  private $_energy = null;

  public function setEnergy($_energy) {
    $this->_energy = in_array($_energy, $this->getEnergies(), true) ? $_energy : null;
    return $this;
  }

  // Clé du prix propre à un lieu : le kWh et le litre n'ont pas le même prix au même endroit
  private function placePriceKey() {
    return ($this->getKind() == 'fuel') ? 'priceFuel' : 'price';
  }

  private function levelId() {
    return ($this->getKind() == 'fuel') ? 'fuel_level' : 'battery_level';
  }

  private function rangeId() {
    if ($this->getKind() != 'fuel') {
      return 'range';
    }
    return is_object($this->getCmd('info', 'fuel_range')) ? 'fuel_range' : 'total_range';
  }

  // Capacité de la batterie en kWh ou contenance du réservoir en litres ; 0 si non saisie
  public function getCapacity() {
    return (float) str_replace(',', '.', $this->getConfiguration(($this->getKind() == 'fuel') ? 'tank_capacity' : 'battery_capacity'));
  }

  // Prix général du kWh ou du litre, saisi dans la configuration du plugin ; 0 si non saisi
  public function getDefaultPrice() {
    if ($this->getKind() == 'fuel') {
      // Essence, diesel, E85… : le prix du litre dépend du véhicule, il peut donc être saisi sur l'équipement
      $own = (float) str_replace(',', '.', $this->getConfiguration('fuel_price'));
      if ($own > 0) {
        return $own;
      }
    }
    return (float) str_replace(',', '.', config::byKey(($this->getKind() == 'fuel') ? 'fuel_price' : 'kwh_price', __CLASS__, 0));
  }

  // Baisse minimale du niveau, en points, pour calculer une consommation : le niveau est un pourcentage entier,
  // une consommation calculée sur un ou deux points n'a pas de sens (décidé avec l'utilisateur le 2026-10-05)
  const CONSUMPTION_MIN_DROP = 5;

  // Consommation entre deux recharges ou deux pleins successifs (liste du plus ancien au plus récent) :
  // ce qui a été consommé depuis la fin du précédent, rapporté à la distance parcourue entre les deux
  private function addIntervals($_rows) {
    $capacity = $this->getCapacity();
    foreach ($_rows as $index => $row) {
      $_rows[$index]['sinceDistance'] = null;
      $_rows[$index]['sinceConsumption'] = null;
      if ($index == 0) {
        continue;
      }
      $previous = $_rows[$index - 1];
      $distance = self::difference($this->valueAt('mileage', $row['start']), $this->valueAt('mileage', $previous['end']), 0);
      $used = self::difference($previous['levelEnd'], $row['levelStart'], 0);
      if ($distance === null || $distance <= 0) {
        continue;
      }
      $_rows[$index]['sinceDistance'] = $distance;
      if ($capacity > 0 && $used !== null && $used >= self::CONSUMPTION_MIN_DROP) {
        $_rows[$index]['sinceConsumption'] = round($capacity * $used / $distance, 1);
      }
    }
    return $_rows;
  }

  // Hausse minimale du niveau de carburant entre deux relevés, en points, pour y voir un plein
  const REFUEL_MIN = 5;

  // Pleins de la période, du plus récent au plus ancien, d'après les hausses du niveau de carburant
  private function getRefuels($_start, $_end) {
    $cmd = $this->getCmd('info', 'fuel_level');
    if (!is_object($cmd)) {
      return array();
    }
    $capacity = $this->getCapacity();
    $refuels = array();
    $previous = $this->valueAt('fuel_level', $_start);
    foreach (history::all($cmd->getId(), $_start, $_end) as $history) {
      $level = (float) $history->getValue();
      if ($previous !== null && $level - $previous >= self::REFUEL_MIN) {
        $at = $history->getDatetime();
        $position = $this->positionAt($at);
        $price = $this->priceAt($position);
        $quantity = ($capacity > 0) ? round($capacity * ($level - $previous) / 100, 1) : null;
        $refuels[] = array(
          'type' => 'charge',
          'start' => $at,
          'end' => $at,
          'ongoing' => false,
          'minutes' => 0,
          'place' => $this->placeAt($at),
          'position' => $position,
          'price' => ($price > 0) ? $price : null,
          'levelStart' => $previous,
          'levelEnd' => $level,
          'energy' => $quantity,
          'power' => null,
          'maxPower' => null,
          'rangeGained' => self::difference($this->valueAt($this->rangeId(), date('Y-m-d H:i:s', strtotime($at) + 120)), $this->valueAt($this->rangeId(), date('Y-m-d H:i:s', strtotime($at) - 1)), 0),
          'cost' => ($quantity !== null && $price > 0) ? round($quantity * $price, 2) : null,
        );
      }
      $previous = $level;
    }
    return array_reverse($this->addIntervals($refuels));
  }

  // Trajets de la période, du plus récent au plus ancien, d'après l'historique de « En déplacement »
  public function getTrips($_start, $_end) {
    $capacity = $this->getCapacity();
    // Le coût d'un trajet est estimé au prix général de la configuration du plugin
    $price = $this->getDefaultPrice();
    $trips = array();
    foreach ($this->periods('moving', $_start, $_end) as $period) {
      list($start, $end, $ongoing) = $period;
      $minutes = self::minutesBetween($start, $end);
      $distance = self::difference($this->valueAt('mileage', $end), $this->valueAt('mileage', $start), 0);
      $levelStart = $this->valueAt($this->levelId(), $start);
      $levelEnd = $this->valueAt($this->levelId(), $end);
      $used = self::difference($levelStart, $levelEnd, 0);
      $energy = ($capacity > 0 && $used !== null) ? round($capacity * $used / 100, 1) : null;
      $fromPosition = $this->positionAt($start);
      $toPosition = $ongoing ? null : $this->positionAt(date('Y-m-d H:i:s', strtotime($end) + 120));
      $fromLabel = $this->placeAt($start);
      $toLabel = $ongoing ? null : $this->placeAt(date('Y-m-d H:i:s', strtotime($end) + 120));
      $trips[] = array(
        'type' => 'trip',
        'start' => $start,
        'end' => $end,
        'ongoing' => $ongoing,
        'minutes' => $minutes,
        'from' => $this->placeAt($start),
        'fromPosition' => $fromPosition,
        'toPosition' => $toPosition,
        // Aller-retour : le véhicule est revenu se garer là d'où il était parti. Škoda ne donnant aucune position
        // en route, la carte n'a pas de trait à tracer et montre le lieu par un cercle.
        // Deux lieux portant le même nom comptent pour un seul : la position transmise par la voiture peut s'écarter
        // de plus de cent mètres d'un stationnement à l'autre au même endroit (constaté le 2026-10-07, 176 m).
        'roundTrip' => (is_array($fromPosition) && is_array($toPosition)
          && (self::distanceMeters($fromPosition[0], $fromPosition[1], $toPosition[0], $toPosition[1]) <= self::PLACE_RADIUS
            || ($fromLabel !== null && $fromLabel !== '' && $fromLabel === $toLabel))),
        // La position d'arrivée est écrite juste après la fin du trajet, dans la même lecture
        'to' => $ongoing ? null : $this->placeAt(date('Y-m-d H:i:s', strtotime($end) + 120)),
        'distance' => $distance,
        'speed' => ($distance !== null && $minutes > 0) ? round($distance / ($minutes / 60)) : null,
        'levelStart' => $levelStart,
        'levelEnd' => $levelEnd,
        'energy' => $energy,
        'consumption' => ($energy !== null && $distance > 0 && $used >= self::CONSUMPTION_MIN_DROP) ? round($energy / $distance * 100, 1) : null,
        'rangeUsed' => self::difference($this->valueAt($this->rangeId(), $start), $this->valueAt($this->rangeId(), $end), 0),
        'cost' => ($energy !== null && $energy > 0 && $price > 0) ? round($energy * $price, 2) : null,
      );
    }
    return array_reverse($trips);
  }

  // Vrai si l'utilisateur a déclaré, sur l'équipement, que la charge de ce véhicule est démarrée et arrêtée par un
  // scénario (surplus solaire, batterie domestique, heures creuses)
  public function isManagedCharging() {
    return $this->getConfiguration('managed_charging', 0) == 1;
  }

  // Recharges de la période : liste de (début, fin, en cours), dans l'ordre du temps.
  // Cas général : une recharge par période où la voiture charge.
  // Recharge pilotée (option de l'équipement) : une recharge par branchement du câble. Un scénario qui suit le surplus
  // solaire démarre et arrête la charge au gré des nuages ; compté période par période, cela donnait huit « recharges »
  // de quelques minutes à 0 kWh pour une journée où la batterie avait gagné 5 points (constaté chez un testeur le
  // 2026-10-07). Toutes les reprises faites pendant que le câble reste branché forment donc une seule recharge, qui
  // porte en plus sa durée de charge réelle (« minutes »), son nombre de reprises et sa durée de branchement.
  private function chargeSessions($_start, $_end) {
    $charging = $this->periods('charging', $_start, $_end);
    if (!$this->isManagedCharging() || !is_object($this->getCmd('info', 'plug_connected'))) {
      return $charging;
    }
    $sessions = array();
    $used = array();
    foreach ($this->periods('plug_connected', $_start, $_end) as $plug) {
      $inside = array();
      foreach ($charging as $index => $period) {
        // Charge et branchement viennent du même relevé de la voiture : leurs heures coïncident à la seconde près
        if (!isset($used[$index]) && $period[0] >= $plug[0] && $period[0] <= $plug[1]) {
          $inside[] = $period;
          $used[$index] = true;
        }
      }
      if (count($inside) == 0) {
        continue;
      }
      $last = $inside[count($inside) - 1];
      $minutes = 0;
      foreach ($inside as $period) {
        $minutes += self::minutesBetween($period[0], $period[1]);
      }
      $sessions[] = array($inside[0][0], $last[1], $last[2], 'minutes' => $minutes, 'resumes' => count($inside), 'pluggedMinutes' => self::minutesBetween($plug[0], $plug[1]));
    }
    // Une période de charge sans branchement connu (historique du câble incomplet) reste une recharge à elle seule
    foreach ($charging as $index => $period) {
      if (!isset($used[$index])) {
        $sessions[] = $period;
      }
    }
    usort($sessions, function ($_a, $_b) {
      return strcmp($_a[0], $_b[0]);
    });
    return $sessions;
  }

  // Recharges de la période, de la plus récente à la plus ancienne, d'après l'historique de « En charge »
  public function getCharges($_start, $_end) {
    if ($this->getKind() == 'fuel') {
      return $this->getRefuels($_start, $_end);
    }
    $capacity = $this->getCapacity();
    $powerCmd = $this->getCmd('info', 'charge_power');
    $charges = array();
    foreach ($this->chargeSessions($_start, $_end) as $session) {
      list($start, $end, $ongoing) = $session;
      // Durée de charge réelle : pour une recharge pilotée, les pauses entre deux reprises n'en font pas partie
      $minutes = isset($session['minutes']) ? $session['minutes'] : self::minutesBetween($start, $end);
      $levelStart = $this->valueAt($this->levelId(), $start);
      $levelEnd = $this->valueAt($this->levelId(), $end);
      $gained = self::difference($levelEnd, $levelStart, 0);
      $energy = ($capacity > 0 && $gained !== null) ? round($capacity * $gained / 100, 1) : null;
      $maxPower = null;
      if (is_object($powerCmd)) {
        foreach (history::all($powerCmd->getId(), $start, $end) as $history) {
          $maxPower = max((float) $maxPower, (float) $history->getValue());
        }
      }
      $position = $this->positionAt($start);
      $price = $this->priceAt($position);
      $charges[] = array(
        'type' => 'charge',
        'start' => $start,
        'end' => $end,
        'ongoing' => $ongoing,
        'minutes' => $minutes,
        'place' => $this->placeAt($start),
        'position' => $position,
        'price' => ($price > 0) ? $price : null,
        'levelStart' => $levelStart,
        'levelEnd' => $levelEnd,
        'energy' => $energy,
        // Sans énergie mesurée (niveau resté au même point entier), une puissance moyenne de 0 kW serait fausse : pas de valeur
        'power' => ($energy !== null && $energy > 0 && $minutes > 0) ? round($energy / ($minutes / 60), 1) : null,
        'maxPower' => ($maxPower === null) ? null : round($maxPower, 1),
        'rangeGained' => self::difference($this->valueAt('range', $end), $this->valueAt('range', $start), 0),
        'cost' => ($energy !== null && $price > 0) ? round($energy * $price, 2) : null,
        // Recharge pilotée : nombre de reprises de charge et durée pendant laquelle le câble est resté branché
        'resumes' => isset($session['resumes']) ? $session['resumes'] : 1,
        'pluggedMinutes' => isset($session['pluggedMinutes']) ? $session['pluggedMinutes'] : null,
      );
    }
    return array_reverse($this->addIntervals($charges));
  }

  private static function total($_rows, $_key) {
    $total = 0;
    foreach ($_rows as $row) {
      $total += (float) $row[$_key];
    }
    return $total;
  }

  // État courant du véhicule pour le bandeau du panneau
  public function getNow() {
    $value = function ($_logicalId) {
      $cmd = $this->getCmd('info', $_logicalId);
      if (!is_object($cmd)) {
        return null;
      }
      $result = $cmd->execCmd();
      return ($result === '' || $result === null) ? null : $result;
    };
    $state = 'parked';
    $since = null;
    foreach (array('moving' => 'trip', 'charging' => 'charge') as $logicalId => $name) {
      $cmd = $this->getCmd('info', $logicalId);
      if (is_object($cmd) && $cmd->execCmd() == 1) {
        $state = $name;
        $since = $cmd->getValueDate();
      }
    }
    if ($state == 'parked') {
      $cmd = $this->getCmd('info', 'moving');
      $since = is_object($cmd) ? $cmd->getValueDate() : null;
    }
    return array(
      'name' => $this->getName(),
      'state' => $state,
      'since' => $since,
      'place' => ($state == 'trip') ? null : (($value('place') !== null) ? $value('place') : $value('address')),
      'range' => $value($this->rangeId()),
      'level' => $value($this->levelId()),
      'fuel' => ($this->getKind() == 'fuel'),
      'mileage' => $value('mileage'),
      'updated' => $value('data_timestamp'),
      'unavailableSince' => $value('unavailable_since'),
    );
  }

  // Commandes dont le journal de bord a besoin et dont l'historisation a été décochée : liste de leurs noms.
  // Le journal reconstitue trajets, recharges et pleins à partir de l'historique Jeedom : sans lui, il reste vide.
  public function getMissingHistory() {
    $needed = array('moving', 'mileage', 'latitude', 'longitude', $this->levelId(), $this->rangeId());
    if ($this->getKind() != 'fuel') {
      $needed = array_merge($needed, array('charging', 'charge_power'));
      // Une recharge pilotée se délimite par le branchement du câble
      if ($this->isManagedCharging()) {
        $needed[] = 'plug_connected';
      }
    }
    $missing = array();
    foreach (array_unique($needed) as $logicalId) {
      $cmd = $this->getCmd('info', $logicalId);
      if (is_object($cmd) && $cmd->getIsHistorized() != 1) {
        $missing[] = $cmd->getName();
      }
    }
    return $missing;
  }

  // Journal d'une journée : trajets, recharges et totaux
  public function getDay($_date) {
    $start = $_date . ' 00:00:00';
    $end = min($_date . ' 23:59:59', date('Y-m-d H:i:s'));
    $trips = ($end > $start) ? $this->getTrips($start, $end) : array();
    $charges = ($end > $start) ? $this->getCharges($start, $end) : array();
    $elapsed = ($end > $start) ? self::minutesBetween($start, $end) : 0;
    $tripMinutes = self::total($trips, 'minutes');
    $distance = self::total($trips, 'distance');
    $measured = array_filter($trips, function ($_trip) {
      return $_trip['energy'] !== null && $_trip['distance'] > 0;
    });
    $measuredDistance = self::total($measured, 'distance');
    $measuredDrop = self::total($measured, 'levelStart') - self::total($measured, 'levelEnd');
    $hasEnergy = count(array_filter($charges, function ($_charge) {
      return $_charge['energy'] !== null;
    })) > 0;
    $hasCost = count(array_filter($charges, function ($_charge) {
      return $_charge['cost'] !== null;
    })) > 0;
    return array(
      'date' => $_date,
      'start' => $start,
      'end' => $end,
      'trips' => $trips,
      'charges' => $charges,
      'summary' => array(
        'parkedMinutes' => max(0, $elapsed - $tripMinutes),
        'tripCount' => count($trips),
        'tripMinutes' => $tripMinutes,
        'distance' => $distance,
        'speed' => ($tripMinutes > 0) ? round($distance / ($tripMinutes / 60)) : null,
        'consumption' => ($measuredDistance > 0 && $measuredDrop >= self::CONSUMPTION_MIN_DROP) ? round(self::total($measured, 'energy') / $measuredDistance * 100, 1) : null,
        'chargeCount' => count($charges),
        'chargeMinutes' => self::total($charges, 'minutes'),
        'rangeGained' => (count($charges) > 0) ? self::total($charges, 'rangeGained') : null,
        'energyGained' => $hasEnergy ? round(self::total($charges, 'energy'), 1) : null,
        'cost' => $hasCost ? round(self::total($charges, 'cost'), 2) : null,
      ),
      'levelCurve' => ($end > $start) ? $this->curve($this->levelId(), $start, $end) : array(),
      'capacity' => $this->getCapacity(),
      'price' => $this->getDefaultPrice(),
      'now' => $this->getNow(),
      'notHistorized' => $this->getMissingHistory(),
    );
  }

  /*     * ***************************Tuile******************************* */

  private static $_chargingStateLabels = array(
    'CONNECT_CABLE' => 'Câble débranché',
    'READY_FOR_CHARGING' => 'Prêt à charger',
    'CHARGING' => 'En charge',
    'CONSERVING' => 'Maintien de la charge',
    'DISCHARGING' => 'Décharge',
    'CHARGING_INTERRUPTED' => 'Charge interrompue',
  );

  private static $_climateStateLabels = array(
    'OFF' => 'Arrêtée',
    'COOLING' => 'Refroidissement',
    'HEATING' => 'Chauffage',
    'HEATING_AUXILIARY' => 'Chauffage auxiliaire',
    'VENTILATION' => 'Ventilation',
    'COMPLETED' => 'Terminée',
  );

  // Tuile dédiée. Chaque élément suit la case « Afficher » de sa commande ; l'option d'affichage
  // « widgetTmpl » de l'équipement à 0 rend la tuile standard de Jeedom.
  public function toHtml($_version = 'dashboard') {
    if ($this->getDisplay('widgetTmpl', 1) == 0) {
      return parent::toHtml($_version);
    }
    $replace = $this->preToHtml($_version);
    if (!is_array($replace)) {
      return $replace;
    }
    $version = jeedom::versionAlias($_version);
    if ($version == 'dashboard' && $this->getDisplay('width', 'auto') == 'auto') {
      $replace['#width#'] = '420px';
    }
    // Le contenu de la tuile varie (charge en cours, entretien, données désactivées…) : sa hauteur suit toujours son contenu.
    // Une hauteur enregistrée par le dashboard la couperait dès qu'un bloc s'ajoute (constaté le 2026-10-05).
    $replace['#height#'] = 'auto';
    // Présentation « carte » (option de l'équipement) : une carte de taille fixe, au fond sombre, pensée pour un design.
    // Elle remplace le cadre et le bandeau de titre que le core dessine autour d'une tuile.
    if ($this->getConfiguration('tile_style') == 'card') {
      $replace['#class#'] = trim($replace['#class#'] . ' myskoda-card-host');
      $replace['#eqLogic_class#'] = '';
      // Le fond du cadre est imposé par le thème avec une règle prioritaire sur toute feuille de style du plugin
      // (constaté le 2026-10-09 sur le dashboard) : seul un style posé sur l'élément lui-même l'emporte
      $replace['#style#'] = trim($replace['#style#'] . ';background:transparent !important;border:0 !important;box-shadow:none !important', ';');
      if ($version == 'dashboard') {
        $replace['#width#'] = self::CARD_WIDTH . 'px';
        // La carte porte tout ce que porte la tuile : sa hauteur suit son contenu, à partir de sa hauteur minimale
        $replace['#height#'] = 'auto';
      }
      list($cardClass, $cardStyle) = $this->cardColors();
      $replace['#cmd#'] = str_replace(array('#ms_card#', '#ms_card_class#', '#ms_card_style#', '#uid#'), array($this->cardContent(), $cardClass, $cardStyle, $replace['#uid#']),
        getTemplate('core', $version, 'myskoda_card', __CLASS__));
      return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic')));
    }
    $tags = $this->tileTags();
    $tags['#uid#'] = $replace['#uid#'];
    $replace['#cmd#'] = str_replace(array_keys($tags), array_values($tags), getTemplate('core', $version, 'myskoda', __CLASS__));
    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic')));
  }

  private static function tileRow($_label, $_value) {
    return '<div class="ms-row"><span>' . $_label . '</span><span>' . $_value . '</span></div>';
  }

  // Icône d'état : verte dans l'état normal, orange sinon ; rien si l'état est inconnu
  private static function tileIcon($_state, $_iconOn, $_iconOff, $_titleOn, $_titleOff) {
    if ($_state === null) {
      return '';
    }
    $on = ($_state == 1);
    return '<i class="' . ($on ? $_iconOn . ' ms-ok' : $_iconOff . ' ms-warn') . '" title="' . ($on ? $_titleOn : $_titleOff) . '"></i>';
  }

  private static function tileDate($_date) {
    $time = strtotime((string) $_date);
    if ($time === false) {
      return htmlspecialchars((string) $_date);
    }
    return (date('Y-m-d', $time) == date('Y-m-d')) ? date('H:i', $time) : date('d/m H:i', $time);
  }

  // Profils de charge sur la tuile, en lecture seule : le profil du lieu où le véhicule est garé, puis la liste de tous
  // les profils, repliée. Affichés tant que la commande « Point de recharge actuel » est visible.
  private function tileChargingProfiles() {
    $cmd = $this->getCmd('info', 'charge_profile');
    $profiles = $this->getCache('chargingProfiles', array());
    if (!is_object($cmd) || $cmd->getIsVisible() != 1 || !is_array($profiles) || count($profiles) == 0) {
      return '';
    }
    $describe = function ($_profile) {
      $parts = array();
      if (isset($_profile['target']) && $_profile['target'] !== null) {
        $parts[] = __('limite', __FILE__) . ' ' . (int) $_profile['target'] . ' %';
      }
      if (isset($_profile['min']) && $_profile['min'] !== null) {
        $parts[] = __('minimum', __FILE__) . ' ' . (int) $_profile['min'] . ' %';
      }
      return implode(' · ', $parts);
    };
    $join = function ($_items) {
      $parts = array();
      foreach ($_items as $item) {
        $parts[] = '<span class="ms-nowrap">' . htmlspecialchars((string) $item) . '</span>';
      }
      return implode(', ', $parts);
    };
    $html = '';
    $list = '';
    foreach ($profiles as $profile) {
      $name = htmlspecialchars((string) $profile['name']);
      if (!empty($profile['current'])) {
        $html .= self::tileRow(__('Point actuel', __FILE__), $name);
        if (!empty($profile['next'])) {
          $html .= self::tileRow(__('Prochaine charge', __FILE__), htmlspecialchars((string) $profile['next']));
        }
      }
      $times = (isset($profile['times']) && count($profile['times']) > 0) ? $join($profile['times']) : __('aucune', __FILE__);
      $timers = (isset($profile['timers']) && count($profile['timers']) > 0) ? $join($profile['timers']) : __('aucune', __FILE__);
      $list .= '<div class="ms-profile"><div class="ms-profile-name">' . (!empty($profile['current']) ? '<i class="fas fa-map-marker-alt ms-ok" title="' . __('Le véhicule est garé dans ce lieu', __FILE__) . '"></i> ' : '') . $name . '</div>'
        . '<div>' . $describe($profile) . '</div>'
        . '<div>' . __('Heures de charge préférées', __FILE__) . ' : ' . $times . '</div>'
        . '<div>' . __('Heures de départ', __FILE__) . ' : ' . $timers . '</div></div>';
    }
    // Bloc à part, avec son titre comme « Entretien » : sur une tuile étroite il vient sous « Climatisation », et sans
    // titre ses lignes sembleraient en faire partie
    return '<div class="ms-block ms-profile-zone"><div class="ms-title"><i class="fas fa-calendar-alt"></i>' . __('Points de recharge', __FILE__) . '</div>' . $html
      . '<details class="ms-profiles"><summary title="' . __('Cliquer pour déplier ou replier', __FILE__) . '"><i class="fas fa-chevron-right"></i>' . __('Voir tous les points', __FILE__) . ' (' . count($profiles) . ')</summary>' . $list . '</details></div>';
  }

  // Largeur de la présentation « carte », en pixels : fixe, pour s'aligner avec d'autres cartes sur un design.
  // Sa hauteur part de 500 pixels (feuille de style) et suit son contenu.
  const CARD_WIDTH = 330;
  // Couleur de fond par défaut de la carte, telle que la page de l'équipement la propose
  const CARD_COLOR = '#142c46';

  // Couleur de fond de la carte, choisie sur l'équipement : rend la classe et le style à poser sur la carte.
  // Le fond est un dégradé de la couleur choisie vers une teinte un peu plus claire ; sur un fond clair, les textes
  // et les voiles passent au sombre. Les couleurs sont rendues en triplets « r, g, b » : le gabarit ne doit contenir
  // aucun croisillon. Sans choix, ou avec la couleur par défaut, la carte garde son dégradé bleu d'origine.
  private function cardColors() {
    $color = strtolower(trim((string) $this->getConfiguration('card_color')));
    if (!preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/', $color, $match) || $color == self::CARD_COLOR) {
      return array('', '');
    }
    $rgb = array(hexdec($match[1]), hexdec($match[2]), hexdec($match[3]));
    $lighter = array();
    foreach ($rgb as $channel) {
      $lighter[] = (int) round($channel + (255 - $channel) * 0.14);
    }
    // Luminance perçue : au-dessus du seuil, le fond est clair
    $light = (0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2]) > 150;
    $style = '--msc-a:' . implode(',', $rgb) . ';--msc-b:' . implode(',', $lighter);
    if ($light) {
      // Une carte claire resterait claire quand Jeedom passe à son thème sombre (le soir, si l'option est activée) :
      // elle porte donc aussi la version sombre de sa couleur, même teinte, que le gabarit applique sous ce thème
      $style .= ';--msc-da:' . implode(',', self::shade($rgb, 0.17)) . ';--msc-db:' . implode(',', self::shade($rgb, 0.25));
    }
    return array($light ? 'msc-clair' : '', $style);
  }

  // Même teinte qu'une couleur (r, g, b), à la luminosité demandée (0 à 1) et avec une saturation contenue
  private static function shade($_rgb, $_lightness) {
    list($r, $g, $b) = array($_rgb[0] / 255, $_rgb[1] / 255, $_rgb[2] / 255);
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $delta = $max - $min;
    $hue = 0;
    if ($delta > 0) {
      if ($max == $r) {
        $hue = fmod((($g - $b) / $delta), 6);
      } elseif ($max == $g) {
        $hue = (($b - $r) / $delta) + 2;
      } else {
        $hue = (($r - $g) / $delta) + 4;
      }
      $hue = fmod($hue * 60 + 360, 360);
    }
    $lightness = ($max + $min) / 2;
    $saturation = ($delta == 0) ? 0 : $delta / (1 - abs(2 * $lightness - 1));
    $saturation = min($saturation, 0.45);
    $chroma = (1 - abs(2 * $_lightness - 1)) * $saturation;
    $x = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
    $m = $_lightness - $chroma / 2;
    $sector = (int) floor($hue / 60) % 6;
    $table = array(array($chroma, $x, 0), array($x, $chroma, 0), array(0, $chroma, $x), array(0, $x, $chroma), array($x, 0, $chroma), array($chroma, 0, $x));
    $result = array();
    foreach ($table[$sector] as $channel) {
      $result[] = (int) round(($channel + $m) * 255);
    }
    return $result;
  }

  // Contenu de la présentation « carte ». Comme la tuile, il est entièrement produit ici à chaque régénération du
  // widget : le gabarit n'a aucune valeur à suivre lui-même, il ne fait que brancher les boutons.
  // Les commandes sont retrouvées par leur identifiant logique ; un élément dont la commande est masquée disparaît.
  private function cardContent() {
    $info = array();
    $action = array();
    foreach ($this->getCmd() as $cmd) {
      if ($cmd->getIsVisible() != 1) {
        continue;
      }
      if ($cmd->getType() == 'action') {
        $action[$cmd->getLogicalId()] = $cmd;
        continue;
      }
      $value = $cmd->execCmd();
      if ($value !== '' && $value !== null) {
        $info[$cmd->getLogicalId()] = $value;
      }
    }
    $get = function ($_logicalId) use ($info) {
      return isset($info[$_logicalId]) ? $info[$_logicalId] : null;
    };
    $text = function ($_logicalId) use ($info) {
      return isset($info[$_logicalId]) ? htmlspecialchars((string) $info[$_logicalId]) : null;
    };
    // Bouton rond d'une commande ; sans la commande, un disque inerte qui porte seulement l'icône.
    // Toute commande envoyée à la voiture demande une confirmation (texte dans data-ms-confirm).
    $round = function ($_logicalId, $_icon, $_class, $_title, $_confirm) use ($action) {
      if (!isset($action[$_logicalId])) {
        return '<span class="msc-b msc-inerte"><i class="' . $_icon . '"></i></span>';
      }
      return '<button type="button" class="msc-b ' . $_class . '" title="' . $_title . '" data-ms-cmd="' . $action[$_logicalId]->getId() . '" data-ms-confirm="' . $_confirm . '"><i class="' . $_icon . '"></i></button>';
    };

    // Petit bouton de la commande opposée à celle du grand bouton ; rien si la commande n'existe pas
    $other = function ($_logicalId, $_icon, $_title, $_confirm) use ($action) {
      if (!isset($action[$_logicalId])) {
        return '';
      }
      return '<button type="button" class="msc-b msc-petit" title="' . $_title . '" data-ms-cmd="' . $action[$_logicalId]->getId() . '" data-ms-confirm="' . $_confirm . '"><i class="' . $_icon . '"></i></button>';
    };

    // Pastille d'état
    $pill = array('', '');
    if ($get('unavailable_since') !== null) {
      $pill = array(__('Données indisponibles', __FILE__), 'msc-rouge');
    } elseif ($get('moving') == 1) {
      $pill = array(__('En déplacement', __FILE__), 'msc-bleu');
    } elseif ($get('charging') == 1) {
      $pill = array(__('En charge', __FILE__), 'msc-vert');
    } elseif ($get('plug_connected') == 1) {
      $pill = array(__('Garée · branchée', __FILE__), '');
    } elseif ($get('moving') !== null) {
      $pill = array(__('Garée', __FILE__), '');
    }
    $refresh = isset($action['refresh']) ? '<button type="button" class="msc-b msc-mini" title="' . __('Relire le véhicule (une requête)', __FILE__) . '" data-ms-cmd="' . $action['refresh']->getId() . '"><i class="fas fa-sync"></i></button>' : '';
    $html = '<div class="msc-head"><div class="msc-nom"><i class="fas fa-car"></i>' . htmlspecialchars($this->getName()) . '</div>'
      . (($pill[0] == '') ? '' : '<div class="msc-pill ' . $pill[1] . '">' . $pill[0] . '</div>') . $refresh . '</div>';

    // Identité : photo, modèle, compteur, états
    $image = ($text('image') !== null) ? $text('image') : 'plugins/myskoda/plugin_info/myskoda_icon.png';
    $render = (strpos((string) $get('image'), 'iprenders.blob.core.windows.net') !== false) ? ' msc-rendu' : '';
    $icons = self::tileIcon($get('locked'), 'icon jeedom-lock-ferme', 'icon jeedom-lock-ouvert', __('Verrouillé', __FILE__), __('Déverrouillé', __FILE__))
      . self::tileIcon($get('doors_closed'), 'icon jeedom-porte-ferme', 'icon jeedom-porte-ouverte', __('Portes fermées', __FILE__), __('Porte ouverte', __FILE__))
      . self::tileIcon($get('windows_closed'), 'icon jeedom-fenetre-ferme', 'icon jeedom-fenetre-ouverte', __('Vitres fermées', __FILE__), __('Vitre ouverte', __FILE__))
      . (($get('plug_connected') == 1) ? '<i class="fas fa-plug ms-ok" title="' . __('Câble branché', __FILE__) . '"></i>' : '')
      . (($get('moving') == 1) ? '<i class="fas fa-road ms-info" title="' . __('En déplacement', __FILE__) . '"></i>' : '');
    // Comme sur la tuile : l'heure de l'état du verrouillage et des ouvrants est toujours donnée, et les icônes sont
    // estompées si cet état a plus de 24 heures de retard sur le reste
    $statusTime = strtotime((string) $get('status_timestamp'));
    $dataTime = strtotime((string) $get('data_timestamp'));
    if ($icons != '' && $statusTime !== false) {
      if ($dataTime !== false && $dataTime - $statusTime > 86400) {
        $icons = '<span class="msc-ancien">' . $icons . '</span>';
      }
      $icons .= '<div class="msc-etat-date" title="' . __('Heure du dernier état envoyé par la voiture. Elle ne l\'envoie pas quand elle est verrouillée ou ouverte à l\'arrêt.', __FILE__) . '">'
        . ((date('Y-m-d', $statusTime) == date('Y-m-d')) ? __('état de', __FILE__) . ' ' . date('H:i', $statusTime) : __('état du', __FILE__) . ' ' . date('d/m/Y H:i', $statusTime)) . '</div>';
    }
    $carTypes = array('ELECTRIC' => 'Électrique', 'GASOLINE' => 'Essence', 'DIESEL' => 'Diesel', 'HYBRID' => 'Hybride', 'CNG' => 'Gaz naturel', 'LPG' => 'GPL');
    $carType = isset($carTypes[$get('car_type')]) ? __($carTypes[$get('car_type')], __FILE__) : null;
    $html .= '<div class="msc-photo"><div class="msc-cadre' . $render . '"><img src="' . $image . '" alt=""></div><div class="msc-id">'
      . (($text('name') !== null) ? '<div class="msc-modele">' . $text('name') . '</div>' : '')
      . (($text('license_plate') !== null || $carType !== null) ? '<div>' . implode(' · ', array_filter(array($text('license_plate'), $carType))) . '</div>' : '')
      . (($get('mileage') !== null) ? '<div>' . number_format((float) $get('mileage'), 0, ',', ' ') . ' km</div>' : '')
      . '<div class="msc-icones">' . $icons . '</div></div></div>';

    // Jauges : batterie (avec un repère à la limite de charge), carburant ; les deux pour un hybride rechargeable
    $fuelRange = ($get('fuel_range') !== null) ? $get('fuel_range') : $get('total_range');
    foreach (array(array('fas fa-car-battery', $get('battery_level'), $get('range'), $get('charge_limit')), array('fas fa-gas-pump', $get('fuel_level'), $fuelRange, null)) as $gauge) {
      if ($gauge[1] === null) {
        continue;
      }
      $level = max(0, min(100, (float) $gauge[1]));
      $mark = ($gauge[3] !== null && $gauge[3] > 0 && $gauge[3] < 100) ? '<span style="left:' . (float) $gauge[3] . '%" title="' . __('Limite de charge', __FILE__) . '"></span>' : '';
      $html .= '<div class="msc-jauge"><div class="msc-chiffres"><div class="msc-pct"><i class="' . $gauge[0] . '"></i>' . round($level) . '<small> %</small></div>'
        . '<div class="msc-km">' . (($gauge[2] !== null) ? round((float) $gauge[2]) . ' km' : '') . '</div></div>'
        . '<div class="msc-barre"><div style="width:' . $level . '%"></div>' . $mark . '</div></div>';
    }

    $blocks = '';
    // Charge
    $state = $get('charging_state');
    if ($state !== null || isset($action['charge_start']) || isset($action['charge_stop'])) {
      $charging = ($get('charging') == 1);
      $details = array();
      if ($state !== null && !$charging) {
        $details[] = isset(self::$_chargingStateLabels[$state]) ? __(self::$_chargingStateLabels[$state], __FILE__) : htmlspecialchars($state);
      }
      if ($charging) {
        if ($get('charge_power') !== null) {
          $type = ($get('charge_type') !== null && $get('charge_type') != 'OFF') ? ' ' . $text('charge_type') : '';
          $details[] = str_replace('.', ',', (string) round((float) $get('charge_power'), 1)) . ' kW' . $type;
        }
        if ($get('charge_rate') !== null) {
          $details[] = '+' . round((float) $get('charge_rate')) . ' km/h';
        }
        if ($get('charge_time_left') !== null) {
          $minutes = (int) $get('charge_time_left');
          $details[] = __('reste', __FILE__) . ' ' . intdiv($minutes, 60) . ' h ' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT);
        }
        if ($get('fully_charged_at') !== null) {
          $details[] = __('fin', __FILE__) . ' ' . self::tileDate($get('fully_charged_at'));
        }
      }
      $button = $charging
        ? $round('charge_stop', 'fas fa-stop', 'msc-grand msc-stop', __('Arrêter la charge', __FILE__), __('Arrêter la charge du véhicule ?', __FILE__))
        : $round('charge_start', 'fas fa-bolt', 'msc-grand', __('Démarrer la charge', __FILE__), __('Démarrer la charge du véhicule ?', __FILE__));
      // L'autre commande reste à portée, en petit : l'état affiché peut avoir du retard sur la voiture
      $button .= $charging
        ? $other('charge_start', 'fas fa-play', __('Démarrer la charge', __FILE__), __('Démarrer la charge du véhicule ?', __FILE__))
        : $other('charge_stop', 'fas fa-stop', __('Arrêter la charge', __FILE__), __('Arrêter la charge du véhicule ?', __FILE__));
      $list = '';
      if (isset($action['charge_limit_set'])) {
        $cmd = $action['charge_limit_set'];
        $parameters = $cmd->getDisplay('parameters');
        $step = (is_array($parameters) && isset($parameters['step'])) ? max(1, (int) $parameters['step']) : 10;
        $options = '';
        for ($limit = (int) $cmd->getConfiguration('minValue', 50); $limit <= (int) $cmd->getConfiguration('maxValue', 100); $limit += $step) {
          $options .= '<option value="' . $limit . '"' . (($get('charge_limit') == $limit) ? ' selected' : '') . '>' . $limit . ' %</option>';
        }
        $list = '<select class="msc-liste" title="' . __('Limite de charge', __FILE__) . '" data-ms-cmd="' . $cmd->getId() . '" data-ms-confirm="' . __('Régler la limite de charge du véhicule à %s ?', __FILE__) . '">' . $options . '</select>';
      } elseif ($get('charge_limit') !== null) {
        $list = '<span class="msc-valeur">' . round((float) $get('charge_limit')) . ' %</span>';
      }
      $blocks .= '<div class="msc-bloc">' . $button . '<div class="msc-txt"><div class="msc-titre">' . ($charging ? __('Charge en cours', __FILE__) : __('Charge', __FILE__)) . '</div>'
        . '<div class="msc-sous">' . implode(' · ', $details) . '</div></div>' . $list . '</div>';
    }
    // Climatisation
    $state = $get('climate_state');
    if ($state !== null || isset($action['climate_start']) || isset($action['climate_stop'])) {
      $running = ($get('climate_on') == 1);
      $details = array();
      if ($state !== null) {
        $details[] = isset(self::$_climateStateLabels[$state]) ? __(self::$_climateStateLabels[$state], __FILE__) : htmlspecialchars($state);
      }
      if ($running && $get('climate_ready_at') !== null) {
        $details[] = __('prête à', __FILE__) . ' ' . self::tileDate($get('climate_ready_at'));
      }
      $button = $running
        ? $round('climate_stop', 'fas fa-stop', 'msc-grand msc-stop', __('Arrêter la climatisation', __FILE__), __('Arrêter la climatisation du véhicule ?', __FILE__))
        : $round('climate_start', 'fas fa-snowflake', 'msc-grand', __('Démarrer la climatisation', __FILE__), __('Démarrer la climatisation du véhicule ?', __FILE__));
      $button .= $running
        ? $other('climate_start', 'fas fa-play', __('Démarrer la climatisation', __FILE__), __('Démarrer la climatisation du véhicule ?', __FILE__))
        : $other('climate_stop', 'fas fa-stop', __('Arrêter la climatisation', __FILE__), __('Arrêter la climatisation du véhicule ?', __FILE__));
      $list = '';
      if (isset($action['climate_temp_set'])) {
        // La consigne est seulement mémorisée par le plugin et envoyée au prochain démarrage : pas de confirmation
        $setpoint = is_numeric($this->getConfiguration('climate_setpoint')) ? (float) $this->getConfiguration('climate_setpoint') : (($get('target_temperature') !== null) ? (float) $get('target_temperature') : null);
        $options = ($setpoint === null) ? '<option value="" selected>—</option>' : '';
        for ($degree = 16.0; $degree <= 30.0; $degree += 0.5) {
          $options .= '<option value="' . $degree . '"' . (($setpoint !== null && abs($setpoint - $degree) < 0.01) ? ' selected' : '') . '>' . str_replace('.', ',', (string) $degree) . ' °C</option>';
        }
        $list = '<select class="msc-liste" title="' . __('Consigne de climatisation', __FILE__) . '" data-ms-cmd="' . $action['climate_temp_set']->getId() . '">' . $options . '</select>';
      } elseif ($get('target_temperature') !== null) {
        $list = '<span class="msc-valeur">' . str_replace('.', ',', (string) round((float) $get('target_temperature'), 1)) . ' °C</span>';
      }
      $blocks .= '<div class="msc-bloc">' . $button . '<div class="msc-txt"><div class="msc-titre">' . __('Climatisation', __FILE__) . '</div>'
        . '<div class="msc-sous">' . implode(' · ', $details) . '</div></div>' . $list . '</div>';
    }

    // Chauffage auxiliaire : seulement pour les véhicules qui en annoncent la commande
    if (isset($action['aux_heating_start']) || isset($action['aux_heating_stop'])) {
      $running = ($get('aux_heating_on') == 1);
      $button = $running
        ? $round('aux_heating_stop', 'fas fa-stop', 'msc-grand msc-stop', __('Arrêter le chauffage auxiliaire', __FILE__), __('Arrêter le chauffage auxiliaire du véhicule ?', __FILE__))
        : $round('aux_heating_start', 'fas fa-fire', 'msc-grand', __('Démarrer le chauffage auxiliaire', __FILE__), __('Démarrer le chauffage auxiliaire du véhicule ?', __FILE__));
      $button .= $running
        ? $other('aux_heating_start', 'fas fa-play', __('Démarrer le chauffage auxiliaire', __FILE__), __('Démarrer le chauffage auxiliaire du véhicule ?', __FILE__))
        : $other('aux_heating_stop', 'fas fa-stop', __('Arrêter le chauffage auxiliaire', __FILE__), __('Arrêter le chauffage auxiliaire du véhicule ?', __FILE__));
      $blocks .= '<div class="msc-bloc">' . $button . '<div class="msc-txt"><div class="msc-titre">' . __('Chauffage auxiliaire', __FILE__) . '</div>'
        . '<div class="msc-sous">' . (($get('aux_heating_on') === null) ? '' : ($running ? __('En marche', __FILE__) : __('Arrêté', __FILE__))) . '</div></div></div>';
    }
    // Profils de charge : le même bloc que sur la tuile (profil du lieu, prochaine charge, liste à déplier)
    $blocks .= $this->tileChargingProfiles();

    // Entretien : toutes les échéances saisies
    $services = array();
    foreach (self::$_services as $key => $label) {
      $parts = array();
      if ($get('service_' . $key . '_km') !== null) {
        $parts[] = self::serviceText((float) $get('service_' . $key . '_km'), 'km', __('en retard de %s km', __FILE__));
      }
      if ($get('service_' . $key . '_days') !== null) {
        $parts[] = self::serviceText((float) $get('service_' . $key . '_days'), __('jours', __FILE__), __('en retard de %s jours', __FILE__));
      }
      if (count($parts) > 0) {
        $services[__($label, __FILE__)] = implode(' · ', $parts);
      }
    }
    if (count($services) > 0) {
      $blocks .= '<div class="ms-block"><div class="ms-title"><i class="fas fa-wrench"></i>' . __('Entretien', __FILE__) . '</div>';
      foreach ($services as $label => $value) {
        $blocks .= '<div class="msc-ligne"><span>' . $label . '</span><b>' . $value . '</b></div>';
      }
      $blocks .= '</div>';
    }
    if ($blocks != '') {
      $html .= '<div class="msc-sep"></div>' . $blocks . '<div class="msc-sep"></div>';
    }

    // Pied : alertes, lieu, heure du relevé, quota
    $foot = '';
    if ($get('unavailable_since') !== null) {
      $foot .= '<div class="msc-alerte"><i class="fas fa-exclamation-triangle"></i>' . __('Škoda ne fournit plus les données du véhicule depuis', __FILE__) . ' ' . self::tileDate($get('unavailable_since')) . '. '
        . __('Les valeurs affichées sont les dernières reçues.', __FILE__) . '</div>';
    }
    if ($text('services_disabled') !== null) {
      $foot .= '<div class="msc-alerte"><i class="fas fa-exclamation-triangle"></i>' . __('Données désactivées', __FILE__) . ' : ' . $text('services_disabled') . '</div>';
    }
    if ($text('service_alert') !== null) {
      $foot .= '<div class="msc-alerte"><i class="fas fa-exclamation-triangle"></i>' . __('À prévoir', __FILE__) . ' — ' . $text('service_alert') . '</div>';
    }
    $place = ($text('place') !== null) ? $text('place') : $text('address');
    if ($place !== null) {
      $foot .= '<div><i class="fas fa-map-marker-alt"></i>' . $place . '</div>';
    }
    if ($get('data_timestamp') !== null) {
      $foot .= '<div><i class="fas fa-clock"></i>' . __('Relevé de la voiture', __FILE__) . ' : ' . self::tileDate($get('data_timestamp')) . '</div>';
    }
    if ($get('api_quota_remaining') !== null) {
      $foot .= '<div><i class="fas fa-exchange-alt"></i>' . __('Requêtes restantes cette heure', __FILE__) . ' : ' . (int) $get('api_quota_remaining') . '</div>';
    }
    return $html . '<div class="msc-pied">' . $foot . '</div>';
  }

  private function tileTags() {
    // Valeurs des infos affichées et commandes action affichées, par identifiant logique
    $info = array();
    $action = array();
    foreach ($this->getCmd() as $cmd) {
      if ($cmd->getIsVisible() != 1) {
        continue;
      }
      if ($cmd->getType() == 'action') {
        $action[$cmd->getLogicalId()] = $cmd;
        continue;
      }
      $value = $cmd->execCmd();
      if ($value !== '' && $value !== null) {
        $info[$cmd->getLogicalId()] = $value;
      }
    }
    $get = function ($_logicalId) use ($info) {
      return isset($info[$_logicalId]) ? $info[$_logicalId] : null;
    };
    $text = function ($_logicalId) use ($info) {
      return isset($info[$_logicalId]) ? htmlspecialchars((string) $info[$_logicalId]) : null;
    };
    $button = function ($_logicalId, $_icon, $_label) use ($action) {
      if (!isset($action[$_logicalId])) {
        return '';
      }
      return '<a class="btn btn-xs btn-default" data-ms-cmd="' . $action[$_logicalId]->getId() . '"><i class="' . $_icon . '"></i> ' . $_label . '</a>';
    };

    $tags = array();
    $tags['#ms_image#'] = ($text('image') !== null) ? $text('image') : 'plugins/myskoda/plugin_info/myskoda_icon.png';
    // Les images de Škoda (1920 x 1080) montrent la voiture petite au milieu d'un grand vide : elles sont recadrées
    $tags['#ms_image_class#'] = (strpos((string) $get('image'), 'iprenders.blob.core.windows.net') !== false) ? 'ms-render' : '';
    $tags['#ms_alert#'] = ($text('services_disabled') === null) ? '' : '<div class="ms-alert"><i class="fas fa-exclamation-triangle"></i> '
      . __('Données désactivées sur le véhicule', __FILE__) . ' : ' . $text('services_disabled') . '. ' . __('À vérifier dans l\'application MyŠkoda.', __FILE__) . '</div>';
    if ($text('unavailable_since') !== null) {
      $tags['#ms_alert#'] .= '<div class="ms-alert"><i class="fas fa-exclamation-triangle"></i> '
        . __('Škoda ne fournit plus les données du véhicule depuis', __FILE__) . ' ' . self::tileDate($get('unavailable_since')) . '. '
        . __('Les valeurs affichées sont les dernières reçues.', __FILE__) . '</div>';
    }

    $tags['#ms_icons#'] = self::tileIcon($get('locked'), 'icon jeedom-lock-ferme', 'icon jeedom-lock-ouvert', __('Verrouillé', __FILE__), __('Déverrouillé', __FILE__))
      . self::tileIcon($get('doors_closed'), 'icon jeedom-porte-ferme', 'icon jeedom-porte-ouverte', __('Portes fermées', __FILE__), __('Porte ouverte', __FILE__))
      . self::tileIcon($get('windows_closed'), 'icon jeedom-fenetre-ferme', 'icon jeedom-fenetre-ouverte', __('Vitres fermées', __FILE__), __('Vitre ouverte', __FILE__))
      . (($get('plug_connected') == 1) ? '<i class="fas fa-plug ms-ok" title="' . __('Câble branché', __FILE__) . '"></i>' : '')
      . (($get('moving') == 1) ? '<i class="fas fa-road ms-info" title="' . __('En déplacement', __FILE__) . '"></i>' : '');

    $statusTime = strtotime((string) $get('status_timestamp'));
    $dataTime = strtotime((string) $get('data_timestamp'));
    // L'état du verrouillage et des ouvrants est celui du dernier envoi de la voiture (départ, arrivée, ou réveil par
    // l'application MyŠkoda) : son heure est toujours affichée. Nettement plus ancien que le reste, il est estompé.
    if ($tags['#ms_icons#'] != '' && $statusTime !== false) {
      if ($dataTime !== false && $dataTime - $statusTime > 86400) {
        $tags['#ms_icons#'] = '<span class="ms-stale">' . $tags['#ms_icons#'] . '</span>';
      }
      $tags['#ms_icons#'] .= '<div class="ms-stale-date" title="' . __('Heure du dernier état envoyé par la voiture. Elle ne l\'envoie pas quand elle est verrouillée ou ouverte à l\'arrêt.', __FILE__) . '">'
        . ((date('Y-m-d', $statusTime) == date('Y-m-d')) ? __('état de', __FILE__) . ' ' . date('H:i', $statusTime) : __('état du', __FILE__) . ' ' . date('d/m/Y H:i', $statusTime)) . '</div>';
    }

    $identity = '';
    if ($text('name') !== null) {
      $identity .= '<div class="ms-name">' . $text('name') . '</div>';
    }
    $carTypes = array('ELECTRIC' => 'Électrique', 'GASOLINE' => 'Essence', 'DIESEL' => 'Diesel', 'HYBRID' => 'Hybride', 'CNG' => 'Gaz naturel', 'LPG' => 'GPL');
    $carType = isset($carTypes[$get('car_type')]) ? __($carTypes[$get('car_type')], __FILE__) : null;
    if ($text('license_plate') !== null || $carType !== null) {
      $identity .= '<div>' . implode(' · ', array_filter(array($text('license_plate'), $carType))) . '</div>';
    }
    if ($get('mileage') !== null) {
      $identity .= '<div>' . __('Compteur', __FILE__) . ' : ' . number_format((float) $get('mileage'), 0, ',', ' ') . ' km</div>';
    }
    if ($get('data_timestamp') !== null) {
      $identity .= '<div>' . __('Remontée', __FILE__) . ' : ' . self::tileDate($get('data_timestamp')) . '</div>';
    }
    $tags['#ms_identity#'] = $identity;

    // Jauges : batterie, et carburant pour les thermiques et les hybrides
    $battery = '';
    $fuelRange = ($get('fuel_range') !== null) ? $get('fuel_range') : $get('total_range');
    foreach (array(array('fas fa-car-battery', $get('battery_level'), $get('range')), array('fas fa-gas-pump', $get('fuel_level'), $fuelRange)) as $gauge) {
      if ($gauge[1] === null) {
        continue;
      }
      $level = max(0, min(100, (float) $gauge[1]));
      $battery .= '<div class="ms-battery"><div class="ms-battery-text"><span><i class="' . $gauge[0] . '"></i> ' . round($level) . ' %</span>'
        . '<span>' . (($gauge[2] !== null) ? round((float) $gauge[2]) . ' km' : '') . '</span></div>'
        . '<div class="ms-bar"><div style="width:' . $level . '%"></div></div></div>';
    }
    if ($battery == '' && $fuelRange !== null) {
      $battery = '<div class="ms-battery"><div class="ms-battery-text"><span><i class="fas fa-gas-pump"></i> ' . __('Autonomie', __FILE__) . '</span><span>' . round((float) $fuelRange) . ' km</span></div></div>';
    }
    $tags['#ms_battery#'] = $battery;

    $charge = '';
    $state = $get('charging_state');
    if ($state !== null) {
      $charge .= self::tileRow(__('État', __FILE__), isset(self::$_chargingStateLabels[$state]) ? __(self::$_chargingStateLabels[$state], __FILE__) : htmlspecialchars($state));
    }
    if ($get('charging') == 1) {
      if ($get('charge_power') !== null) {
        $type = ($get('charge_type') !== null && $get('charge_type') != 'OFF') ? ' ' . $text('charge_type') : '';
        $charge .= self::tileRow(__('Puissance', __FILE__), str_replace('.', ',', (string) round((float) $get('charge_power'), 1)) . ' kW' . $type);
      }
      if ($get('charge_rate') !== null) {
        $charge .= self::tileRow(__('Gain', __FILE__), round((float) $get('charge_rate')) . ' km/h');
      }
      if ($get('charge_time_left') !== null) {
        $minutes = (int) $get('charge_time_left');
        $charge .= self::tileRow(__('Temps restant', __FILE__), intdiv($minutes, 60) . ' h ' . str_pad($minutes % 60, 2, '0', STR_PAD_LEFT));
      }
      if ($get('fully_charged_at') !== null) {
        $charge .= self::tileRow(__('Fin prévue', __FILE__), self::tileDate($get('fully_charged_at')));
      }
    }
    if (isset($action['charge_limit_set'])) {
      $cmd = $action['charge_limit_set'];
      $parameters = $cmd->getDisplay('parameters');
      $step = (is_array($parameters) && isset($parameters['step'])) ? max(1, (int) $parameters['step']) : 10;
      $options = '';
      for ($limit = (int) $cmd->getConfiguration('minValue', 50); $limit <= (int) $cmd->getConfiguration('maxValue', 100); $limit += $step) {
        $options .= '<option value="' . $limit . '"' . (($get('charge_limit') == $limit) ? ' selected' : '') . '>' . $limit . ' %</option>';
      }
      $charge .= self::tileRow(__('Limite', __FILE__), '<select class="form-control ms-select" data-ms-cmd="' . $cmd->getId() . '">' . $options . '</select>');
    } elseif ($get('charge_limit') !== null) {
      $charge .= self::tileRow(__('Limite', __FILE__), round((float) $get('charge_limit')) . ' %');
    }
    $buttons = $button('charge_start', 'fas fa-play', __('Charger', __FILE__)) . $button('charge_stop', 'fas fa-stop', __('Arrêter', __FILE__));
    if ($buttons != '') {
      $charge .= '<div class="ms-actions">' . $buttons . '</div>';
    }
    // Profils de charge : sous les colonnes « Charge » et « Climatisation », sur toute la largeur. Dans la colonne
    // « Charge », deux fois plus étroite sur une tuile large, le bandeau et les plages horaires passaient à la ligne.
    $tags['#ms_profiles#'] = $this->tileChargingProfiles();
    $tags['#ms_charge#'] = ($charge == '') ? '' : '<div class="ms-block"><div class="ms-title"><i class="fas fa-charging-station"></i>' . __('Charge', __FILE__) . '</div>' . $charge . '</div>';

    $climate = '';
    $state = $get('climate_state');
    if ($state !== null) {
      $climate .= self::tileRow(__('État', __FILE__), isset(self::$_climateStateLabels[$state]) ? __(self::$_climateStateLabels[$state], __FILE__) : htmlspecialchars($state));
    }
    if (isset($action['climate_temp_set'])) {
      // Liste de la consigne : la valeur choisie dans Jeedom, à défaut celle de la voiture ; elle sert au prochain démarrage
      $setpoint = is_numeric($this->getConfiguration('climate_setpoint')) ? (float) $this->getConfiguration('climate_setpoint') : (($get('target_temperature') !== null) ? (float) $get('target_temperature') : null);
      $options = ($setpoint === null) ? '<option value="" selected>—</option>' : '';
      for ($degree = 16.0; $degree <= 30.0; $degree += 0.5) {
        $options .= '<option value="' . $degree . '"' . (($setpoint !== null && abs($setpoint - $degree) < 0.01) ? ' selected' : '') . '>' . str_replace('.', ',', (string) $degree) . ' °C</option>';
      }
      $climate .= self::tileRow(__('Consigne', __FILE__), '<select class="form-control ms-select" data-ms-cmd="' . $action['climate_temp_set']->getId() . '">' . $options . '</select>');
    } elseif ($get('target_temperature') !== null) {
      $climate .= self::tileRow(__('Consigne', __FILE__), str_replace('.', ',', (string) round((float) $get('target_temperature'), 1)) . ' °C');
    }
    if ($get('climate_on') == 1 && $get('climate_ready_at') !== null) {
      $climate .= self::tileRow(__('Prête à', __FILE__), self::tileDate($get('climate_ready_at')));
    }
    $buttons = $button('climate_start', 'fas fa-play', __('Démarrer', __FILE__)) . $button('climate_stop', 'fas fa-stop', __('Arrêter', __FILE__));
    if ($buttons != '') {
      $climate .= '<div class="ms-actions">' . $buttons . '</div>';
    }
    $tags['#ms_climate#'] = ($climate == '') ? '' : '<div class="ms-block"><div class="ms-title"><i class="fas fa-snowflake"></i>' . __('Climatisation', __FILE__) . '</div>' . $climate . '</div>';

    // Chauffage auxiliaire : seulement pour les véhicules qui en annoncent la commande
    $buttons = $button('aux_heating_start', 'fas fa-play', __('Démarrer', __FILE__)) . $button('aux_heating_stop', 'fas fa-stop', __('Arrêter', __FILE__));
    if ($buttons != '') {
      $auxState = ($get('aux_heating_on') === null) ? '' : self::tileRow(__('État', __FILE__), ($get('aux_heating_on') == 1) ? __('En marche', __FILE__) : __('Arrêté', __FILE__));
      $tags['#ms_climate#'] .= '<div class="ms-block"><div class="ms-title"><i class="fas fa-fire"></i>' . __('Chauffage auxiliaire', __FILE__) . '</div>' . $auxState . '<div class="ms-actions">' . $buttons . '</div></div>';
    }

    // Entretien : une ligne par échéance saisie, en avertissement si elle est proche ou dépassée
    $service = '';
    foreach (self::$_services as $key => $label) {
      $parts = array();
      if ($get('service_' . $key . '_km') !== null) {
        $parts[] = self::serviceText((float) $get('service_' . $key . '_km'), 'km', __('en retard de %s km', __FILE__));
      }
      if ($get('service_' . $key . '_days') !== null) {
        $parts[] = self::serviceText((float) $get('service_' . $key . '_days'), __('jours', __FILE__), __('en retard de %s jours', __FILE__));
      }
      if (count($parts) > 0) {
        $service .= self::tileRow(__($label, __FILE__), implode(' · ', $parts));
      }
    }
    if ($service != '') {
      $alert = ($text('service_alert') === null) ? '' : '<div class="ms-alert"><i class="fas fa-exclamation-triangle"></i> ' . __('À prévoir', __FILE__) . ' — ' . $text('service_alert') . '</div>';
      $service = '<div class="ms-block ms-service"><div class="ms-title"><i class="fas fa-wrench"></i>' . __('Entretien', __FILE__) . '</div>' . $service . $alert . '</div>';
    }
    $tags['#ms_service#'] = $service;

    $foot = array();
    $place = ($text('place') !== null) ? $text('place') : $text('address');
    if ($place !== null) {
      $foot[] = '<i class="fas fa-map-marker-alt"></i> ' . $place;
    }
    if ($get('api_quota_remaining') !== null) {
      $foot[] = __('Requêtes restantes cette heure', __FILE__) . ' : ' . (int) $get('api_quota_remaining');
    }
    $tags['#ms_foot#'] = implode('<br>', $foot);
    return $tags;
  }
}

class myskodaCmd extends cmd {
  /*     * *********************Methode d'instance************************* */

  // Les commandes sont créées par le plugin d'après le véhicule : la page d'équipement ne doit pas les supprimer
  public function dontRemoveCmd() {
    return true;
  }

  // Exécution d'une commande
  public function execute($_options = array()) {
    $eqLogic = $this->getEqLogic();
    switch ($this->getLogicalId()) {
      case 'refresh':
        $eqLogic->refreshFromApi();
        break;
      case 'charge_start':
        $eqLogic->sendCommand('POST', 'charging/start');
        break;
      case 'charge_stop':
        $eqLogic->sendCommand('POST', 'charging/stop');
        break;
      case 'charge_limit_set':
        $eqLogic->sendCommand('PUT', 'charging/limit', array('targetStateOfChargeInPercent' => (int) $_options['slider']));
        break;
      case 'charge_mode_set':
        $eqLogic->sendCommand('PUT', 'charging/mode', array('chargeMode' => $_options['select']));
        break;
      case 'climate_start':
        $eqLogic->sendCommand('POST', 'air-conditioning/start', $eqLogic->climateStartBody());
        break;
      case 'climate_start_temp':
        $eqLogic->sendCommand('POST', 'air-conditioning/start', $eqLogic->climateStartBody((float) $_options['slider']));
        break;
      case 'climate_temp_set':
        $eqLogic->setClimateSetpoint($_options['slider']);
        break;
      case 'climate_stop':
        $eqLogic->sendCommand('POST', 'air-conditioning/stop');
        break;
      case 'aux_heating_start':
        $eqLogic->startAuxiliaryHeating();
        break;
      case 'aux_heating_stop':
        $eqLogic->sendCommand('POST', 'auxiliary-heating/stop');
        break;
      case 'ventilation_start':
        $eqLogic->sendCommand('POST', 'active-ventilation/start');
        break;
      case 'ventilation_stop':
        $eqLogic->sendCommand('POST', 'active-ventilation/stop');
        break;
    }
  }
}
