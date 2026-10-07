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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>
<form class="form-horizontal">
  <fieldset>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Version du plugin}}</label>
      <div class="col-md-4">
        <span class="label label-info" style="font-size:1em;"><?php echo htmlspecialchars(myskoda::getVersionLabel()); ?></span>
      </div>
      <div class="col-md-4">
        {{À indiquer quand vous signalez un problème.}}
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Clé API MyŠkoda}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Clé créée dans l'application MyŠkoda (Profil, Maison intelligente), en y cochant les véhicules à suivre. Elle expire : sa date de fin est dans la commande « Expiration de la clé »}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control inputPassword" data-l1key="apikey" autocomplete="new-password"/>
      </div>
      <div class="col-md-4">
        {{À créer sur le téléphone, dans l'application MyŠkoda : Profil, puis Maison intelligente.}}
        <a href="https://public.api.connect.skoda-auto.cz/docs" target="_blank" rel="noopener">{{Documentation de Škoda}}</a>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Code S-PIN}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Facultatif. Code à 4 chiffres de votre compte MyŠkoda. Škoda ne l'exige que pour démarrer le chauffage auxiliaire (chauffage stationnaire des véhicules thermiques et hybrides qui en sont équipés). Inutile pour la climatisation et la charge d'un véhicule électrique}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control inputPassword" data-l1key="spin" autocomplete="new-password"/>
      </div>
      <div class="col-md-4">
        {{À remplir seulement si votre véhicule a un chauffage auxiliaire.}}
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Lecture à l'arrêt et en charge}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Škoda autorise 20 requêtes par heure et par véhicule, commandes comprises : chaque commande en coûte deux (la commande, puis la relecture une minute après). Ce rythme vaut aussi pendant une recharge, pour garder des requêtes pour les commandes}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="interval">
          <option value="10">{{Toutes les 10 minutes}}</option>
          <option value="5">{{Toutes les 5 minutes}}</option>
          <option value="15">{{Toutes les 15 minutes}}</option>
          <option value="30">{{Toutes les 30 minutes}}</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Lecture en déplacement}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Rythme gardé encore 10 minutes après l'arrêt, et pendant les 10 minutes qui suivent le début ou la fin d'une recharge. Plus la lecture est rapide, plus les trajets sont suivis finement. À 3 minutes, tout le quota part en lectures : il ne reste presque rien pour les commandes}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="interval_active">
          <option value="5">{{Toutes les 5 minutes}}</option>
          <option value="4">{{Toutes les 4 minutes}}</option>
          <option value="3">{{Toutes les 3 minutes}}</option>
          <option value="10">{{Toutes les 10 minutes}}</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Rayon des lieux favoris (m)}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Un stationnement fait à moins de cette distance d'un lieu favori (Maison, Travail…) lui est rattaché. La position transmise par la voiture peut s'écarter de plus de cent mètres au même endroit. Chaque favori peut avoir son propre rayon, réglé dans le journal de bord, sous la carte}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="favorite_radius" placeholder="200"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Prix du kWh (€)}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Facultatif. Sert au journal de bord pour estimer le coût des trajets et des recharges. Un prix propre à un lieu (borne publique, par exemple) se saisit dans le journal, sous la carte}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="kwh_price" placeholder="{{par exemple 0,20}}"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Prix du litre de carburant (€)}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Facultatif, pour les véhicules thermiques. Sert au journal de bord pour estimer le coût des trajets et des pleins. Si un véhicule utilise un autre carburant, son propre prix se saisit sur l'équipement}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="fuel_price" placeholder="{{par exemple 1,75}}"/>
      </div>
    </div>
  </fieldset>
</form>
