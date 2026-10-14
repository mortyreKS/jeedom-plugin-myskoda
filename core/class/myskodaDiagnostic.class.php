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

/*
* Rapport de diagnostic (page de configuration du plugin, « Diagnostic et support ») : l'état du plugin et de chaque
* véhicule, en texte, à joindre à une demande d'aide.
* Le rapport doit pouvoir être transmis tel quel. Il ne contient donc rien qui identifie ou localise le véhicule ou son
* propriétaire : ni clé, ni code S-PIN, ni numéro de série, ni plaque, ni nom d'équipement ou d'objet, ni lieu, ni
* adresse, ni position. Les véhicules y sont numérotés. Une dernière passe retire ces valeurs de tout le texte, au cas
* où l'une d'elles figurerait dans un message d'erreur.
* Les intitulés des lignes ne sont pas traduits : ils sont lus par la personne qui aide, pas par l'utilisateur.
*/
class myskodaDiagnostic {
  // Lignes du journal du plugin reprises dans le rapport, et longueur gardée de chacune
  const LOG_LINES = 30;
  const LOG_LINE_LENGTH = 300;
  // Période sur laquelle le rapport compte les trajets et les recharges du journal de bord
  const JOURNAL_DAYS = 7;

  private static function yesNo($_value) {
    return $_value ? 'oui' : 'non';
  }

  private static function line($_key, $_value) {
    return '- ' . $_key . ': ' . (($_value === '' || $_value === null) ? '(vide)' : $_value);
  }

  // Valeur d'une commande d'information, suivie de l'heure à laquelle elle a été reçue
  private static function info($_eqLogic, $_logicalId, $_unit = '') {
    $cmd = $_eqLogic->getCmd('info', $_logicalId);
    if (!is_object($cmd)) {
      return 'absente';
    }
    $value = $cmd->execCmd();
    if ($value === '' || $value === null) {
      return '(vide)';
    }
    return $value . $_unit . ' (' . $cmd->getValueDate() . ')';
  }

  private static function environment() {
    $lines = array('', '## Environnement');
    $lines[] = self::line('plugin.version', myskoda::getVersionLabel());
    $update = update::byTypeAndLogicalId('plugin', 'myskoda');
    $lines[] = self::line('plugin.source', is_object($update) ? $update->getSource() : 'inconnue');
    $lines[] = self::line('plugin.installation', is_object($update) ? $update->getLocalVersion() : 'inconnue');
    $lines[] = self::line('jeedom.version', jeedom::version());
    $lines[] = self::line('jeedom.materiel', jeedom::getHardwareName());
    $lines[] = self::line('php.version', phpversion());
    $lines[] = self::line('os.version', system::getDistrib() . ' ' . trim((string) @file_get_contents('/etc/debian_version')));
    $lines[] = self::line('log.niveau', log::convertLogLevel(log::getLogLevel('myskoda')));
    $lines[] = self::line('tache.active', self::yesNo(config::byKey('functionality::cron::enable', 'myskoda', 1) == 1));
    $cron = cron::byClassAndFunction('plugin', 'cron');
    $lines[] = self::line('tache.derniereExecution', is_object($cron) ? $cron->getLastRun() : 'inconnue');
    return $lines;
  }

  private static function settings() {
    $lines = array('', '## Réglages du plugin');
    $lines[] = self::line('cle.renseignee', self::yesNo(config::byKey('apikey', 'myskoda') != ''));
    $lines[] = self::line('lecture.arret.minutes', config::byKey('interval', 'myskoda'));
    $lines[] = self::line('lecture.deplacement.minutes', config::byKey('interval_active', 'myskoda'));
    $lines[] = self::line('relecture.apresCommande.secondes', config::byKey('refresh_after_command', 'myskoda'));
    $lines[] = self::line('favoris.rayon.metres', myskoda::favoriteRadius());
    $lines[] = self::line('unites', myskoda::isImperial() ? 'imperial' : 'metric');
    $lines[] = self::line('monnaie', myskoda::currency());
    $lines[] = self::line('prix.kwh.renseigne', self::yesNo(config::byKey('kwh_price', 'myskoda') != ''));
    $lines[] = self::line('prix.carburant.renseigne', self::yesNo(config::byKey('fuel_price', 'myskoda') != ''));
    $lines[] = self::line('fonctions.experimentales', self::yesNo(myskoda::experimentalEnabled()));
    return $lines;
  }

