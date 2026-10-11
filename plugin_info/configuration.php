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
    <!-- Le code S-PIN se saisit sur la page de chaque véhicule : il est propre au véhicule et à son utilisateur principal -->

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
      <label class="col-md-4 control-label">{{Relecture après une commande}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Délai entre l'envoi d'une commande (charge, climatisation) et la lecture qui en montre l'effet sur la tuile et dans les scénarios. Plus court, l'état suit plus vite ; trop court, la voiture n'a pas encore répondu et l'état ne changera qu'à la lecture suivante}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="refresh_after_command">
          <option value="60">{{1 minute}}</option>
          <option value="45">{{45 secondes}}</option>
          <option value="30">{{30 secondes}}</option>
          <option value="20">{{20 secondes}}</option>
          <option value="120">{{2 minutes}}</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Rayon des lieux favoris (m)}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Un stationnement fait à moins de cette distance d'un lieu favori (Maison, Travail…) lui est rattaché. La position transmise par la voiture peut s'écarter de plus de cent mètres au même endroit. Chaque favori peut avoir son propre rayon, réglé dans le journal de bord, onglet « Lieux »}}"></i></sup>
      </label>
      <div class="col-md-4">
        <input class="configKey form-control" data-l1key="favorite_radius" placeholder="200"/>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Unités d'affichage}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Métrique : kilomètres, km/h, kWh ou litres aux 100 km. Impérial : miles, mph, miles par kWh ou miles par gallon (gallon britannique). Ce réglage vaut pour tous les véhicules et ne change que l'affichage de la tuile, du journal de bord et de ses exports : les valeurs des commandes restent en kilomètres, vos scénarios ne changent pas. Les températures restent en °C}}"></i></sup>
      </label>
      <div class="col-md-4">
        <select class="configKey form-control" data-l1key="units">
          <option value="metric">{{Métrique (km)}}</option>
          <option value="imperial">{{Impérial (miles)}}</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Prix du kWh (€)}}
        <sup><i class="fas fa-question-circle tooltips" title="{{Facultatif. Sert au journal de bord pour estimer le coût des trajets et des recharges. Un prix propre à un lieu (borne publique, par exemple) se saisit dans le journal de bord, onglet « Lieux »}}"></i></sup>
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
  <fieldset>
    <legend>{{Diagnostic et support}}</legend>
    <div class="form-group">
      <label class="col-md-4 control-label">{{Rapport de diagnostic}}
        <sup><i class="fas fa-question-circle tooltips" title="{{État du plugin et de chaque véhicule, à joindre quand vous signalez un problème. Il ne contient ni clé, ni code S-PIN, ni numéro de série, ni plaque, ni nom, ni adresse, ni position}}"></i></sup>
      </label>
      <div class="col-md-8">
        <a class="btn btn-default" id="bt_myskodaDiagnostic"><i class="fas fa-file-medical-alt"></i> {{Générer un rapport de diagnostic}}</a>
        <span id="span_myskodaDiagnostic" style="margin-left:8px;"></span>
        <div id="div_myskodaDiagnostic" style="display:none;margin-top:10px;">
          <textarea id="ta_myskodaDiagnostic" class="form-control" readonly rows="18" spellcheck="false" style="font-family:monospace;font-size:12px;"></textarea>
          <div style="margin-top:5px;">
            <a class="btn btn-default" id="bt_myskodaDiagnosticCopy"><i class="fas fa-copy"></i> {{Copier le rapport}}</a>
            <a class="btn btn-default" id="bt_myskodaDiagnosticDownload"><i class="fas fa-download"></i> {{Télécharger le rapport}}</a>
          </div>
        </div>
      </div>
    </div>
  </fieldset>
</form>
<script>
// Rapport de diagnostic : demandé au plugin, affiché tel quel, puis copié ou téléchargé depuis le navigateur
(function() {
  var button = document.getElementById('bt_myskodaDiagnostic')
  var status = document.getElementById('span_myskodaDiagnostic')
  var zone = document.getElementById('div_myskodaDiagnostic')
  var area = document.getElementById('ta_myskodaDiagnostic')
  var fileName = 'myskoda-diagnostic.txt'
  var busy = false

  button.addEventListener('click', function() {
    if (busy) {
      return
    }
    busy = true
    status.textContent = '{{Rapport en cours de préparation…}}'
    fetch('plugins/myskoda/core/ajax/myskoda.ajax.php', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ action: 'getDiagnostic' }) })
      .then(function(response) { return response.json() })
      .then(function(answer) {
        if (answer.state != 'ok') {
          throw new Error(String(answer.result).replace(/<[^>]+>/g, ''))
        }
        area.value = answer.result.report
        fileName = answer.result.fileName || fileName
        zone.style.display = ''
        status.textContent = '{{Rapport prêt : copiez-le ou téléchargez-le pour le joindre à votre message.}}'
      })
      .catch(function(error) {
        status.textContent = '{{Le rapport n\'a pas pu être préparé}} : ' + error.message
      })
      .then(function() { busy = false })
  })

  // Le presse-papiers du navigateur n'est accessible qu'en https : en http (accès local), on passe par la sélection
  function copyBySelection() {
    area.focus()
    area.select()
    var done = false
    try {
      done = document.execCommand('copy')
    } catch (error) {
      done = false
    }
    status.textContent = done ? '{{Rapport copié.}}' : '{{Copie automatique impossible : le rapport est sélectionné, copiez-le avec Ctrl+C.}}'
  }

  document.getElementById('bt_myskodaDiagnosticCopy').addEventListener('click', function() {
    if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(area.value).then(function() {
        status.textContent = '{{Rapport copié.}}'
      }, copyBySelection)
    } else {
      copyBySelection()
    }
  })

  document.getElementById('bt_myskodaDiagnosticDownload').addEventListener('click', function() {
    var url = URL.createObjectURL(new Blob([area.value], { type: 'text/plain;charset=utf-8' }))
    var link = document.createElement('a')
    link.href = url
    link.download = fileName
    // Jeedom intercepte sur la page tout clic sur un lien pour charger son adresse comme une page : ce clic-ci ne doit
    // pas lui parvenir, sinon le téléchargement est remplacé par une erreur « Failed to fetch »
    link.addEventListener('click', function(event) { event.stopPropagation() })
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    setTimeout(function() { URL.revokeObjectURL(url) }, 1000)
  })
})()
</script>
