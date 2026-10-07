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

/* Ajout d'un véhicule par son numéro de série : le plugin le vérifie auprès de Škoda et crée l'équipement au nom du modèle */
;(function() {
  var button = document.getElementById('bt_myskodaAddVehicle')
  if (!button) {
    return
  }
  function alertUser(text) {
    text = String(text).replace(/<[^>]+>/g, '')
    if (window.jeedomUtils && typeof jeedomUtils.showAlert == 'function') {
      jeedomUtils.showAlert({ message: text, level: 'danger' })
    } else {
      window.alert(text)
    }
  }
  button.addEventListener('click', function() {
    jeeDialog.prompt('{{Numéro de série (VIN) du véhicule : 17 caractères, visibles dans l\'application MyŠkoda et sur la carte grise}}', function(vin) {
      if (vin === null || String(vin).trim() == '') {
        return
      }
      fetch('plugins/myskoda/core/ajax/myskoda.ajax.php', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ action: 'addVehicle', vin: vin }) })
        .then(function(response) { return response.json() })
        .then(function(answer) {
          if (answer.state != 'ok') {
            alertUser(answer.result)
            return
          }
          window.location.href = 'index.php?v=d&m=myskoda&p=myskoda&id=' + answer.result.id
        })
        .catch(function(error) { alertUser(error.message) })
    })
  })
})()

/* Appelée par Jeedom à l'ouverture d'un équipement : ne montre que les réglages de l'énergie du véhicule
   (batterie pour un électrique, réservoir et prix du litre pour un thermique, les deux pour un hybride).
   Tant que le véhicule n'a rien remonté, tout est montré. */
function printEqLogic(_eqLogic) {
  var ids = (_eqLogic.cmd || []).map(function(cmd) { return cmd.logicalId })
  var hasBattery = ids.indexOf('battery_level') >= 0
  var hasFuel = ids.indexOf('fuel_level') >= 0
  document.querySelectorAll('[data-ms-energy]').forEach(function(group) {
    var wanted = (group.getAttribute('data-ms-energy') == 'electric') ? hasBattery : hasFuel
    group.style.display = ((!hasBattery && !hasFuel) || wanted) ? '' : 'none'
  })

  // Photo du véhicule en haut de la colonne de droite : celle de la commande « Image », connue après la première lecture.
  // Elle est masquée pour un équipement qui n'en a pas encore, ou si elle ne se charge pas.
  var photoBox = document.getElementById('div_myskodaPhoto')
  var photo = document.getElementById('img_myskodaPhoto')
  if (photoBox && photo) {
    photoBox.style.display = 'none'
    photo.removeAttribute('src')
    photo.onerror = function() { photoBox.style.display = 'none' }
    // Sous la photo, la désignation du véhicule donnée par Škoda (par exemple « KAROQ STY TS110/1.5A7F »)
    var photoTitle = document.getElementById('div_myskodaPhotoTitle')
    if (photoTitle) {
      photoTitle.textContent = ''
      var nameCmd = (_eqLogic.cmd || []).filter(function(cmd) { return cmd.logicalId == 'name' })[0]
      if (nameCmd) {
        jeedom.cmd.execute({
          id: nameCmd.id,
          notify: false,
          error: function() {},
          success: function(value) {
            photoTitle.textContent = (typeof value == 'string') ? value : ''
          }
        })
      }
    }
    var imageCmd = (_eqLogic.cmd || []).filter(function(cmd) { return cmd.logicalId == 'image' })[0]
    if (imageCmd) {
      jeedom.cmd.execute({
        id: imageCmd.id,
        notify: false,
        error: function() {},
        success: function(value) {
          if (typeof value == 'string' && value.indexOf('https://') == 0) {
            photo.src = value
            photoBox.style.display = ''
          }
        }
      })
    }
  }

  // Motorisation affichée à côté du titre « Paramètres spécifiques » : celle que Škoda indique,
  // à défaut celle qui se déduit des énergies remontées
  var badge = document.getElementById('span_myskodaCarType')
  if (!badge) {
    return
  }
  var labels = { ELECTRIC: '{{Électrique}}', GASOLINE: '{{Essence}}', DIESEL: '{{Diesel}}', HYBRID: '{{Hybride}}', CNG: '{{Gaz naturel}}', LPG: '{{GPL}}' }
  var deduced = (hasBattery && hasFuel) ? '{{Hybride}}' : (hasBattery ? '{{Électrique}}' : (hasFuel ? '{{Thermique}}' : ''))
  badge.textContent = deduced ? '{{Motorisation}} : ' + deduced : ''
  var typeCmd = (_eqLogic.cmd || []).filter(function(cmd) { return cmd.logicalId == 'car_type' })[0]
  if (typeCmd) {
    jeedom.cmd.execute({
      id: typeCmd.id,
      notify: false,
      error: function() {},
      success: function(value) {
        // Pour un hybride, Škoda donne parfois le carburant du moteur thermique : l'hybride déduit des deux énergies prime
        if (labels[value] && !(hasBattery && hasFuel)) {
          badge.textContent = '{{Motorisation}} : ' + labels[value]
        }
      }
    })
  }
}

/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
    var _cmd = { configuration: {} }
  }
  if (!isset(_cmd.configuration)) {
    _cmd.configuration = {}
  }
  var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
  tr += '<option value="">{{Aucune}}</option>'
  tr += '</select>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
  tr += '<div style="margin-top:7px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
  tr += '</div>'
  tr += '</td>'
  tr += '<td>';
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>';
  tr += '</td>';
  tr += '<td>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> {{Tester}}</a>'
  }
  // Pas d'icône de suppression : les commandes sont créées par le plugin d'après le véhicule (dontRemoveCmd)
  tr += '</td>'
  tr += '</tr>'
  $('#table_cmd tbody').append(tr)
  var tr = $('#table_cmd tbody tr').last()
  jeedom.eqLogic.buildSelectCmd({
    id: $('.eqLogicAttr[data-l1key=id]').value(),
    filter: { type: 'info' },
    error: function (error) {
      $('#div_alert').showAlert({ message: error.message, level: 'danger' })
    },
    success: function (result) {
      tr.find('.cmdAttr[data-l1key=value]').append(result)
      tr.setValues(_cmd, '.cmdAttr')
      jeedom.cmd.changeType(tr, init(_cmd.subType))
    }
  })
}