  private static function vehicle($_eqLogic, $_rank) {
    $lines = array('', '## Véhicule ' . $_rank);
    $lines[] = self::line('actif', self::yesNo($_eqLogic->getIsEnable() == 1));
    $lines[] = self::line('visible', self::yesNo($_eqLogic->getIsVisible() == 1));
    $lines[] = self::line('numeroDeSerie.renseigne', self::yesNo($_eqLogic->getConfiguration('vin') != ''));
    // Škoda ne donne pas le modèle à part : seulement le nom du véhicule, qui est le modèle tant que l'utilisateur ne
    // l'a pas changé. Seul le modèle reconnu dans ce nom est écrit.
    $name = $_eqLogic->getCmd('info', 'name');
    $model = is_object($name) ? self::modelOf($name->execCmd()) : null;
    $lines[] = self::line('modele', ($model === null) ? 'non reconnu' : $model);
    $lines[] = self::line('energies', implode(', ', $_eqLogic->getEnergies()));
    $lines[] = self::line('option.rechargePilotee', self::yesNo($_eqLogic->isManagedCharging()));
    $lines[] = self::line('option.prechauffageSansBranchement', self::yesNo($_eqLogic->getConfiguration('climate_without_power', 0) == 1));
    $lines[] = self::line('spin.renseigne', self::yesNo($_eqLogic->getConfiguration('spin') != ''));
    $lines[] = self::line('capacite.renseignee', self::yesNo($_eqLogic->getCapacity() > 0));
    // Adresse d'un faux serveur Škoda, réservée aux essais du développeur : ne doit jamais rester renseignée
    if ($_eqLogic->getConfiguration('api_url_test') != '') {
      $lines[] = self::line('api.adresseEssai', 'renseignée (à retirer)');
    }

    $actions = array();
    foreach ($_eqLogic->getCmd('action') as $cmd) {
      $actions[] = $cmd->getLogicalId();
    }
    sort($actions);
    $lines[] = self::line('commandes.info', count($_eqLogic->getCmd('info')));
    $lines[] = self::line('commandes.action', count($actions) . ' (' . implode(', ', $actions) . ')');
    $missing = $_eqLogic->getMissingHistory();
    $lines[] = self::line('historisation.manquante', (count($missing) > 0) ? implode(', ', $missing) : 'aucune');

    $quota = $_eqLogic->getCmd('info', 'api_quota_remaining');
    $lines[] = self::line('lecture.derniere', is_object($quota) ? $quota->getCollectDate() : 'jamais');
    $next = $_eqLogic->getNextRefresh() - time();
    if ($_eqLogic->getConfiguration('vin') == '' || $_eqLogic->getIsEnable() != 1) {
      $lines[] = self::line('lecture.prochaine', 'aucune (véhicule inactif ou sans numéro de série)');
    } else {
      $lines[] = self::line('lecture.prochaine', ($next > 0) ? 'dans ' . $next . ' s' : 'à la prochaine minute');
    }
    $lines[] = self::line('quota.restant', self::info($_eqLogic, 'api_quota_remaining'));
    $lines[] = self::line('cle.expiration', self::info($_eqLogic, 'api_key_expires'));
    $lines[] = self::line('releve.voiture', self::info($_eqLogic, 'data_timestamp'));
    $lines[] = self::line('donnees.indisponiblesDepuis', self::info($_eqLogic, 'unavailable_since'));
    $lines[] = self::line('donnees.desactivees', self::info($_eqLogic, 'services_disabled'));

    foreach (array('moving' => '', 'mileage' => ' km', 'battery_level' => ' %', 'range' => ' km', 'charging' => '', 'charging_state' => '', 'plug_connected' => '',
      'charge_limit' => ' %', 'charge_mode' => '', 'climate_state' => '', 'target_temperature' => ' °C', 'fuel_level' => ' %', 'fuel_range' => ' km', 'locked' => '') as $logicalId => $unit) {
      if (is_object($_eqLogic->getCmd('info', $logicalId))) {
        $lines[] = self::line('etat.' . $logicalId, self::info($_eqLogic, $logicalId, $unit));
      }
    }

    $profiles = $_eqLogic->getCache('chargingProfiles', array());
    if (is_array($profiles) && count($profiles) > 0) {
      $here = 0;
      foreach ($profiles as $profile) {
        $here += empty($profile['current']) ? 0 : 1;
      }
      $lines[] = self::line('pointsDeRecharge', count($profiles) . ', véhicule dans un lieu enregistré : ' . self::yesNo($here > 0));
    }

    $places = $_eqLogic->getPlaces();
    $favorites = 0;
    foreach ($places as $place) {
      if (!empty($place['favorite'])) {
        $favorites++;
      }
    }
    $lines[] = self::line('lieux.memorises', count($places) . ' dont ' . $favorites . ' favori(s)');

    // Le journal de bord est recalculé pour chaque énergie du véhicule (les deux pour un hybride rechargeable)
    $start = date('Y-m-d 00:00:00', strtotime('-' . (self::JOURNAL_DAYS - 1) . ' days'));
    $end = date('Y-m-d 23:59:59');
    foreach ($_eqLogic->getEnergies() as $energy) {
      try {
        $_eqLogic->setEnergy($energy);
        $trips = $_eqLogic->getTrips($start, $end);
        $charges = $_eqLogic->getCharges($start, $end);
        $distance = 0;
        foreach ($trips as $trip) {
          $distance += isset($trip['distance']) ? (float) $trip['distance'] : 0;
        }
        $lines[] = self::line('journal.' . $energy . '.' . self::JOURNAL_DAYS . 'jours', count($trips) . ' trajet(s), ' . round($distance) . ' km, '
          . count($charges) . (($energy == 'fuel') ? ' plein(s)' : ' recharge(s)'));
      } catch (\Throwable $e) {
        $lines[] = self::line('journal.' . $energy . '.erreur', $e->getMessage());
      }
    }
    $_eqLogic->setEnergy(null);
    return $lines;
  }

