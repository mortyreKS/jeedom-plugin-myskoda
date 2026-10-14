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
* Client de l'API publique officielle MyŠkoda.
* Référence : https://public.api.connect.skoda-auto.cz/docs (spécification OpenAPI 1.1.0).
* Ce fichier ne dépend pas de Jeedom : il ne journalise rien et ne connaît que la clé qu'on lui donne.
*/

class myskodaApiException extends Exception {
  private $problemType;
  private $retryAfter;

  public function __construct($_message, $_httpStatus = 0, $_problemType = '', $_retryAfter = null) {
    parent::__construct($_message, $_httpStatus);
    $this->problemType = $_problemType;
    $this->retryAfter = $_retryAfter;
  }

  // Code HTTP de la réponse ; 0 si la requête n'a pas abouti (réseau, délai)
  public function getHttpStatus() {
    return $this->getCode();
  }

  // Dernier segment du champ « type » de la réponse RFC 9457, par exemple « api-key-expired » ; vide si générique
  public function getProblemType() {
    return $this->problemType;
  }

  // Attente demandée par le serveur, en secondes ; null si absente
  public function getRetryAfter() {
    return $this->retryAfter;
  }
}

class myskodaApi {
  const BASE_URL = 'https://public.api.connect.skoda-auto.cz/api/v1';
  // Délai total d'une demande, en secondes : court, parce que la lecture se fait dans la tâche planifiée de Jeedom,
  // commune à tous les plugins
  const TIMEOUT = 15;

  private $apiKey;
  private $baseUrl;
  private $lastHeaders = array();

  // $_baseUrl ne sert qu'aux essais du développeur, contre un faux serveur Škoda installé sur la box
  public function __construct($_apiKey, $_baseUrl = '') {
    $this->apiKey = trim($_apiKey);
    $this->baseUrl = (trim((string) $_baseUrl) != '') ? rtrim(trim($_baseUrl), '/') : self::BASE_URL;
  }

  // État complet du véhicule ; $_include limite la réponse aux parties demandées. Avec $_assoc à false, la réponse est
  // rendue en objets plutôt qu'en tableaux : c'est la seule forme qui se renvoie à Škoda à l'identique (un objet vide
  // « {} » deviendrait sinon une liste vide « [] »).
  public function getVehicle($_vin, $_include = array(), $_assoc = true) {
    $path = '/vehicles/' . rawurlencode($_vin);
    if (count($_include) > 0) {
      $path .= '?include=' . implode(',', array_map('rawurlencode', $_include));
    }
    return $this->request('GET', $path, null, $_assoc);
  }

  // Commande à distance, par exemple command('POST', $vin, 'charging/start')
  public function command($_method, $_vin, $_operation, $_body = null) {
    return $this->request($_method, '/vehicles/' . rawurlencode($_vin) . '/' . $_operation, $_body);
  }

  // En-tête de la dernière réponse (nom insensible à la casse) ; null si absent
  public function getLastHeader($_name) {
    $name = strtolower($_name);
    return isset($this->lastHeaders[$name]) ? $this->lastHeaders[$name] : null;
  }

  private function request($_method, $_path, $_body = null, $_assoc = true) {
    $this->lastHeaders = array();
    $headers = array('X-API-Key: ' . $this->apiKey, 'Accept: application/json');
    $ch = curl_init($this->baseUrl . $_path);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
    if ($_body !== null) {
      $headers[] = 'Content-Type: application/json';
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($_body));
    } elseif ($_method != 'GET') {
      // Commande sans contenu (démarrer ou arrêter la charge, par exemple) : aucun corps n'est envoyé, sans quoi
      // curl annoncerait un formulaire ; la longueur nulle est déclarée explicitement
      $headers[] = 'Content-Length: 0';
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $responseHeaders = array();
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($_ch, $_line) use (&$responseHeaders) {
      $parts = explode(':', $_line, 2);
      if (count($parts) == 2) {
        $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
      }
      return strlen($_line);
    });
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $this->lastHeaders = $responseHeaders;

    if ($raw === false) {
      throw new myskodaApiException('Serveur Škoda injoignable : ' . $error, 0);
    }
    if ($status >= 200 && $status < 300) {
      if ($_assoc) {
        $data = ($raw === '') ? array() : json_decode($raw, true);
        return is_array($data) ? $data : array();
      }
      $data = ($raw === '') ? null : json_decode($raw);
      return is_object($data) ? $data : new stdClass();
    }
    $data = ($raw === '') ? array() : json_decode($raw, true);

    $type = '';
    $detail = '';
    if (is_array($data)) {
      if (isset($data['type']) && strpos($data['type'], '/problems/') !== false) {
        $type = substr($data['type'], strrpos($data['type'], '/') + 1);
      }
      if (isset($data['detail'])) {
        $detail = $data['detail'];
      } elseif (isset($data['title'])) {
        $detail = $data['title'];
      }
    }
    $retryAfter = $this->getLastHeader('Retry-After');
    throw new myskodaApiException(
      'HTTP ' . $status . ($detail != '' ? ' : ' . $detail : ''),
      $status,
      $type,
      is_numeric($retryAfter) ? (int) $retryAfter : null
    );
  }
}
