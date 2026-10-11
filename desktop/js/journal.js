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

/* Journal de bord : une seule interface pour le panneau desktop et le panneau mobile.
 * myskodaJournal.mount(element, { version: 'dashboard' | 'mobile' }) construit tout dans l'élément donné. */
var myskodaJournal = (function() {
  var EMPTY = '—'
  var AJAX = 'plugins/myskoda/core/ajax/myskoda.ajax.php'
  var CSS = [
    '.msj { padding: 10px 15px; text-align: left; }',
    '.msj-bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }',
    '.msj-bar select, .msj-bar input { height: 32px; padding: 2px 8px; max-width: 100%; }',
    '.msj-btn { display: inline-block; padding: 5px 12px; border-radius: 4px; cursor: pointer; background: rgba(127,127,127,.2); user-select: none; }',
    'a.msj-btn, a.msj-btn:hover { color: inherit; text-decoration: none; }',
    '.msj-btn.msj-on { background: #554E5E; color: #fff; font-weight: bold; }',
    '.msj-message { margin: 10px 0; opacity: .8; }',
    '.msj-banner { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px 30px; padding: 12px 16px; border-radius: 8px; background: rgba(127,127,127,.14); margin-bottom: 12px; }',
    '.msj-state { font-size: 22px; font-weight: bold; }',
    '.msj-figures { display: flex; gap: 24px; align-items: baseline; }',
    '.msj-figures b { font-size: 22px; }',
    '.msj-summary { border-radius: 8px; background: rgba(127,127,127,.14); margin-bottom: 12px; }',
    '.msj-summary-row { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 10px; padding: 10px 16px; border-top: 1px solid rgba(127,127,127,.25); }',
    '.msj-summary-row:first-child { border-top: 0; }',
    '.msj-icon { flex: 0 0 44px; height: 44px; border-radius: 8px; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; }',
    '.msj-cell { flex: 1 1 110px; text-align: center; }',
    '.msj-cell b { display: block; font-size: 20px; }',
    '.msj-parked { background: #6f7a80; } .msj-trip { background: #2a7ab9; } .msj-charge { background: #d9534f; }',
    '.msj-c-trip b { color: #2a7ab9; } .msj-c-charge b { color: #d9534f; }',
    '.msj-timeline { position: relative; height: 22px; border-radius: 4px; background: #6f7a80; overflow: hidden; }',
    '.msj-timeline > div { position: absolute; top: 0; bottom: 0; min-width: 2px; }',
    '.msj-future { background: rgba(255,255,255,.55); }',
    '.msj-hours { display: flex; justify-content: space-between; font-size: 11px; opacity: .7; margin-bottom: 12px; }',
    '.msj-card { display: flex; margin-bottom: 8px; border-radius: 8px; overflow: hidden; background: rgba(127,127,127,.14); }',
    '.msj-card .msj-icon { flex: 0 0 56px; height: auto; border-radius: 0; }',
    // Vignette de taille fixe, quelle que soit la hauteur de la fiche : la photo de Škoda montre la voiture petite au
    // milieu d'un grand vide, elle est donc agrandie dans son cadre
    '.msj-photo { flex: 0 0 104px; height: 58px; align-self: center; margin-left: 8px; border-radius: 6px; overflow: hidden; background: #fff; }',
    '.msj-photo img { display: block; width: 100%; height: 100%; object-fit: cover; transform: scale(1.9); }',
    '@media (max-width: 520px) { .msj-photo { flex-basis: 78px; height: 44px; margin-left: 6px; } }',
    '.msj-card-body { flex: 1 1 auto; display: flex; flex-wrap: wrap; gap: 4px 20px; padding: 8px 14px; align-items: center; min-width: 0; }',
    '.msj-card-main { flex: 1 1 240px; min-width: 0; }',
    '.msj-card-main b { font-size: 15px; }',
    '.msj-card-figures { flex: 0 1 auto; text-align: right; margin-left: auto; }',
    '.msj-card-figures b { font-size: 17px; }',
    '.msj-scroll { overflow-x: auto; }',
    '.msj table { width: 100%; border-collapse: collapse; }',
    '.msj th, .msj td { padding: 5px 8px; border-bottom: 1px solid rgba(127,127,127,.25); white-space: nowrap; text-align: right; }',
    '.msj th:first-child, .msj td:first-child, .msj td.msj-left, .msj th.msj-left { text-align: left; }',
    '.msj td.msj-wrap { white-space: normal; min-width: 160px; }',
    '.msj tr.msj-group td { font-weight: bold; background: rgba(127,127,127,.14); }',
    // Tableau resserré, puis en fiches, quand il ne tient pas dans la largeur (voir fitTables)
    '.msj table.msj-compact th { white-space: normal; vertical-align: bottom; }',
    '.msj table.msj-compact th, .msj table.msj-compact td { padding: 4px 5px; font-size: 12px; }',
    // Les fiches se rangent côte à côte tant que la largeur le permet
    '.msj table.msj-stack { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 8px; align-items: start; }',
    '.msj table.msj-stack tr.msj-head { display: none; }',
    '.msj table.msj-stack tr { display: block; padding: 5px 10px; border-radius: 8px; background: rgba(127,127,127,.14); }',
    '.msj table.msj-stack tr.msj-group { background: rgba(127,127,127,.28); }',
    '.msj table.msj-stack td { display: flex; justify-content: space-between; gap: 12px; padding: 2px 0; border: 0; white-space: normal; text-align: right; min-width: 0; background: none; }',
    '.msj table.msj-stack td::before { content: attr(data-label); flex: 0 0 auto; font-weight: normal; opacity: .75; text-align: left; }',
    '.msj table.msj-stack td:empty { display: none; }',
    // La première cellule (date, ou intitulé d'un total) sert de titre à la fiche : son intitulé de colonne est inutile
    '.msj table.msj-stack td:first-child { font-weight: bold; font-size: 14px; justify-content: flex-start; text-align: left; }',
    '.msj table.msj-stack td:first-child::before { content: none; }',
    '.msj-curve { display: block; width: 100%; height: 64px; }',
    '.msj-curve polygon { fill: rgba(92,184,92,.3); } .msj-curve polyline { fill: none; stroke: #5cb85c; stroke-width: 2; vector-effect: non-scaling-stroke; }',
    '.msj-caption { font-size: 11px; opacity: .7; }',
    '.msj-map { height: 440px; border-radius: 8px; margin-bottom: 10px; z-index: 0; }',
    '.msj-map .leaflet-control-attribution, .msj-map .leaflet-control-attribution a { font-size: 11px; line-height: 1.4; font-weight: normal; text-shadow: none; }',
    '.msj-place { display: flex; flex-wrap: wrap; gap: 4px 10px; align-items: center; padding: 5px 0; border-bottom: 1px solid rgba(127,127,127,.25); }',
    '.msj-place input[type=text] { flex: 0 1 200px; height: 30px; padding: 2px 8px; }',
    '.msj-place input[type=checkbox] { flex: 0 0 auto; margin: 0; }',
    // Un bouton qui est un lien garde l'aspect des autres boutons, quel que soit le thème (la version mobile colore les liens)
    '.msj a.msj-btn, .msj a.msj-btn:visited, .msj a.msj-btn:hover { color: inherit !important; text-decoration: none !important; font-weight: normal; text-shadow: none; }',
    // Ligne d'un lieu : le nom reste court, l'adresse prend la place ; les petits réglages sont dans la partie dépliable
    '.msj-place input.msj-name { flex: 1 1 150px; max-width: 240px; }',
    '.msj-place input.msj-address { flex: 3 1 260px; }',
    '.msj-place input.msj-small { flex: 0 0 96px; width: 96px; }',
    '.msj-star { flex: 0 0 auto; cursor: pointer; font-size: 19px; padding: 2px 3px; color: rgba(127,127,127,.75); }',
    '.msj-star.msj-star-on { color: #f1c40f; }',
    '.msj-place-more { flex: 1 1 100%; display: flex; flex-wrap: wrap; gap: 6px 14px; align-items: center; padding: 4px 0 6px 28px; }',
    '.msj-field { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-weight: normal; white-space: nowrap; }',
    '.msj-field select { height: 30px; padding: 2px 6px; }',
    '@media (max-width: 520px) { .msj-place input.msj-name { max-width: none; } .msj-place input.msj-address { order: 10; flex-basis: 100%; } .msj-place-more { order: 11; padding-left: 0; } }',
    // Sur un écran large, la place ne manque pas : les réglages sont toujours visibles, à la suite sur la ligne, sans clic.
    // Le repli ne sert que sur les écrans étroits.
    '@media (min-width: 900px) { .msj-place-more { display: flex !important; flex: 0 1 auto; padding: 0; margin-left: auto; } .msj-place .msj-toggle { display: none; } .msj-place input.msj-address { flex: 2 1 220px; } }',
    '.msj-place input.msj-price { flex: 0 1 190px; }',
    '.msj-kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; margin-bottom: 14px; }',
    '.msj-kpi { border-radius: 8px; background: rgba(127,127,127,.14); padding: 10px 14px; }',
    '.msj-kpi b { font-size: 22px; display: block; }',
    '.msj-chart { margin-bottom: 14px; }',
    '.msj-chart-title { font-weight: bold; margin-bottom: 4px; }',
    '.msj-bars { display: block; width: 100%; height: 90px; border-bottom: 1px solid rgba(127,127,127,.5); }',
    '.msj-tiles { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; position: relative; }',
    // La tuile du dashboard a une largeur fixe ; dans le journal elle ne doit jamais dépasser l'écran (version ordinateur
    // ouverte sur un téléphone, par exemple depuis une application tierce) : son conteneur est borné, et elle avec.
    '.msj-tiles > div { max-width: 100%; min-width: 0; }',
    '.msj-tiles .eqLogic-widget { position: relative !important; left: auto !important; top: auto !important; margin: 0 !important; max-width: 100% !important; box-sizing: border-box; }',
    '.msj-note { font-size: 12px; opacity: .75; margin-top: 10px; }'
  ].join('\n')

  function el(tag, className, text) {
    var node = document.createElement(tag)
    if (className) {
      node.className = className
    }
    if (text !== undefined && text !== null) {
      node.textContent = text
    }
    return node
  }

  // Langue des dates et des nombres : le libellé « fr-FR » se traduit comme les autres (« en-GB » en anglais), ce qui
  // donne les noms des jours et des mois et le séparateur décimal de la langue de Jeedom
  var LOCALE = '{{fr-FR}}'
  var DECIMAL = (1.5).toLocaleString(LOCALE).charAt(1) == '.' ? '.' : ','

  function show(value, unit, decimals) {
    if (value === null || value === undefined) {
      return EMPTY
    }
    var shown = convert(value, unit)
    return (shown[0] === null) ? EMPTY : format(shown[0], shown[1], decimals)
  }

  // Nombre mis en forme avec son unité, sans conversion
  function format(value, unit, decimals) {
    return Number(value).toLocaleString(LOCALE, { minimumFractionDigits: decimals || 0, maximumFractionDigits: decimals || 0 }) + (unit ? ' ' + unit : '')
  }

  // Unités d'affichage, réglées dans la configuration du plugin (action getUnits) : en « impérial », les distances
  // passent en miles, les vitesses en mph, et les consommations s'inversent comme on les exprime au Royaume-Uni
  // (miles par kWh, miles par gallon britannique). Le serveur, lui, ne manipule que des kilomètres.
  var IMPERIAL = false
  var MILES = 0.621371

  // Rend [valeur, unité] dans l'unité d'affichage ; une consommation nulle n'a pas d'équivalent (valeur null)
  function convert(value, unit) {
    if (!IMPERIAL || value === null || value === undefined) {
      return [value, unit]
    }
    if (unit == 'km') {
      return [value * MILES, 'mi']
    }
    if (unit == 'km/h') {
      return [value * MILES, 'mph']
    }
    if (unit == 'kWh/100 km') {
      return [value > 0 ? 100 * MILES / value : null, 'mi/kWh']
    }
    if (unit == 'L/100 km') {
      return [value > 0 ? 282.481 / value : null, 'mpg']
    }
    return [value, unit]
  }

  // Unité affichée pour une unité métrique donnée
  function unitOf(unit) {
    return convert(1, unit)[1]
  }

  function signed(value, unit, decimals) {
    return (value === null || value === undefined) ? EMPTY : (value > 0 ? '+' : '') + show(value || 0, unit, decimals)
  }

  function duration(minutes) {
    minutes = Math.round(minutes)
    return Math.floor(minutes / 60) + ' h ' + ('0' + (minutes % 60)).slice(-2)
  }

  function pad(number) {
    return ('0' + number).slice(-2)
  }

  function isoDate(date) {
    return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
  }

  // 'AAAA-MM-JJ HH:MM:SS' -> Date locale
  function parse(text) {
    var p = text.split(/[- :]/)
    return new Date(p[0], p[1] - 1, p[2], p[3] || 0, p[4] || 0, p[5] || 0)
  }

  function hour(text) {
    return text.substr(11, 5)
  }

  function dayLabel(text) {
    return parse(text).toLocaleDateString(LOCALE, { weekday: 'short', day: 'numeric', month: 'short' })
  }

  function since(text) {
    if (!text) {
      return ''
    }
    return (text.substr(0, 10) == isoDate(new Date())) ? hour(text) : dayLabel(text) + ' ' + hour(text)
  }

  // Un trajet de moins d'une minute est un trajet reconstitué d'après le compteur : sa durée n'est pas connue
  function tripDuration(row) {
    return (row.type == 'trip' && !row.ongoing && row.minutes < 1) ? '{{durée inconnue}}' : duration(row.minutes)
  }

  function battery(row) {
    return (row.levelStart === null || row.levelEnd === null) ? EMPTY : show(row.levelStart, '%') + ' → ' + show(row.levelEnd, '%')
  }

  function sum(rows, key) {
    return rows.reduce(function(total, row) {
      return total + (row[key] === null || row[key] === undefined ? 0 : Number(row[key]))
    }, 0)
  }

  function has(rows, key) {
    return rows.some(function(row) { return row[key] !== null && row[key] !== undefined })
  }

  function request(data, done, fail) {
    fetch(AJAX, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(data) })
      .then(function(response) { return response.json() })
      .then(function(answer) {
        if (answer.state != 'ok') {
          throw new Error(String(answer.result).replace(/<[^>]+>/g, ''))
        }
        done(answer.result)
      })
      .catch(function(error) { fail(error.message) })
  }

  function mount(root, options) {
    var version = (options && options.version) || 'dashboard'
    var vehicles = []
    if (!document.getElementById('style_myskodaJournal')) {
      var style = el('style')
      style.id = 'style_myskodaJournal'
      style.textContent = CSS
      document.head.appendChild(style)
    }
    root.innerHTML = ''
    root.classList.add('msj')

    // Vocabulaire et unités selon l'énergie du véhicule choisi
    var WORDS = {
      electric: {
        fuel: false, energy: 'kWh', consumption: 'kWh/100 km', price: '€/kWh', level: '{{Batterie}}', levelLower: '{{batterie}}',
        refills: '{{recharge(s)}}', refillTab: '{{Recharges}}', refilled: '{{rechargés}}', refillCost: '{{coût des recharges}}',
        refillChart: '{{Énergie rechargée}}', noRefill: '{{Aucune recharge sur cette période.}}', icon: 'fas fa-plug', file: 'recharges',
        capacityNote: '{{Pour obtenir les kWh, saisissez la capacité de la batterie sur l\'équipement.}}',
        priceNote: '{{Pour obtenir les coûts, saisissez le prix du kWh dans la configuration du plugin.}}'
      },
      fuel: {
        fuel: true, energy: 'L', consumption: 'L/100 km', price: '€/L', level: '{{Carburant}}', levelLower: '{{carburant}}',
        refills: '{{plein(s)}}', refillTab: '{{Pleins}}', refilled: '{{ajoutés}}', refillCost: '{{coût des pleins}}',
        refillChart: '{{Carburant ajouté}}', noRefill: '{{Aucun plein sur cette période.}}', icon: 'fas fa-gas-pump', file: 'pleins',
        capacityNote: '{{Pour obtenir les litres, saisissez la contenance du réservoir sur l\'équipement.}}',
        priceNote: '{{Pour obtenir les coûts, saisissez le prix du litre de carburant dans la configuration du plugin.}}'
      }
    }
    var U = WORDS.electric
    var vehicleBar = el('div', 'msj-bar')
    var state = { tab: 'car', day: isoDate(new Date()), start: isoDate(new Date(Date.now() - 30 * 86400000)), end: isoDate(new Date()), openPlaces: {} }
    var bar = el('div', 'msj-bar')
    var vehicle = el('select')
    var tabs = {}
    var dayBar = el('div', 'msj-bar')
    var periodBar = el('div', 'msj-bar')
    var message = el('div', 'msj-message')
    var content = el('div')
    bar.appendChild(vehicle)
    ;[['car', '{{Véhicule}}'], ['day', '{{Journée}}'], ['map', '{{Carte}}'], ['places', '{{Lieux}}'], ['trips', '{{Trajets}}'], ['charges', '{{Recharges}}'], ['stats', '{{Statistiques}}']].forEach(function(tab) {
      tabs[tab[0]] = el('span', 'msj-btn', tab[1])
      tabs[tab[0]].addEventListener('click', function() {
        state.tab = tab[0]
        refresh()
      })
      bar.appendChild(tabs[tab[0]])
    })

    // Raccourcis vers les équipements et la configuration du plugin (administrateurs, panneau desktop)
    ;[['equipments', 'fas fa-car', '{{Équipements}}'], ['configuration', 'fas fa-wrench', '{{Configuration}}']].forEach(function(shortcut, index) {
      var url = root.getAttribute('data-' + shortcut[0])
      if (!url) {
        return
      }
      var link = el('a', 'msj-btn')
      link.href = url
      link.appendChild(el('i', shortcut[1]))
      link.appendChild(document.createTextNode(' ' + shortcut[2]))
      if (index == 0) {
        link.style.marginLeft = 'auto'
      }
      bar.appendChild(link)
    })

    var previous = el('span', 'msj-btn', '‹')
    var dayInput = el('input')
    var next = el('span', 'msj-btn', '›')
    var today = el('span', 'msj-btn', '{{Aujourd\'hui}}')
    dayInput.type = 'date'
    function shift(days) {
      var date = parse(state.day)
      date.setDate(date.getDate() + days)
      state.day = isoDate(date)
      state.dayPicked = true
      refresh()
    }
    previous.addEventListener('click', function() { shift(-1) })
    next.addEventListener('click', function() { shift(1) })
    today.addEventListener('click', function() {
      state.day = isoDate(new Date())
      state.dayPicked = true
      refresh()
    })
    dayInput.addEventListener('change', function() {
      if (dayInput.value) {
        state.day = dayInput.value
        state.dayPicked = true
        refresh()
      }
    })
    ;[previous, dayInput, next, today].forEach(function(node) { dayBar.appendChild(node) })

    var startInput = el('input')
    var endInput = el('input')
    startInput.type = endInput.type = 'date'
    ;[startInput, endInput].forEach(function(input) {
      input.addEventListener('change', function() {
        if (startInput.value && endInput.value) {
          state.start = startInput.value
          state.end = endInput.value
          refresh()
        }
      })
    })
    periodBar.appendChild(el('span', null, '{{Du}}'))
    periodBar.appendChild(startInput)
    periodBar.appendChild(el('span', null, '{{au}}'))
    periodBar.appendChild(endInput)
    ;[['{{7 jours}}', 6, 'day'], ['{{30 jours}}', 29, 'day'], ['{{12 mois}}', 12, 'month'], ['{{Cette année}}', 0, 'year']].forEach(function(shortcut) {
      var button = el('span', 'msj-btn', shortcut[0])
      button.addEventListener('click', function() {
        var from = new Date()
        if (shortcut[2] == 'day') {
          from.setDate(from.getDate() - shortcut[1])
        } else if (shortcut[2] == 'month') {
          from.setMonth(from.getMonth() - shortcut[1])
          from.setDate(from.getDate() + 1)
        } else {
          from = new Date(from.getFullYear(), 0, 1)
        }
        state.start = isoDate(from)
        state.end = isoDate(new Date())
        refresh()
      })
      periodBar.appendChild(button)
    })

    vehicle.style.display = 'none'
    ;[vehicleBar, bar, dayBar, periodBar, message, content].forEach(function(node) { root.appendChild(node) })
    vehicle.addEventListener('change', refresh)

    function fail(text) {
      content.innerHTML = ''
      message.textContent = text
    }

    // Le journal est tiré de l'historique Jeedom : si l'historisation d'une commande nécessaire a été décochée,
    // il reste vide sans raison apparente. L'avertissement nomme les commandes à recocher.
    function warnHistory(result) {
      if (result.now && result.now.unavailableSince) {
        var down = el('div', 'msj-message', '{{Škoda ne fournit plus les données du véhicule depuis}} ' + hour(result.now.unavailableSince)
          + '. {{Les valeurs sont les dernières reçues ; un trajet ou une recharge en cours se terminera à la bonne heure quand Škoda répondra de nouveau.}}')
        down.style.cssText = 'padding:8px 10px;border-radius:4px;background:rgba(240,173,78,0.18);opacity:1;'
        content.appendChild(down)
      }
      if (!result.notHistorized || result.notHistorized.length == 0) {
        return
      }
      var box = el('div', 'msj-message', '{{Le journal de bord ne peut pas se remplir : l\'historisation est décochée pour}} ' + result.notHistorized.join(', ')
        + '. {{Pour la rétablir : page de l\'équipement, onglet Commandes, case « Historiser » de ces commandes, puis Sauvegarder. Le journal reprendra à partir de ce moment ; ce qui s\'est passé entre-temps n\'est pas récupérable.}}')
      box.style.cssText = 'padding:8px 10px;border-radius:4px;background:rgba(240,173,78,0.18);opacity:1;'
      content.appendChild(box)
    }

    function note(result) {
      var parts = ['{{Le journal est reconstitué à partir de l\'historique Jeedom ; les heures sont celles où la voiture a envoyé ses données. Il commence vide le jour où le plugin est installé : Škoda ne fournit aucun historique.}}']
      if (!(result.capacity > 0)) {
        parts.push(U.capacityNote)
      }
      if (!(result.price > 0)) {
        parts.push(U.priceNote)
      }
      return el('div', 'msj-note', parts.join(' '))
    }

    function summaryRow(kind, icon, cells) {
      var row = el('div', 'msj-summary-row msj-c-' + kind)
      var badge = el('div', 'msj-icon msj-' + kind)
      badge.appendChild(el('i', icon))
      row.appendChild(badge)
      cells.forEach(function(cell) {
        var node = el('div', 'msj-cell')
        node.appendChild(el('b', null, cell[0]))
        node.appendChild(el('span', null, cell[1]))
        row.appendChild(node)
      })
      return row
    }

    // Véhicule choisi en haut du journal ; null tant que la liste n'est pas chargée
    function currentVehicle() {
      for (var i = 0; i < vehicles.length; i++) {
        if (String(vehicles[i].id) == vehicle.value) {
          return vehicles[i]
        }
      }
      return null
    }

    function card(row) {
      var isTrip = (row.type == 'trip')
      var node = el('div', 'msj-card')
      var badge = el('div', 'msj-icon ' + (isTrip ? 'msj-trip' : 'msj-charge'))
      var pictogram = el('i', isTrip ? 'fas fa-car-side' : U.icon)
      badge.appendChild(pictogram)
      node.appendChild(badge)
      // Avec plusieurs véhicules, chaque fiche montre celui qu'elle concerne : sa photo, à côté du pictogramme coloré
      // (bleu pour un trajet, rouge pour une recharge ou un plein). Sans photo, son nom figure dans la fiche.
      var car = currentVehicle()
      if (car && car.image) {
        var photo = el('div', 'msj-photo')
        var picture = el('img')
        picture.alt = car.short
        picture.title = car.short
        picture.onerror = function() { photo.style.display = 'none' }
        picture.src = car.image
        photo.appendChild(picture)
        node.appendChild(photo)
      }
      var body = el('div', 'msj-card-body')
      var main = el('div', 'msj-card-main')
      // Un plein est un instant, pas une période
      main.appendChild(el('b', null, (!isTrip && U.fuel) ? hour(row.start) : hour(row.start) + ' → ' + (row.ongoing ? '{{en cours}}' : hour(row.end)) + ' · ' + tripDuration(row)))
      if (isTrip) {
        main.appendChild(el('div', null, (row.from || EMPTY) + (row.ongoing ? '' : '  →  ' + (row.to || EMPTY))))
      } else {
        main.appendChild(el('div', null, chargePlace(row)))
      }
      main.appendChild(el('div', null, U.level + ' ' + battery(row)))
      if (car && vehicles.length > 1) {
        main.appendChild(el('div', 'msj-note', car.short))
      }
      body.appendChild(main)
      var figures = el('div', 'msj-card-figures')
      if (isTrip) {
        figures.appendChild(el('b', null, show(row.distance, 'km')))
        figures.appendChild(el('div', null, [show(row.speed, 'km/h'), show(row.consumption, U.consumption, 1)].filter(function(t) { return t != EMPTY }).join(' · ')))
        figures.appendChild(el('div', null, row.rangeUsed === null ? '' : '{{Autonomie}} ' + signed(-row.rangeUsed, 'km')))
      } else {
        figures.appendChild(el('b', null, row.energy === null ? signed(row.rangeGained, 'km') : signed(row.energy, U.energy, 1)))
        figures.appendChild(el('div', null, [row.energy === null ? EMPTY : signed(row.rangeGained, 'km'), show(row.power, 'kW', 1), show(row.cost, '€', 2)].filter(function(t) { return t != EMPTY }).join(' · ')))
      }
      body.appendChild(figures)
      node.appendChild(body)
      return node
    }

    function renderDay(result) {
      var now = result.now
      var banner = el('div', 'msj-banner')
      var left = el('div')
      var labels = { parked: '{{Stationnée}}', trip: '{{En déplacement}}', charge: '{{En charge}}' }
      left.appendChild(el('div', 'msj-state', labels[now.state] + (now.since ? ' {{depuis}} ' + since(now.since) : '')))
      left.appendChild(el('div', null, [now.name, now.place].filter(function(t) { return t }).join(' · ')))
      banner.appendChild(left)
      var figures = el('div', 'msj-figures')
      ;[[show(now.range, 'km'), '{{autonomie}}'], [show(now.level, '%'), U.levelLower], [show(now.mileage, 'km'), '{{compteur}}']].forEach(function(figure) {
        var node = el('div')
        node.appendChild(el('b', null, figure[0]))
        node.appendChild(el('div', null, figure[1]))
        figures.appendChild(node)
      })
      banner.appendChild(figures)
      content.appendChild(banner)

      var s = result.summary
      var summary = el('div', 'msj-summary')
      summary.appendChild(summaryRow('parked', 'fas fa-parking', [[duration(s.parkedMinutes), '{{à l\'arrêt}}']]))
      summary.appendChild(summaryRow('trip', 'fas fa-car-side', [
        [duration(s.tripMinutes), s.tripCount + ' {{trajet(s)}}'],
        [show(s.distance, 'km'), s.speed === null ? '' : show(s.speed, 'km/h')],
        [show(s.consumption, U.consumption, 1), '{{consommation}}']
      ]))
      summary.appendChild(summaryRow('charge', U.icon, [
        U.fuel ? [String(s.chargeCount), U.refills] : [duration(s.chargeMinutes), s.chargeCount + ' ' + U.refills],
        [signed(s.rangeGained, 'km'), '{{autonomie}}'],
        [signed(s.energyGained, U.energy, 1), s.cost === null ? '' : show(s.cost, '€', 2)]
      ]))
      content.appendChild(summary)

      // Frise de 0 h à 24 h : arrêt en fond, trajets en bleu, recharges en rouge, partie à venir éclaircie
      var dayStart = parse(result.date + ' 00:00:00').getTime()
      var position = function(text) { return Math.max(0, Math.min(100, (parse(text).getTime() - dayStart) / 864000)) }
      if (result.levelCurve.length > 0) {
        // La batterie garde sa valeur jusqu'au relevé suivant : tracé en marches
        var points = []
        result.levelCurve.forEach(function(point, index) {
          if (index > 0) {
            points.push((position(point[0]) * 10) + ',' + (100 - result.levelCurve[index - 1][1]))
          }
          points.push((position(point[0]) * 10) + ',' + (100 - point[1]))
        })
        var lastPoint = result.levelCurve[result.levelCurve.length - 1]
        points.push((position(result.end) * 10) + ',' + (100 - lastPoint[1]))
        var first = points[0].split(',')[0]
        var last = points[points.length - 1].split(',')[0]
        var NS = 'http://www.w3.org/2000/svg'
        var svg = document.createElementNS(NS, 'svg')
        svg.setAttribute('viewBox', '0 0 1000 100')
        svg.setAttribute('preserveAspectRatio', 'none')
        svg.setAttribute('class', 'msj-curve')
        var area = document.createElementNS(NS, 'polygon')
        area.setAttribute('points', points.join(' ') + ' ' + last + ',100 ' + first + ',100')
        var line = document.createElementNS(NS, 'polyline')
        line.setAttribute('points', points.join(' '))
        svg.appendChild(area)
        svg.appendChild(line)
        var levels = result.levelCurve.map(function(point) { return point[1] })
        content.appendChild(el('div', 'msj-caption', U.level + ' : ' + show(Math.min.apply(null, levels), '%') + ' {{à}} ' + show(Math.max.apply(null, levels), '%')))
        content.appendChild(svg)
      }
      var timeline = el('div', 'msj-timeline')
      result.charges.concat(result.trips).forEach(function(row) {
        var segment = el('div', row.type == 'trip' ? 'msj-trip' : 'msj-charge')
        segment.style.left = position(row.start) + '%'
        segment.style.width = (position(row.end) - position(row.start)) + '%'
        segment.title = hour(row.start) + ' → ' + hour(row.end)
        timeline.appendChild(segment)
      })
      if (position(result.end) < 99.9) {
        var future = el('div', 'msj-future')
        future.style.left = position(result.end) + '%'
        future.style.right = '0'
        timeline.appendChild(future)
      }
      content.appendChild(timeline)
      var hours = el('div', 'msj-hours')
      for (var h = 0; h <= 24; h += 2) {
        hours.appendChild(el('span', null, h + ' h'))
      }
      content.appendChild(hours)

      var rows = result.trips.concat(result.charges).sort(function(a, b) { return a.start < b.start ? 1 : -1 })
      if (rows.length == 0) {
        content.appendChild(el('div', 'msj-message', '{{Rien ce jour-là.}}'))
      }
      rows.forEach(function(row) { content.appendChild(card(row)) })
      content.appendChild(note(result))
    }

    // ----- Onglet Véhicule : la tuile de chaque voiture, telle que Jeedom la sert, avec ses boutons
    function loadTile(id, holder) {
      fetch('core/ajax/eqLogic.ajax.php', { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ action: 'toHtml', id: id, version: version }) })
        .then(function(response) { return response.json() })
        .then(function(answer) {
          if (answer.state != 'ok') {
            throw new Error(String(answer.result).replace(/<[^>]+>/g, ''))
          }
          holder.innerHTML = answer.result.html
          // Un script inséré par innerHTML n'est pas exécuté : on le recrée pour que les boutons de la tuile fonctionnent
          Array.prototype.forEach.call(holder.querySelectorAll('script'), function(old) {
            var script = document.createElement('script')
            script.textContent = old.textContent
            old.parentNode.replaceChild(script, old)
          })
        })
        .catch(function(error) { holder.textContent = error.message })
    }

    function renderCars() {
      var tiles = el('div', 'msj-tiles')
      content.appendChild(tiles)
      var holders = vehicles.filter(function(item) { return String(item.id) == vehicle.value }).map(function(item) {
        var holder = el('div')
        tiles.appendChild(holder)
        loadTile(item.id, holder)
        return [item.id, holder]
      })
      // La tuile est relue chaque minute tant que l'onglet est affiché
      state.timer = setInterval(function() {
        if (!document.body.contains(tiles)) {
          clearInterval(state.timer)
          return
        }
        holders.forEach(function(pair) { loadTile(pair[0], pair[1]) })
      }, 60000)
    }

    function loadLeaflet(done) {
      if (window.L && window.L.map) {
        done()
        return
      }
      var css = el('link')
      css.rel = 'stylesheet'
      css.href = 'plugins/myskoda/3rdparty/leaflet/leaflet.css'
      document.head.appendChild(css)
      var script = el('script')
      script.src = 'plugins/myskoda/3rdparty/leaflet/leaflet.js'
      script.onload = done
      script.onerror = function() { fail('{{La bibliothèque de cartes n\'a pas pu être chargée.}}') }
      document.head.appendChild(script)
    }

    function renderMap(map, day) {
      // Sans trajet ce jour-là, la carte n'a aucun trait à montrer : le dire, pour qu'on ne croie pas à un défaut
      var drawable = day.trips.filter(function(trip) { return trip.fromPosition && trip.toPosition }).length
      if (state.jumped) {
        state.jumped = false
        content.appendChild(el('div', 'msj-message', '{{Aucun trajet aujourd\'hui : la carte montre le dernier jour où la voiture a roulé. Le bouton « Aujourd\'hui » y revient.}}'))
      }
      var loops = day.trips.filter(function(trip) { return trip.roundTrip })
      if (loops.length > 0) {
        content.appendChild(el('div', 'msj-message', '{{Aller-retour}} : ' + loops.map(function(trip) {
          return hour(trip.start) + ' → ' + hour(trip.end) + ' (' + show(trip.distance, 'km') + ')'
        }).join(', ') + '. {{La voiture est revenue à son point de départ : la carte l\'indique par un cercle bleu autour du lieu, faute de connaître le parcours.}}'))
      }
      if (drawable == 0) {
        content.appendChild(el('div', 'msj-message', day.trips.length == 0
          ? '{{Aucun trajet ce jour-là. Les points gris sont les lieux où la voiture a stationné les jours précédents, pas des trajets du jour. Changez de jour avec la flèche pour voir les trajets d\'un autre jour.}}'
          : '{{Les trajets de ce jour n\'ont pas de départ ou d\'arrivée localisé : ils ne peuvent pas être tracés.}}'))
      }
      var holder = el('div', 'msj-map')
      content.appendChild(holder)
      content.appendChild(el('div', 'msj-note', '{{Points gris : tous les lieux où la voiture a déjà stationné, quel que soit le jour. Points de couleur : vos favoris. Point vert cerclé de blanc : sa position actuelle. Traits bleus : trajets du jour choisi seulement, tracés à vol d\'oiseau entre le départ et l\'arrivée, car Škoda ne fournit pas le parcours. Cercle bleu en pointillés : aller-retour revenu à son point de départ. Points rouges : recharges ou pleins du jour choisi.}}'))
      loadLeaflet(function() {
        var chart = L.map(holder)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>' }).addTo(chart)
        var bounds = []
        map.places.forEach(function(place) {
          // Un favori garde sur cette carte la couleur que l'utilisateur lui a donnée
          var style = place.favorite
            ? { radius: 8, color: place.color || map.defaultColor, weight: 2, fillColor: place.color || map.defaultColor, fillOpacity: 1 }
            : { radius: 7, color: '#6f7a80', fillOpacity: 0.8 }
          L.circleMarker([place.lat, place.lng], style).addTo(chart)
            .bindPopup(el('span', null, (place.name ? place.name + ' — ' : '') + place.address))
          bounds.push([place.lat, place.lng])
        })
        day.trips.forEach(function(trip) {
          if (trip.fromPosition && trip.toPosition && trip.roundTrip) {
            // Aller-retour revenu à son point de départ : pas de trait possible, un cercle bleu autour du lieu
            L.circleMarker(trip.fromPosition, { radius: 18, color: '#2a7ab9', weight: 4, dashArray: '8 8', fill: false }).addTo(chart)
              .bindPopup(el('span', null, '{{Aller-retour}} · ' + hour(trip.start) + ' → ' + hour(trip.end) + ' · ' + show(trip.distance, 'km')))
          } else if (trip.fromPosition && trip.toPosition) {
            L.polyline([trip.fromPosition, trip.toPosition], { color: '#2a7ab9', weight: 4, dashArray: '8 8' }).addTo(chart)
              .bindPopup(el('span', null, hour(trip.start) + ' → ' + hour(trip.end) + ' · ' + show(trip.distance, 'km')))
          }
        })
        day.charges.forEach(function(charge) {
          if (charge.position) {
            L.circleMarker(charge.position, { radius: 11, color: '#d9534f', fillOpacity: 0.5 }).addTo(chart)
              .bindPopup(el('span', null, hour(charge.start) + ' → ' + hour(charge.end) + ' · ' + battery(charge)))
          }
        })
        if (map.now.position && map.now.state != 'trip') {
          L.circleMarker(map.now.position, { radius: 9, color: '#fff', weight: 3, fillColor: map.now.state == 'charge' ? '#d9534f' : '#5cb85c', fillOpacity: 1 }).addTo(chart)
            .bindPopup(el('span', null, map.now.name + (map.now.place ? ' — ' + map.now.place : '')))
          bounds.push(map.now.position)
        }
        // Les trajets comptent dans le cadrage : un départ ou une arrivée hors des lieux mémorisés doit rester visible
        day.trips.forEach(function(trip) {
          if (trip.fromPosition && trip.toPosition) {
            bounds.push(trip.fromPosition)
            bounds.push(trip.toPosition)
          }
        })
        var frame = function() {
          if (bounds.length > 0) {
            chart.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 })
          } else {
            chart.setView([46.6, 2.4], 5)
          }
        }
        frame()
        // Sur mobile, la page peut ne pas avoir encore sa taille définitive quand la carte est créée : la carte
        // se croit alors minuscule et ne dessine pas les traits situés hors de cette zone. Elle est remesurée et
        // recadrée une fois la page en place, puis à chaque changement de taille (rotation du téléphone).
        var remeasure = function() {
          if (document.body.contains(holder)) {
            chart.invalidateSize()
            frame()
          }
        }
        setTimeout(remeasure, 300)
        setTimeout(remeasure, 1200)
        if (window.ResizeObserver) {
          new ResizeObserver(function() {
            if (document.body.contains(holder)) {
              chart.invalidateSize()
            }
          }).observe(holder)
        }
      })

      // Sous la carte, le détail de ce qu'elle montre : les trajets et les recharges ou pleins du jour choisi
      var rows = day.trips.concat(day.charges).sort(function(a, b) { return a.start < b.start ? 1 : -1 })
      content.appendChild(el('h4', null, '{{Trajets}} {{et}} ' + U.refillTab.toLowerCase() + ' {{de ce jour}}'))
      if (rows.length == 0) {
        content.appendChild(el('div', 'msj-message', '{{Rien ce jour-là.}}'))
      }
      rows.forEach(function(row) { content.appendChild(card(row)) })

      // La gestion des lieux (noms, favoris, adresses) a sa propre page
      var manage = el('span', 'msj-btn')
      manage.appendChild(el('i', 'fas fa-map-marker-alt'))
      manage.appendChild(document.createTextNode(' {{Gérer les lieux et les favoris}}'))
      manage.addEventListener('click', function() {
        state.tab = 'places'
        refresh()
      })
      content.appendChild(manage)
    }

    // Page « Lieux » : carte des lieux, ajout d'un favori par son adresse ou par un clic sur la carte, liste à gérer
    function renderPlaces(map) {
      // La page est redessinée après chaque modification. Sans précaution elle se vide un instant et le navigateur
      // remonte tout en haut : la hauteur est donc tenue pendant le changement, et la position de lecture remise.
      var reload = function(text) {
        request({ action: 'getMap', id: vehicle.value, energy: state.energy }, function(fresh) {
          var positions = []
          for (var node = content; node; node = node.parentElement) {
            if (node.scrollTop > 0) {
              positions.push([node, node.scrollTop])
            }
          }
          var pageY = window.pageYOffset
          content.style.minHeight = content.offsetHeight + 'px'
          content.innerHTML = ''
          renderPlaces(fresh)
          message.textContent = text
          positions.forEach(function(saved) { saved[0].scrollTop = saved[1] })
          window.scrollTo(0, pageY)
          content.style.minHeight = ''
        }, fail)
      }
      var refuse = function(text) {
        message.textContent = text
        message.scrollIntoView({ block: 'center' })
      }
      content.appendChild(el('div', 'msj-note', '{{Les lieux où la voiture se gare sont mémorisés automatiquement. Ici, vous leur donnez un nom, vous corrigez leur adresse, et vous créez vos lieux favoris (Maison, Travail, École…) : tout stationnement fait dans le rayon d\'un favori lui est rattaché, même si la position transmise par la voiture s\'écarte un peu.}}'))

      var holder = el('div', 'msj-map')
      content.appendChild(holder)
      var chart = null
      var pending = null
      var marker = null
      var setPoint = function(lat, lng, zoom) {
        pending = [lat, lng]
        if (!chart) {
          return
        }
        if (marker) {
          marker.remove()
        }
        marker = L.circleMarker(pending, { radius: 10, color: '#d9534f', weight: 3, fillColor: '#d9534f', fillOpacity: 0.6 }).addTo(chart)
        if (zoom) {
          chart.setView(pending, 17)
        }
        if (editing === null) {
          pointText.textContent = '{{Point placé. Cliquez ailleurs sur la carte pour le déplacer.}}'
          add.style.opacity = '1'
        }
      }
      // Lieu existant en cours de retouche : son point passe en rouge, un clic sur la carte le déplace, puis on enregistre
      var editing = null
      var editBar = el('div', 'msj-place')
      editBar.style.display = 'none'
      var startEdit = function(index) {
        if (!map.canEdit || !chart) {
          return
        }
        editing = index
        var place = map.places[index]
        setPoint(place.lat, place.lng, true)
        editBar.innerHTML = ''
        editBar.style.display = ''
        editBar.appendChild(el('b', null, (place.name || place.address || '{{Lieu}}')))
        editBar.appendChild(el('span', null, '{{Le point rouge est l\'emplacement enregistré. S\'il est mal placé, cliquez sur la carte pour le déplacer. Puis validez : l\'adresse de ce point vous sera proposée.}}'))
        var keep = el('span', 'msj-btn')
        keep.appendChild(el('i', 'fas fa-check'))
        keep.appendChild(document.createTextNode(' {{Valider ce point et vérifier son adresse}}'))
        var move = function(address) {
          var query = { action: 'movePlace', id: vehicle.value, energy: state.energy, index: index, lat: pending[0], lng: pending[1] }
          if (address) {
            query.address = address
            query.withAddress = 1
          }
          request(query, function() { reload(address ? '{{Position et adresse enregistrées.}}' : '{{Position enregistrée.}}') }, refuse)
        }
        keep.addEventListener('click', function() {
          // La box demande à OpenStreetMap l'adresse du point et la propose, que le point ait bougé ou non (un point
          // bien placé peut porter une adresse fausse) ; l'adresse affichée n'est remplacée que si l'utilisateur l'accepte
          var moved = !(pending[0] == place.lat && pending[1] == place.lng)
          editBar.innerHTML = ''
          editBar.appendChild(el('span', null, '{{Recherche de l\'adresse de ce point…}}'))
          request({ action: 'findAddress', id: vehicle.value, energy: state.energy, lat: pending[0], lng: pending[1] }, function(found) {
            if (!found || !found.label || found.label == place.address) {
              if (moved) {
                move(null)
              } else {
                reload('{{L\'adresse affichée correspond déjà à ce point.}}')
              }
              return
            }
            editBar.innerHTML = ''
            editBar.appendChild(el('b', null, (place.name || '{{Lieu}}')))
            var text = el('span', null, '{{Adresse de ce point}} : ' + found.label + (place.address ? '. {{Adresse actuelle}} : ' + place.address + '.' : '.'))
            text.style.flex = '1 1 100%'
            editBar.appendChild(text)
            var both = el('span', 'msj-btn')
            both.appendChild(el('i', 'fas fa-check'))
            both.appendChild(document.createTextNode(' {{Enregistrer avec cette adresse}}'))
            both.addEventListener('click', function() { move(found.label) })
            var only = el('span', 'msj-btn', place.address ? '{{Garder l\'adresse actuelle}}' : '{{Enregistrer sans adresse}}')
            only.addEventListener('click', function() {
              if (moved) {
                move(null)
              } else {
                reload('')
              }
            })
            var back = el('span', 'msj-btn', '{{Annuler}}')
            back.addEventListener('click', function() { reload('') })
            ;[both, only, back].forEach(function(node) { editBar.appendChild(node) })
          }, function() {
            // Adresse introuvable ou service muet : la position est enregistrée, l'adresse reste à corriger à la main
            if (moved) {
              move(null)
            } else {
              reload('{{L\'adresse de ce point n\'a pas été trouvée. Vous pouvez la corriger à la main dans la liste.}}')
            }
          })
        })
        var cancel = el('span', 'msj-btn', '{{Annuler}}')
        cancel.addEventListener('click', function() { reload('') })
        editBar.appendChild(keep)
        editBar.appendChild(cancel)
        holder.scrollIntoView({ block: 'start' })
      }
      loadLeaflet(function() {
        chart = L.map(holder)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>' }).addTo(chart)
        var bounds = []
        map.places.forEach(function(place, index) {
          var point
          if (place.favorite) {
            // Favori : son point, et le cercle de son rayon (le cercle laisse passer les clics vers la carte)
            var tint = place.color || map.defaultColor
            L.circle([place.lat, place.lng], { radius: place.radius || map.defaultRadius, color: tint, weight: 2, fillOpacity: 0.12, interactive: false }).addTo(chart)
            point = L.circleMarker([place.lat, place.lng], { radius: 8, color: tint, weight: 2, fillColor: tint, fillOpacity: 1 }).addTo(chart)
          } else {
            point = L.circleMarker([place.lat, place.lng], { radius: 7, color: '#6f7a80', fillOpacity: 0.8 }).addTo(chart)
          }
          point.bindTooltip((place.name ? place.name + ' — ' : '') + place.address)
          // Un clic sur un lieu l'ouvre pour le voir de près et, au besoin, le déplacer
          point.on('click', function(event) {
            L.DomEvent.stopPropagation(event)
            startEdit(index)
          })
          bounds.push([place.lat, place.lng])
        })
        if (map.now.position && map.now.state != 'trip') {
          L.circleMarker(map.now.position, { radius: 9, color: '#fff', weight: 3, fillColor: '#5cb85c', fillOpacity: 1 }).addTo(chart)
            .bindPopup(el('span', null, map.now.name))
          bounds.push(map.now.position)
        }
        var frame = function() {
          if (bounds.length > 0) {
            chart.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 })
          } else {
            chart.setView([46.6, 2.4], 5)
          }
        }
        frame()
        setTimeout(function() {
          if (document.body.contains(holder)) {
            chart.invalidateSize()
            if (!pending) {
              frame()
            }
          }
        }, 300)
        if (map.canEdit) {
          chart.on('click', function(event) {
            setPoint(event.latlng.lat, event.latlng.lng, false)
          })
        }
      })
      content.appendChild(el('div', 'msj-note', '{{Points de couleur entourés d\'un cercle : vos favoris et leur rayon. Points gris : les autres lieux. Point vert cerclé de blanc : la voiture. Cliquez sur un lieu, ici ou dans la liste, pour le voir de près et affiner son emplacement.}}'))
      content.appendChild(editBar)

      // Noms déjà donnés, proposés dans chaque case « Nom » : on choisit dans la liste au lieu de retaper
      var names = el('datalist')
      names.id = 'msj-names-' + vehicle.value
      var known = {}
      map.places.forEach(function(place) {
        if (place.name && !known[place.name]) {
          known[place.name] = true
          names.appendChild(el('option', null, place.name)).value = place.name
        }
      })
      content.appendChild(names)

      var pointText = el('span', null, '')
      var add = el('span', 'msj-btn')
      if (map.canEdit) {
        content.appendChild(el('h4', null, '{{Ajouter un lieu favori}}'))
        var form = el('div', 'msj-place')
        var newName = el('input')
        newName.type = 'text'
        newName.placeholder = '{{Nom (Maison, Travail…)}}'
        newName.setAttribute('list', names.id)
        var newAddress = el('input')
        newAddress.type = 'text'
        newAddress.placeholder = '{{Adresse (numéro, rue, ville)}}'
        newAddress.style.flex = '1 1 280px'
        var search = el('span', 'msj-btn')
        search.appendChild(el('i', 'fas fa-search'))
        search.appendChild(document.createTextNode(' {{Chercher l\'adresse}}'))
        search.title = '{{L\'adresse saisie est envoyée par votre Jeedom au service de recherche d\'OpenStreetMap pour être située sur la carte}}'
        var newRadius = el('input')
        newRadius.type = 'text'
        newRadius.placeholder = '{{Rayon}} ' + map.defaultRadius + ' m'
        newRadius.title = '{{Facultatif : rayon de ce favori en mètres. Vide : le rayon général réglé dans la configuration du plugin}}'
        newRadius.style.flex = '0 0 120px'
        var locate = function() {
          if (newAddress.value.trim() == '') {
            refuse('{{Saisissez une adresse, ou cliquez sur la carte pour placer le point.}}')
            return
          }
          pointText.textContent = '{{Recherche en cours…}}'
          // Recherche faite par la box auprès d'OpenStreetMap, seulement à la demande : le navigateur n'en a pas le droit
          // (règle de sécurité de Jeedom), et rien n'est envoyé sans ce clic
          request({ action: 'searchAddress', id: vehicle.value, energy: state.energy, address: newAddress.value.trim() }, function(found) {
            setPoint(parseFloat(found.lat), parseFloat(found.lng), true)
            pointText.textContent = '{{Adresse trouvée}} : ' + found.label + '. {{Si le point n\'est pas exactement au bon endroit, cliquez sur la carte pour le déplacer.}}'
          }, function(error) {
            pointText.textContent = error
          })
        }
        search.addEventListener('click', locate)
        newAddress.addEventListener('keydown', function(event) {
          if (event.key == 'Enter') {
            locate()
          }
        })
        add.appendChild(el('i', 'fas fa-star'))
        add.appendChild(document.createTextNode(' {{Enregistrer ce favori}}'))
        add.style.opacity = '0.5'
        add.addEventListener('click', function() {
          if (!pending) {
            refuse('{{Placez d\'abord le point : cherchez l\'adresse, ou cliquez sur la carte.}}')
            return
          }
          request({ action: 'addPlace', id: vehicle.value, energy: state.energy, lat: pending[0], lng: pending[1], name: newName.value, address: newAddress.value, radius: newRadius.value },
            function() { reload('{{Favori enregistré.}}') }, refuse)
        })
        ;[newName, newAddress, search, newRadius, add].forEach(function(node) { form.appendChild(node) })
        content.appendChild(form)
        pointText.className = 'msj-note'
        pointText.style.display = 'block'
        pointText.textContent = '{{Cherchez l\'adresse, ou cliquez directement sur la carte à l\'endroit voulu. Le point se corrige d\'un clic. L\'adresse que vous écrivez est celle qui sera affichée.}}'
        content.appendChild(pointText)
        if (map.homeAvailable) {
          var home = el('span', 'msj-btn', '{{Créer le favori « Maison » aux coordonnées du domicile connues de Jeedom}}')
          home.addEventListener('click', function() {
            request({ action: 'addHomePlace', id: vehicle.value, energy: state.energy }, function() { reload('{{Favori « Maison » créé.}}') }, refuse)
          })
          content.appendChild(home)
        }
      }

      // Une ligne de la liste : l'essentiel est visible (ordre, couleur, étoile, nom, adresse, cible), les réglages
      // rarement touchés (prix, rayon, couleur, suppression) se déplient. « rank » et « count » : rang du favori parmi
      // les favoris, pour le glisser-déposer et les flèches.
      var buildRow = function(place, index, rank, count) {
        var row = el('div', 'msj-place')
        var dot = el('span', null, '')
        dot.style.cssText = 'flex:0 0 14px;width:14px;height:14px;border-radius:50%;background:' + (place.favorite ? (place.color || map.defaultColor) : '#6f7a80') + ';'
        if (!map.canEdit) {
          row.appendChild(dot)
          if (place.name) {
            row.appendChild(el('b', null, place.name))
          }
          row.appendChild(el('span', null, place.address))
          return row
        }
        var key = place.lat + ',' + place.lng
        var name = el('input', 'msj-name')
        name.type = 'text'
        name.value = place.name || ''
        name.placeholder = '{{Nom (Maison, Travail…)}}'
        name.title = '{{Nom donné à ce lieu. La liste propose les noms déjà utilisés}}'
        name.setAttribute('list', names.id)
        var address = el('input', 'msj-address')
        address.type = 'text'
        address.value = place.address || ''
        address.placeholder = '{{Adresse affichée}}'
        address.title = '{{Adresse affichée pour ce lieu. Vous pouvez la corriger si celle donnée par Škoda est fausse}}'
        var price = el('input', 'msj-small')
        price.type = 'text'
        price.value = place.price ? String(place.price).replace('.', DECIMAL) : ''
        // Case vide : c'est le prix général de la configuration qui s'applique, il est donc montré en filigrane
        price.placeholder = (map.price > 0) ? show(map.price, U.price, 2) : U.price
        price.title = U.fuel ? '{{Facultatif : prix du litre à cet endroit, si vous y faites le plein. Vide : le prix général de la configuration du plugin}}' : '{{Facultatif : prix du kWh à cet endroit, si vous y rechargez. Vide : le prix général de la configuration du plugin}}'
        var radius = el('input', 'msj-small')
        radius.type = 'text'
        radius.value = place.radius ? String(place.radius) : ''
        radius.placeholder = map.defaultRadius + ' m'
        radius.title = '{{Facultatif : rayon de ce favori en mètres. Vide : le rayon général réglé dans la configuration du plugin}}'
        // Un seul envoi pour toute la ligne ; la page est rechargée quand la carte ou la liste de noms en dépend
        var send = function(reloading, text, favorite) {
          request({ action: 'renamePlace', id: vehicle.value, energy: state.energy, index: index, name: name.value, price: price.value, address: address.value, withAddress: 1, favorite: favorite ? 1 : 0, radius: radius.value }, function(fresh) {
            // Rechargement aussi quand l'enregistrement a retiré un lieu couvert par un favori : les rangs ont changé
            if (reloading || (fresh && fresh.places && fresh.places.length != map.places.length)) {
              reload(text)
            } else {
              message.textContent = text
            }
          }, refuse)
        }
        var isFavorite = !!place.favorite
        name.addEventListener('change', function() { send(true, '{{Lieu enregistré.}}', isFavorite) })
        address.addEventListener('change', function() { send(false, '{{Adresse enregistrée.}}', isFavorite) })
        price.addEventListener('change', function() { send(false, '{{Prix enregistré.}}', isFavorite) })
        radius.addEventListener('change', function() { send(true, '{{Rayon enregistré.}}', isFavorite) })

        // Étoile : jaune pour un favori, grise sinon ; un clic bascule
        var star = el('span', 'msj-star' + (isFavorite ? ' msj-star-on' : ''))
        star.appendChild(el('i', isFavorite ? 'fas fa-star' : 'far fa-star'))
        star.title = isFavorite ? '{{Favori : cliquez pour le retirer des favoris}}' : '{{Cliquez pour en faire un favori : tous les stationnements faits dans son rayon lui seront rattachés}}'
        star.addEventListener('click', function() {
          send(true, isFavorite ? '{{Ce lieu n\'est plus un favori.}}' : '{{Favori enregistré.}}', !isFavorite)
        })

        var locate = el('span', 'msj-btn')
        locate.appendChild(el('i', 'fas fa-crosshairs'))
        locate.title = '{{Voir ce lieu sur la carte et affiner son emplacement}}'
        locate.style.cssText = 'padding:2px 9px;'
        locate.addEventListener('click', function() { startEdit(index) })

        // Réglages repliés ; un lieu qu'on vient de régler reste ouvert après le rechargement de la page
        var more = el('div', 'msj-place-more')
        more.style.display = state.openPlaces[key] ? '' : 'none'
        var toggle = el('span', 'msj-btn msj-toggle')
        var chevron = el('i', state.openPlaces[key] ? 'fas fa-chevron-up' : 'fas fa-chevron-down')
        toggle.appendChild(chevron)
        toggle.title = '{{Réglages de ce lieu : prix, rayon, couleur, suppression}}'
        toggle.style.cssText = 'padding:2px 9px;'
        toggle.addEventListener('click', function() {
          state.openPlaces[key] = !state.openPlaces[key]
          more.style.display = state.openPlaces[key] ? '' : 'none'
          chevron.className = state.openPlaces[key] ? 'fas fa-chevron-up' : 'fas fa-chevron-down'
        })
        var field = function(label, node) {
          var wrap = el('label', 'msj-field')
          wrap.appendChild(el('span', null, label))
          wrap.appendChild(node)
          return wrap
        }
        more.appendChild(field(U.fuel ? '{{Prix du litre ici}}' : '{{Prix du kWh ici}}', price))

        var head = [dot, star, name, address, locate, toggle]
        if (isFavorite) {
          more.appendChild(field('{{Rayon}}', radius))
          // Couleur du favori, reprise sur les cartes
          var color = el('select')
          color.title = '{{Couleur de ce favori sur les cartes}}'
          map.colors.forEach(function(choice) {
            var option = el('option', null, choice[1])
            option.value = choice[0]
            option.selected = ((place.color || map.defaultColor) == choice[0])
            color.appendChild(option)
          })
          color.addEventListener('change', function() {
            request({ action: 'colorPlace', id: vehicle.value, energy: state.energy, index: index, color: color.value }, function() { reload('{{Couleur enregistrée.}}') }, refuse)
          })
          more.appendChild(field('{{Couleur}}', color))
          // Ordre des favoris : flèches, en complément du glisser-déposer
          ;[['fas fa-arrow-up', -1, '{{Monter ce favori dans la liste}}', rank > 0], ['fas fa-arrow-down', 1, '{{Descendre ce favori dans la liste}}', rank < count - 1]].forEach(function(arrow) {
            var move = el('span', 'msj-btn')
            move.appendChild(el('i', arrow[0]))
            move.title = arrow[2]
            // Une flèche sans objet (premier ou dernier favori) garde sa place, invisible, pour que les colonnes restent alignées
            move.style.cssText = 'padding:2px 9px;' + (arrow[3] ? '' : 'visibility:hidden;')
            move.addEventListener('click', function() {
              request({ action: 'reorderPlace', id: vehicle.value, energy: state.energy, index: index, direction: arrow[1] }, function() { reload('') }, refuse)
            })
            more.appendChild(move)
          })

          // Poignée à traits horizontaux : on attrape le favori et on le glisse à sa place, à la souris comme au doigt
          row.setAttribute('data-rank', rank)
          var grip = el('span', null, '')
          grip.appendChild(el('i', 'fas fa-grip-lines'))
          grip.title = '{{Glisser pour changer l\'ordre des favoris}}'
          grip.style.cssText = 'flex:0 0 auto;padding:4px 6px;cursor:grab;touch-action:none;opacity:.7;font-size:16px;' + (count > 1 ? '' : 'visibility:hidden;')
          grip.addEventListener('pointerdown', function(start) {
            start.preventDefault()
            var target = rank
            var rows = function() { return Array.prototype.slice.call(content.querySelectorAll('.msj-place[data-rank]')) }
            var clear = function() { rows().forEach(function(other) { other.style.boxShadow = '' }) }
            try {
              grip.setPointerCapture(start.pointerId)
            } catch (error) {}
            row.style.opacity = '0.45'
            grip.style.cursor = 'grabbing'
            var move = function(event) {
              var under = document.elementFromPoint(event.clientX, event.clientY)
              var over = (under && under.closest) ? under.closest('.msj-place[data-rank]') : null
              clear()
              if (over) {
                target = parseInt(over.getAttribute('data-rank'), 10)
                // Trait bleu à l'endroit où le favori sera déposé
                if (target != rank) {
                  over.style.boxShadow = (target > rank) ? '0 3px 0 0 #2a7ab9' : '0 -3px 0 0 #2a7ab9'
                }
              }
            }
            var drop = function() {
              grip.removeEventListener('pointermove', move)
              grip.removeEventListener('pointerup', drop)
              grip.removeEventListener('pointercancel', drop)
              clear()
              row.style.opacity = ''
              grip.style.cursor = 'grab'
              if (target != rank) {
                request({ action: 'rankPlace', id: vehicle.value, energy: state.energy, index: index, rank: target }, function() { reload('') }, refuse)
              }
            }
            grip.addEventListener('pointermove', move)
            grip.addEventListener('pointerup', drop)
            grip.addEventListener('pointercancel', drop)
          })
          head.unshift(grip)
        }
        var remove = el('span', 'msj-btn')
        remove.appendChild(el('i', 'fas fa-trash'))
        remove.appendChild(document.createTextNode(' {{Supprimer ce lieu}}'))
        remove.style.cssText = 'padding:2px 9px;margin-left:auto;'
        remove.addEventListener('click', function() {
          if (window.confirm('{{Supprimer ce lieu ? Les trajets déjà enregistrés ne sont pas modifiés.}}')) {
            request({ action: 'deletePlace', id: vehicle.value, energy: state.energy, index: index }, function() { reload('{{Lieu supprimé.}}') }, refuse)
          }
        })
        more.appendChild(remove)
        head.forEach(function(node) { row.appendChild(node) })
        row.appendChild(more)
        if (place.coveredBy) {
          // Lieu ordinaire situé dans le rayon d'un favori : gardé parce qu'il porte une saisie, mais il ne sert plus
          var covered = el('div', 'msj-note', '{{Couvert par le favori}} « ' + place.coveredBy + ' » : {{les stationnements faits ici sont rattachés à ce favori. Supprimez ce lieu, ou réduisez le rayon du favori.}}')
          covered.style.flex = '1 1 100%'
          row.appendChild(covered)
        }
        return row
      }


      // Les favoris d'abord, dans l'ordre choisi ; les autres arrêts de la voiture à part, les plus récents en tête
      var favorites = []
      var others = []
      map.places.forEach(function(place, index) {
        (place.favorite ? favorites : others).push([place, index])
      })
      others.reverse()
      content.appendChild(el('h4', null, '{{Mes favoris}}'))
      if (favorites.length == 0) {
        content.appendChild(el('div', 'msj-message', '{{Aucun favori pour le moment. Ajoutez-en un ci-dessus, ou donnez un nom à un lieu de la liste ci-dessous puis cliquez sur son étoile.}}'))
      }
      favorites.forEach(function(item, rank) {
        content.appendChild(buildRow(item[0], item[1], rank, favorites.length))
      })
      content.appendChild(el('h4', null, '{{Autres lieux où la voiture s\'est arrêtée}}'))
      if (others.length == 0) {
        content.appendChild(el('div', 'msj-message', '{{Aucun autre lieu pour le moment : ils s\'ajoutent à chaque stationnement hors d\'un favori.}}'))
      }
      // Au-delà de quelques lignes, le reste est replié pour que la page reste lisible avec un long historique
      var SHOWN = 6
      var hidden = el('div')
      hidden.style.display = 'none'
      others.forEach(function(item, rank) {
        (rank < SHOWN ? content : hidden).appendChild(buildRow(item[0], item[1], 0, 0))
      })
      if (others.length > SHOWN) {
        var more = el('span', 'msj-btn', '{{Afficher les}} ' + (others.length - SHOWN) + ' {{lieux plus anciens}}')
        more.style.marginTop = '8px'
        more.addEventListener('click', function() {
          hidden.style.display = ''
          more.style.display = 'none'
        })
        content.appendChild(hidden)
        content.appendChild(more)
      }

      if (map.canEdit) {
        content.appendChild(el('div', 'msj-note', '{{Le nom d\'un lieu remplace son adresse dans le journal, sur la tuile et dans la commande « Lieu », utilisable dans les scénarios. Pour donner à un lieu le nom d\'un autre, choisissez-le dans la liste de la case « Nom » : deux lieux de même nom s\'affichent sous ce nom et comptent pour un même lieu dans les trajets ; chacun garde son prix, et seul celui marqué d\'une étoile a un rayon. Le rayon général des favoris se règle dans la configuration du plugin ; chaque favori peut avoir le sien, plus large à la campagne ou pour un parking souterrain, plus serré en ville. Le prix saisi sur un lieu s\'applique aux recharges ou aux pleins faits à cet endroit ; ailleurs, c\'est le prix de la configuration du plugin.}}'))
      }
    }

    function table(headers, lines) {
      var wrapper = el('div', 'msj-scroll')
      var node = el('table')
      var head = el('tr', 'msj-head')
      headers.forEach(function(header) {
        head.appendChild(el('th', header[1] || null, header[0]))
      })
      node.appendChild(head)
      lines.forEach(function(line) {
        var tr = el('tr', line.group ? 'msj-group' : null)
        line.cells.forEach(function(cell, index) {
          // Une cellule peut porter son propre intitulé ({ text, label }) quand elle ne contient pas ce qu'annonce sa
          // colonne (le nombre de recharges d'une ligne de total, rangé sous « Batterie »)
          var own = (cell !== null && typeof cell == 'object')
          var td = el('td', headers[index][1] || null, own ? cell.text : cell)
          // Intitulé de la colonne, affiché devant la valeur quand le tableau est présenté en fiches
          td.setAttribute('data-label', own ? cell.label : headers[index][0])
          tr.appendChild(td)
        })
        node.appendChild(tr)
      })
      wrapper.appendChild(node)
      // Le tableau n'est pas encore dans la page : sa largeur se mesure juste après
      setTimeout(fitTables, 0)
      return wrapper
    }

    // Un tableau plus large que l'écran (téléphone, tablette, fenêtre étroite) aurait ses dernières colonnes hors de
    // vue, sans que rien ne le signale (constaté chez un testeur le 2026-10-09). Il est d'abord resserré (intitulés sur
    // deux lignes, marges réduites) ; s'il dépasse encore, chaque ligne devient une fiche : une valeur par ligne,
    // précédée de l'intitulé de sa colonne.
    function fitTables() {
      root.querySelectorAll('.msj-scroll').forEach(function(wrapper) {
        var node = wrapper.querySelector('table')
        // Un tableau masqué (onglet replié) n'a pas de largeur : il sera mesuré quand il sera affiché
        if (!node || wrapper.clientWidth == 0) {
          return
        }
        node.classList.remove('msj-compact', 'msj-stack')
        if (node.scrollWidth <= wrapper.clientWidth + 1) {
          return
        }
        node.classList.add('msj-compact')
        if (node.scrollWidth > wrapper.clientWidth + 1) {
          node.classList.remove('msj-compact')
          node.classList.add('msj-stack')
        }
      })
    }

    // ----- Export vers un tableur (CSV lisible par Excel : point-virgule, virgule décimale, UTF-8 avec marque)
    // Dans une langue à point décimal, le séparateur de colonnes devient la virgule, comme Excel l'attend alors
    function csvCell(value) {
      if (value === null || value === undefined) {
        return ''
      }
      if (typeof value == 'number') {
        return String(value).replace('.', DECIMAL)
      }
      var text = String(value)
      // Un texte commençant par = + - @ serait pris pour une formule
      if (/^[=+\-@]/.test(text)) {
        text = "'" + text
      }
      return '"' + text.replace(/"/g, '""') + '"'
    }

    // units : unité métrique de chaque colonne (null si la colonne n'en a pas), pour l'écrire dans l'unité d'affichage
    function exportCsv(name, headers, lines, units) {
      if (IMPERIAL && units) {
        headers = headers.map(function(header, index) {
          return units[index] ? header.replace('(' + units[index] + ')', '(' + unitOf(units[index]) + ')') : header
        })
        lines = lines.map(function(line) {
          return line.map(function(cell, index) {
            if (!units[index] || typeof cell != 'number') {
              return cell
            }
            var value = convert(cell, units[index])[0]
            return value === null ? null : Math.round(value * 10) / 10
          })
        })
      }
      var csv = '\uFEFF' + [headers].concat(lines).map(function(line) { return line.map(csvCell).join(DECIMAL == ',' ? ';' : ',') }).join('\r\n')
      var link = el('a')
      link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
      link.download = 'myskoda-' + name + '_' + state.start + '_' + state.end + '.csv'
      // Jeedom intercepte sur la page tout clic sur un lien pour charger son adresse comme une page : ce clic-ci ne
      // doit pas lui parvenir, sinon le téléchargement est remplacé par une erreur « Failed to fetch »
      link.addEventListener('click', function(event) { event.stopPropagation() })
      document.body.appendChild(link)
      link.click()
      link.remove()
    }

    function headline(text, exportAction) {
      var line = el('div', 'msj-bar')
      line.appendChild(el('span', 'msj-message', text))
      if (exportAction) {
        var button = el('span', 'msj-btn')
        button.appendChild(el('i', 'fas fa-file-excel'))
        button.appendChild(document.createTextNode(' {{Exporter (Excel)}}'))
        button.addEventListener('click', exportAction)
        button.style.marginLeft = 'auto'
        line.appendChild(button)
      }
      return line
    }

    function consumptionOf(rows) {
      // Calculée sur le total, et seulement si le niveau a baissé d'au moins 5 points en tout : en dessous, le chiffre n'a pas de sens
      var measured = rows.filter(function(row) { return row.energy !== null && row.distance > 0 })
      var drop = sum(measured, 'levelStart') - sum(measured, 'levelEnd')
      return (measured.length > 0 && drop >= 5) ? sum(measured, 'energy') / sum(measured, 'distance') * 100 : null
    }

    function renderTrips(result) {
      var rows = result.trips
      var total = rows.length + ' {{trajet(s)}} · ' + show(sum(rows, 'distance'), 'km') + ' · ' + duration(sum(rows, 'minutes'))
      if (consumptionOf(rows) !== null) {
        total += ' · ' + show(sum(rows, 'energy'), U.energy, 1) + ' · ' + show(consumptionOf(rows), U.consumption, 1)
      }
      content.appendChild(headline(total, rows.length == 0 ? null : function() {
        exportCsv('trajets',
          ['{{Départ}}', '{{Arrivée}}', '{{Lieu de départ}}', '{{Lieu d\'arrivée}}', '{{Durée (min)}}', '{{Distance (km)}}', '{{Vitesse moyenne (km/h)}}', U.level + ' {{au départ (%)}}', U.level + ' {{à l\'arrivée (%)}}', '{{Consommé}} (' + U.energy + ')', '{{Consommation}} (' + U.consumption + ')', '{{Autonomie utilisée (km)}}', '{{Coût estimé (€)}}'],
          rows.map(function(row) {
            return [row.start, row.end, row.from, row.to, row.minutes, row.distance, row.speed, row.levelStart, row.levelEnd, row.energy, row.consumption, row.rangeUsed, row.cost]
          }), [null, null, null, null, null, 'km', 'km/h', null, null, null, U.consumption, 'km', null])
      }))
      content.appendChild(table(
        [['{{Départ}}'], ['{{De}}', 'msj-left msj-wrap'], ['{{À}}', 'msj-left msj-wrap'], ['{{Durée}}'], ['{{Distance}}'], ['{{Vitesse}}'], [U.level], ['{{Consommé}}'], ['{{Consommation}}'], ['{{Autonomie}}'], ['{{Coût estimé}}']],
        rows.map(function(row) {
          return { cells: [dayLabel(row.start) + ' ' + hour(row.start), row.from || EMPTY, row.to || EMPTY, tripDuration(row), show(row.distance, 'km'), show(row.speed, 'km/h'), battery(row), show(row.energy, U.energy, 1), show(row.consumption, U.consumption, 1), row.rangeUsed === null ? EMPTY : signed(-row.rangeUsed, 'km'), show(row.cost, '€', 2)] }
        })
      ))
      content.appendChild(note(result))
    }

    // Colonnes sans objet pour un plein de carburant : durée et puissance
    function refillColumns(list, skipped) {
      return U.fuel ? list.filter(function(item, index) { return skipped.indexOf(index) < 0 }) : list
    }

    // Lieu d'une recharge ; pour une recharge pilotée faite en plusieurs fois, le nombre de reprises et le temps de branchement
    function chargePlace(row) {
      var text = row.place || EMPTY
      if (row.resumes > 1) {
        text += ' · ' + row.resumes + ' {{reprises}}' + (row.pluggedMinutes ? ', {{branchée}} ' + duration(row.pluggedMinutes) : '')
      }
      return text
    }

    function chargeCells(label, place, rows, isGroup) {
      var energy = has(rows, 'energy') ? sum(rows, 'energy') : null
      var minutes = sum(rows, 'minutes')
      var cost = has(rows, 'cost') ? sum(rows, 'cost') : null
      return refillColumns([
        label, place, duration(minutes),
        isGroup ? { text: rows.length + ' ' + U.refills, label: '{{Nombre}}' } : battery(rows[0]),
        show(energy, U.energy, 1),
        (energy !== null && minutes > 0) ? show(energy / (minutes / 60), 'kW', 1) : EMPTY,
        has(rows, 'rangeGained') ? signed(sum(rows, 'rangeGained'), 'km') : EMPTY,
        (cost !== null && energy > 0) ? show(cost / energy, U.price, 2) : (isGroup ? EMPTY : show(rows[0].price, U.price, 2)),
        show(cost, '€', 2),
        isGroup ? '' : show(rows[0].sinceDistance, 'km'),
        isGroup ? '' : show(rows[0].sinceConsumption, U.consumption, 1)
      ], [2, 5])
    }

    function monthsOf(rows) {
      var months = []
      rows.forEach(function(row) {
        var key = row.start.substr(0, 7)
        if (months.length == 0 || months[months.length - 1].key != key) {
          months.push({ key: key, rows: [] })
        }
        months[months.length - 1].rows.push(row)
      })
      return months
    }

    function monthLabel(key) {
      return parse(key + '-01').toLocaleDateString(LOCALE, { month: 'long', year: 'numeric' })
    }

    function renderCharges(result) {
      var rows = result.charges
      var lines = []
      if (rows.length > 0) {
        lines.push({ group: true, cells: chargeCells('{{Total}}', '', rows, true) })
      }
      monthsOf(rows).forEach(function(month) {
        lines.push({ group: true, cells: chargeCells(monthLabel(month.key), '', month.rows, true) })
        month.rows.forEach(function(row) {
          lines.push({ cells: chargeCells(dayLabel(row.start) + ' ' + hour(row.start), chargePlace(row), [row]) })
        })
      })
      content.appendChild(headline(rows.length == 0 ? U.noRefill : rows.length + ' ' + U.refills, rows.length == 0 ? null : function() {
        exportCsv(U.file,
          refillColumns(['{{Début}}', '{{Fin}}', '{{Lieu}}', '{{Durée (min)}}', U.level + ' {{au début (%)}}', U.level + ' {{à la fin (%)}}', '{{Quantité}} (' + U.energy + ')', '{{Puissance moyenne (kW)}}', '{{Puissance maximale (kW)}}', '{{Autonomie gagnée (km)}}', '{{Prix}} (' + U.price + ')', '{{Coût (€)}}', '{{Distance depuis le précédent (km)}}', '{{Consommation depuis le précédent}} (' + U.consumption + ')'], [1, 3, 7, 8]),
          rows.map(function(row) {
            return refillColumns([row.start, row.end, row.place, row.minutes, row.levelStart, row.levelEnd, row.energy, row.power, row.maxPower, row.rangeGained, row.price, row.cost, row.sinceDistance, row.sinceConsumption], [1, 3, 7, 8])
          }), refillColumns([null, null, null, null, null, null, null, null, null, 'km', null, null, 'km', U.consumption], [1, 3, 7, 8]))
      }))
      content.appendChild(table(
        refillColumns([['{{Début}}'], ['{{Lieu}}', 'msj-left msj-wrap'], ['{{Durée}}'], [U.level], ['{{Quantité}}'], ['{{Puissance moyenne}}'], ['{{Autonomie}}'], ['{{Prix}}'], ['{{Coût}}'], ['{{Depuis le précédent}}'], ['{{Consommation}}']], [2, 5]),
        lines
      ))
      content.appendChild(note(result))
    }

    // ----- Statistiques
    function kpi(value, label, detail) {
      var node = el('div', 'msj-kpi')
      node.appendChild(el('b', null, value))
      node.appendChild(el('div', null, label))
      node.appendChild(el('div', 'msj-caption', detail || ''))
      return node
    }

    // Histogramme : une barre par jour ou par mois de la période
    function barChart(title, keys, values, color, unit, decimals) {
      var NS = 'http://www.w3.org/2000/svg'
      // Les barres sont tracées dans l'unité d'affichage : une consommation en miles par kWh s'inverse
      values = values.map(function(value) { return value ? convert(value, unit)[0] : value })
      unit = unitOf(unit)
      var max = Math.max.apply(null, values.map(function(value) { return value || 0 }))
      var box = el('div', 'msj-chart')
      box.appendChild(el('div', 'msj-chart-title', title + (max > 0 ? ' — {{maximum}} ' + format(max, unit, decimals) : '')))
      var svg = document.createElementNS(NS, 'svg')
      svg.setAttribute('viewBox', '0 0 1000 100')
      svg.setAttribute('preserveAspectRatio', 'none')
      svg.setAttribute('class', 'msj-bars')
      var step = 1000 / keys.length
      keys.forEach(function(key, index) {
        var value = values[index]
        if (!value || max <= 0) {
          return
        }
        var height = Math.max(1, value / max * 96)
        var bar = document.createElementNS(NS, 'rect')
        bar.setAttribute('x', index * step + step * 0.12)
        bar.setAttribute('width', step * 0.76)
        bar.setAttribute('y', 100 - height)
        bar.setAttribute('height', height)
        bar.setAttribute('fill', color)
        var tip = document.createElementNS(NS, 'title')
        tip.textContent = bucketLabel(key) + ' : ' + format(value, unit, decimals)
        bar.appendChild(tip)
        svg.appendChild(bar)
      })
      box.appendChild(svg)
      var axis = el('div', 'msj-hours')
      var shown = Math.min(keys.length, 6)
      for (var i = 0; i < shown; i++) {
        axis.appendChild(el('span', null, bucketLabel(keys[Math.round(i * (keys.length - 1) / Math.max(1, shown - 1))])))
      }
      box.appendChild(axis)
      return box
    }

    function bucketLabel(key) {
      return key.length == 7
        ? parse(key + '-01').toLocaleDateString(LOCALE, { month: 'short', year: '2-digit' })
        : parse(key).toLocaleDateString(LOCALE, { day: 'numeric', month: 'short' })
    }

    function renderStats(result) {
      var trips = result.trips
      var charges = result.charges
      var from = parse(state.start)
      var to = parse(state.end)
      var days = Math.max(1, Math.round((to - from) / 86400000) + 1)
      var byMonth = days > 62
      var keys = []
      for (var date = new Date(from); date <= to; byMonth ? date.setMonth(date.getMonth() + 1, 1) : date.setDate(date.getDate() + 1)) {
        keys.push(isoDate(date).substr(0, byMonth ? 7 : 10))
      }
      var inBucket = function(rows, key) {
        return rows.filter(function(row) { return row.start.substr(0, key.length) == key })
      }

      var distance = sum(trips, 'distance')
      var minutes = sum(trips, 'minutes')
      var energyUsed = has(trips, 'energy') ? sum(trips, 'energy') : null
      var tripCost = has(trips, 'cost') ? sum(trips, 'cost') : null
      var energyCharged = has(charges, 'energy') ? sum(charges, 'energy') : null
      var chargeCost = has(charges, 'cost') ? sum(charges, 'cost') : null
      var longest = trips.reduce(function(best, row) { return (row.distance !== null && (best === null || row.distance > best.distance)) ? row : best }, null)

      var grid = el('div', 'msj-kpis')
      grid.appendChild(kpi(show(distance, 'km'), '{{parcourus}}', show(distance / days, 'km', 1) + ' {{par jour en moyenne}}'))
      grid.appendChild(kpi(String(trips.length), '{{trajet(s)}}', duration(minutes) + ' {{au volant}}'))
      grid.appendChild(kpi(minutes > 0 ? show(distance / (minutes / 60), 'km/h') : EMPTY, '{{vitesse moyenne}}', longest ? '{{plus long trajet}} : ' + show(longest.distance, 'km') + ' (' + dayLabel(longest.start) + ')' : ''))
      grid.appendChild(kpi(show(consumptionOf(trips), U.consumption, 1), '{{consommation moyenne}}', energyUsed === null ? '' : show(energyUsed, U.energy, 1) + ' {{consommés}}'))
      grid.appendChild(kpi(show(tripCost, '€', 2), '{{coût estimé des trajets}}', (tripCost !== null && distance > 0) ? show(tripCost / distance * 100 / (IMPERIAL ? MILES : 1), '€', 2) + ' ' + (IMPERIAL ? '{{aux 100 miles}}' : '{{aux 100 km}}') : ''))
      grid.appendChild(kpi(String(charges.length), U.refills, U.fuel ? '' : duration(sum(charges, 'minutes')) + ' {{en charge}}'))
      grid.appendChild(kpi(show(energyCharged, U.energy, 1), U.refilled, has(charges, 'rangeGained') ? signed(sum(charges, 'rangeGained'), 'km') + ' {{d\'autonomie}}' : ''))
      grid.appendChild(kpi(show(chargeCost, '€', 2), U.refillCost, (chargeCost !== null && energyCharged > 0) ? show(chargeCost / energyCharged, U.price, 2) + ' {{en moyenne}}' : ''))
      content.appendChild(grid)

      var per = byMonth ? '{{par mois}}' : '{{par jour}}'
      content.appendChild(barChart('{{Distance}} ' + per, keys, keys.map(function(key) { return sum(inBucket(trips, key), 'distance') }), '#2a7ab9', 'km'))
      content.appendChild(barChart('{{Consommation}} ' + per, keys, keys.map(function(key) { return consumptionOf(inBucket(trips, key)) }), '#5cb85c', U.consumption, 1))
      content.appendChild(barChart(U.refillChart + ' ' + per, keys, keys.map(function(key) { return sum(inBucket(charges, key), 'energy') }), '#d9534f', U.energy, 1))
      content.appendChild(barChart('{{Temps de conduite}} ' + per, keys, keys.map(function(key) { return sum(inBucket(trips, key), 'minutes') }), '#8e7cc3', 'min'))
      content.appendChild(barChart('{{Vitesse moyenne}} ' + per, keys, keys.map(function(key) {
        var rows = inBucket(trips, key)
        var minutes = sum(rows, 'minutes')
        return minutes > 0 ? sum(rows, 'distance') / (minutes / 60) : null
      }), '#f0ad4e', 'km/h'))

      var monthKeys = []
      trips.concat(charges).forEach(function(row) {
        if (monthKeys.indexOf(row.start.substr(0, 7)) < 0) {
          monthKeys.push(row.start.substr(0, 7))
        }
      })
      monthKeys.sort().reverse()
      var monthly = monthKeys.map(function(key) {
        var t = inBucket(trips, key)
        var c = inBucket(charges, key)
        return {
          key: key, trips: t.length, distance: sum(t, 'distance'), minutes: sum(t, 'minutes'),
          energy: has(t, 'energy') ? sum(t, 'energy') : null, consumption: consumptionOf(t), tripCost: has(t, 'cost') ? sum(t, 'cost') : null,
          charges: c.length, charged: has(c, 'energy') ? sum(c, 'energy') : null, chargeCost: has(c, 'cost') ? sum(c, 'cost') : null
        }
      })
      content.appendChild(headline('{{Par mois}}', monthly.length == 0 ? null : function() {
        exportCsv('statistiques',
          ['{{Mois}}', '{{Trajets}}', '{{Distance (km)}}', '{{Durée (min)}}', '{{Consommé}} (' + U.energy + ')', '{{Consommation}} (' + U.consumption + ')', '{{Coût estimé des trajets (€)}}', U.refillTab, U.refillChart + ' (' + U.energy + ')', '{{Coût (€)}}'],
          monthly.map(function(m) {
            return [m.key, m.trips, m.distance, m.minutes, m.energy === null ? null : Math.round(m.energy * 10) / 10, m.consumption === null ? null : Math.round(m.consumption * 10) / 10, m.tripCost === null ? null : Math.round(m.tripCost * 100) / 100, m.charges, m.charged === null ? null : Math.round(m.charged * 10) / 10, m.chargeCost === null ? null : Math.round(m.chargeCost * 100) / 100]
          }), [null, null, 'km', null, null, U.consumption, null, null, null, null])
      }))
      content.appendChild(table(
        [['{{Mois}}'], ['{{Trajets}}'], ['{{Distance}}'], ['{{Durée}}'], ['{{Consommé}}'], ['{{Consommation}}'], ['{{Coût estimé}}'], [U.refillTab], ['{{Ajouté}}'], ['{{Coût}}']],
        monthly.map(function(m) {
          return { cells: [monthLabel(m.key), String(m.trips), show(m.distance, 'km'), duration(m.minutes), show(m.energy, U.energy, 1), show(m.consumption, U.consumption, 1), show(m.tripCost, '€', 2), String(m.charges), show(m.charged, U.energy, 1), show(m.chargeCost, '€', 2)] }
        })
      ))
      content.appendChild(note(result))
    }

    function refresh() {
      Object.keys(tabs).forEach(function(name) {
        tabs[name].classList.toggle('msj-on', name == state.tab)
      })
      var byDay = (state.tab == 'day' || state.tab == 'map')
      dayBar.style.display = byDay ? '' : 'none'
      periodBar.style.display = (byDay || state.tab == 'car' || state.tab == 'places') ? 'none' : ''
      clearInterval(state.timer)
      var energies = []
      vehicleBar.innerHTML = ''
      vehicles.forEach(function(item) {
        var selected = (String(item.id) == vehicle.value)
        if (selected) {
          // Un hybride rechargeable a deux énergies : le journal en montre une à la fois, au choix
          energies = item.energies || [item.kind]
          if (energies.indexOf(state.energy) < 0) {
            state.energy = energies[0]
          }
          U = WORDS[state.energy] || WORDS.electric
        }
        if (vehicles.length < 2) {
          return
        }
        var button = el('span', 'msj-btn' + (selected ? ' msj-on' : ''))
        button.appendChild(el('i', item.kind == 'fuel' ? 'fas fa-gas-pump' : 'fas fa-charging-station'))
        button.appendChild(document.createTextNode(' ' + item.name))
        button.addEventListener('click', function() {
          vehicle.value = item.id
          refresh()
        })
        vehicleBar.appendChild(button)
      })
      if (energies.length > 1) {
        ;[['electric', 'fas fa-bolt', '{{Vue électrique}}'], ['fuel', 'fas fa-gas-pump', '{{Vue carburant}}']].forEach(function(choice) {
          var button = el('span', 'msj-btn' + (state.energy == choice[0] ? ' msj-on' : ''))
          button.appendChild(el('i', choice[1]))
          button.appendChild(document.createTextNode(' ' + choice[2]))
          button.addEventListener('click', function() {
            state.energy = choice[0]
            refresh()
          })
          if (choice[0] == 'electric') {
            button.style.marginLeft = 'auto'
          }
          vehicleBar.appendChild(button)
        })
      }
      tabs.charges.textContent = U.refillTab
      dayInput.value = state.day
      startInput.value = state.start
      endInput.value = state.end
      next.style.visibility = (state.day >= isoDate(new Date())) ? 'hidden' : ''
      if (!vehicle.value) {
        return
      }
      if (state.tab == 'car') {
        state.asked = 'car'
        message.textContent = ''
        content.innerHTML = ''
        renderCars()
        return
      }
      var asked = state.tab + vehicle.value + state.energy + state.day + state.start + state.end
      state.asked = asked
      if (state.tab == 'places') {
        request({ action: 'getMap', id: vehicle.value, energy: state.energy }, function(map) {
          if (state.asked != asked) {
            return
          }
          message.textContent = ''
          content.innerHTML = ''
          renderPlaces(map)
        }, fail)
        return
      }
      var render = { day: renderDay, trips: renderTrips, charges: renderCharges, stats: renderStats }[state.tab]
      var query = (state.tab == 'day' || state.tab == 'map')
        ? { action: 'getDay', id: vehicle.value, energy: state.energy, date: state.day }
        : { action: 'getPeriod', id: vehicle.value, energy: state.energy, start: state.start, end: state.end }
      request(query, function(result) {
        // Une réponse arrivée après un autre choix de l'utilisateur est ignorée
        if (state.asked != asked) {
          return
        }
        if (state.tab == 'map') {
          request({ action: 'getMap', id: vehicle.value, energy: state.energy }, function(map) {
            if (state.asked != asked) {
              return
            }
            // Tant que l'utilisateur n'a pas choisi lui-même un jour, la carte s'ouvre sur le dernier jour où ce
            // véhicule a roulé : un jour sans trajet n'aurait aucun trait à montrer
            if (!state.dayPicked && state.jumpedFor != vehicle.value) {
              state.jumpedFor = vehicle.value
              if (result.trips.length == 0 && map.lastTripDay && map.lastTripDay != state.day) {
                state.day = map.lastTripDay
                state.jumped = true
                refresh()
                return
              }
            }
            message.textContent = ''
            content.innerHTML = ''
            warnHistory(result)
            renderMap(map, result)
          }, fail)
          return
        }
        message.textContent = ''
        content.innerHTML = ''
        warnHistory(result)
        render(result)
      }, fail)
    }

    // Version du plugin, au bout de la barre des onglets : c'est elle qu'un utilisateur indique quand il signale un problème
    request({ action: 'getVersion' }, function(version) {
      var tag = el('span', null, 'v' + version)
      tag.title = '{{Version du plugin MySkoda}}'
      tag.style.cssText = 'align-self:center;font-size:11px;opacity:0.6;white-space:nowrap;margin-left:' + (bar.querySelector('a.msj-btn') ? '8px' : 'auto') + ';'
      bar.appendChild(tag)
    }, function() {})

    // Version mobile : Jeedom n'y a aucune page de réglage. Un administrateur y trouve donc, en bas du journal, deux
    // boutons vers les pages du plugin dans la version ordinateur, seul endroit où ajouter un véhicule ou changer un réglage.
    if (version == 'mobile') {
      request({ action: 'isAdmin' }, function(admin) {
        if (!admin) {
          return
        }
        var settings = el('div', 'msj-bar')
        settings.style.marginTop = '18px'
        ;[['index.php?v=d&m=myskoda&p=myskoda', 'fas fa-car', '{{Véhicules}}'], ['index.php?v=d&p=plugin&id=myskoda', 'fas fa-wrench', '{{Configuration}}']].forEach(function(target) {
          var link = el('a', 'msj-btn')
          link.href = target[0]
          link.target = '_blank'
          link.rel = 'noopener'
          link.appendChild(el('i', target[1]))
          link.appendChild(document.createTextNode(' ' + target[2]))
          settings.appendChild(link)
        })
        root.appendChild(settings)
        root.appendChild(el('div', 'msj-note', '{{Ces deux boutons ouvrent la version ordinateur de Jeedom : c\'est là que l\'on ajoute un véhicule et que l\'on règle le plugin.}}'))
      }, function() {})
    }


    // Écran tourné ou fenêtre redimensionnée : les tableaux sont remesurés. Jeedom change de page sans recharger :
    // l'écoute se retire d'elle-même quand le journal n'est plus dans la page.
    var onResize = function() {
      if (!document.body.contains(root)) {
        window.removeEventListener('resize', onResize)
        return
      }
      fitTables()
    }
    window.addEventListener('resize', onResize)

    // Les unités d'affichage sont lues avant tout affichage ; en cas d'échec, le journal reste en métrique
    var start = function() {
      request({ action: 'getVehicles' }, function(list) {
        vehicles = list
        vehicles.forEach(function(item) {
          var option = el('option', null, item.name)
          option.value = item.id
          vehicle.appendChild(option)
        })
        if (vehicles.length == 0) {
          fail(version == 'mobile'
            ? '{{Aucun véhicule actif. Un véhicule s\'ajoute depuis la version ordinateur de Jeedom : menu Plugins, Objets connectés, MySkoda, « Ajouter un véhicule ».}}'
            : '{{Aucun véhicule actif. Ajoutez-en un : menu Plugins, Objets connectés, MySkoda, « Ajouter un véhicule ».}}')
        }
        refresh()
      }, fail)
    }
    request({ action: 'getUnits' }, function(units) {
      IMPERIAL = (units == 'imperial')
      start()
    }, start)
  }

  return { mount: mount }
})()