  // Messages laissés par le plugin dans le centre de messages de Jeedom
  private static function messages() {
    $lines = array('', '## Messages du plugin');
    $messages = message::byPlugin('myskoda');
    if (!is_array($messages) || count($messages) == 0) {
      $lines[] = 'Aucun message.';
      return $lines;
    }
    foreach ($messages as $message) {
      $lines[] = '- ' . $message->getDate() . ' : ' . mb_substr(strip_tags((string) $message->getMessage()), 0, self::LOG_LINE_LENGTH);
    }
    return $lines;
  }

  // Fin du journal du plugin, sans les lignes de niveau debug : elles portent la réponse entière de Škoda, où figurent
  // des noms donnés par l'utilisateur (profils de charge) ; ce journal-là se joint à part, quand il est demandé.
  private static function logTail() {
    $lines = array('', '## Journal du plugin (hors debug)');
    $path = log::getPathToLog('myskoda');
    $rows = file_exists($path) ? @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : false;
    if (!is_array($rows) || count($rows) == 0) {
      $lines[] = 'Journal vide.';
      return $lines;
    }
    $counts = array('ERROR' => 0, 'WARNING' => 0, 'INFO' => 0, 'DEBUG' => 0);
    $kept = array();
    foreach ($rows as $row) {
      $level = preg_match('/^\[[^\]]*\]\[([A-Z]+)\]/', $row, $match) ? $match[1] : '';
      if (isset($counts[$level])) {
        $counts[$level]++;
      }
      if ($level != 'DEBUG') {
        $kept[] = mb_substr($row, 0, self::LOG_LINE_LENGTH);
      }
    }
    $lines[] = self::line('journal.lignes', count($rows) . ' (erreur ' . $counts['ERROR'] . ', avertissement ' . $counts['WARNING'] . ', info ' . $counts['INFO'] . ', debug ' . $counts['DEBUG'] . ')');
    $lines[] = self::line('journal.premiereLigne', substr((string) $rows[0], 0, 21));
    if (count($kept) == 0) {
      $lines[] = 'Aucune ligne hors debug.';
      return $lines;
    }
    foreach (array_slice($kept, -self::LOG_LINES) as $row) {
      $lines[] = $row;
    }
    return $lines;
  }

  // Modèle Škoda reconnu dans un nom de véhicule, ou null
  private static function modelOf($_name) {
    foreach (array('Enyaq', 'Elroq', 'Epiq', 'Karoq', 'Kodiaq', 'Kamiq', 'Kushaq', 'Kylaq', 'Octavia', 'Superb', 'Fabia', 'Scala', 'Rapid', 'Citigo', 'Slavia', 'Yeti') as $model) {
      if (stripos((string) $_name, $model) !== false) {
        return $model;
      }
    }
    return null;
  }

  // Dernière passe : retire du texte tout ce qui identifie ou localise. Rend le nombre de remplacements faits.
  private static function scrub(&$_text, $_eqLogics) {
    $replaced = 0;
    $swap = function ($_search, $_by) use (&$_text, &$replaced) {
      $_search = trim((string) $_search);
      // Une valeur trop courte se retrouverait par hasard dans un mot ou un nombre du rapport
      if (mb_strlen($_search) < 4) {
        return;
      }
      $count = 0;
      $_text = str_ireplace($_search, $_by, $_text, $count);
      $replaced += $count;
    };
    $swap(config::byKey('apikey', 'myskoda'), '[clé]');
    $rank = 0;
    foreach ($_eqLogics as $eqLogic) {
      $rank++;
      $swap($eqLogic->getConfiguration('vin'), '[numéro de série]');
      // Le code S-PIN n'est jamais écrit dans le rapport, et il ne faut surtout pas le chercher pour le masquer :
      // remplacer ses quatre chiffres partout (dates, kilométrage) reviendrait à les montrer
      // Le nom donné au véhicule dans l'application MyŠkoda peut être personnel (« Enyaq de Marie »)
      $name = $eqLogic->getCmd('info', 'name');
      if (is_object($name) && self::modelOf($name->execCmd()) !== trim((string) $name->execCmd())) {
        $swap($name->execCmd(), '[nom du véhicule]');
      }
      $plate = $eqLogic->getCmd('info', 'license_plate');
      if (is_object($plate)) {
        $swap($plate->execCmd(), '[plaque]');
      }
      // Le nom complet (objet et équipement) d'abord, tel que le plugin l'écrit dans son journal et ses messages
      $swap($eqLogic->getHumanName(), 'Véhicule ' . $rank);
      // Un profil de charge porte le nom que l'utilisateur lui a donné, souvent celui d'un lieu
      $profiles = $eqLogic->getCache('chargingProfiles', array());
      foreach (is_array($profiles) ? $profiles : array() as $profile) {
        $swap(isset($profile['name']) ? $profile['name'] : '', '[point de recharge]');
      }
      foreach ($eqLogic->getPlaces() as $place) {
        foreach (array('name', 'address') as $key) {
          if (isset($place[$key])) {
            $swap($place[$key], '[lieu]');
          }
        }
      }
    }
    // Nom complet d'un équipement renommé, déplacé ou supprimé depuis : « [objet][équipement] », tel qu'il reste dans
    // les anciennes lignes du journal. L'en-tête d'une ligne, « [date][NIVEAU] », a la même forme et n'est pas touché.
    $count = 0;
    $_text = preg_replace('/\[(?!\d{4}-\d{2}-\d{2} )[^\]\n]+\]\[(?!(?:ERROR|WARNING|INFO|DEBUG|ALERT|NOTICE|CRITICAL|EMERGENCY)\])[^\]\n]+\]/u', '[équipement]', $_text, -1, $count);
    $replaced += $count;
    // Positions : tout nombre à quatre décimales ou plus
    $count = 0;
    $_text = preg_replace('/-?\d{1,3}\.\d{4,}/', '[position]', $_text, -1, $count);
    $replaced += $count;
    return $replaced;
  }

  // Rend le texte du rapport et le nom du fichier sous lequel le télécharger
  public static function generate() {
    $eqLogics = eqLogic::byType('myskoda');
    $lines = array('# Rapport de diagnostic MySkoda', '', 'Rapport généré le ' . date('d/m/Y H:i:s'), '',
      'Ce rapport rassemble l\'état du plugin et de chaque véhicule pour une demande d\'aide. Il ne contient ni clé, ni code S-PIN, ni numéro de série, ni plaque, ni nom, ni adresse, ni position : il peut être transmis tel quel.');
    $lines = array_merge($lines, self::environment(), self::settings());
    if (count($eqLogics) == 0) {
      $lines[] = '';
      $lines[] = '## Véhicules';
      $lines[] = 'Aucun véhicule.';
    }
    $rank = 0;
    foreach ($eqLogics as $eqLogic) {
      $rank++;
      // Un véhicule illisible ne doit pas priver du reste du rapport
      try {
        $lines = array_merge($lines, self::vehicle($eqLogic, $rank));
      } catch (\Throwable $e) {
        $lines[] = '';
        $lines[] = '## Véhicule ' . $rank;
        $lines[] = self::line('erreur', $e->getMessage());
      }
    }
    $lines = array_merge($lines, self::messages(), self::logTail());
    $text = implode("\n", $lines);
    $replaced = self::scrub($text, $eqLogics);
    $text .= "\n\n## Vérification\n" . self::line('valeurs.retirees', $replaced) . "\n";
    return array('report' => $text, 'fileName' => 'myskoda-diagnostic-' . date('Ymd-Hi') . '.txt');
  }
}
