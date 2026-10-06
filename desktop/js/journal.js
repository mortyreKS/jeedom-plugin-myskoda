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
    '.msj-curve { display: block; width: 100%; height: 64px; }',
    '.msj-curve polygon { fill: rgba(92,184,92,.3); } .msj-curve polyline { fill: none; stroke: #5cb85c; stroke-width: 2; vector-effect: non-scaling-stroke; }',
    '.msj-caption { font-size: 11px; opacity: .7; }',
    '.msj-map { height: 440px; border-radius: 8px; margin-bottom: 10px; z-index: 0; }',
    '.msj-map .leaflet-control-attribution, .msj-map .leaflet-control-attribution a { font-size: 11px; line-height: 1.4; font-weight: normal; text-shadow: none; }',
    '.msj-place { display: flex; flex-wrap: wrap; gap: 4px 10px; align-items: center; padding: 5px 0; border-bottom: 1px solid rgba(127,127,127,.25); }',
    '.msj-place input { flex: 0 1 200px; height: 30px; padding: 2px 8px; }',
    '.msj-place input.msj-price { flex: 0 1 190px; }',
    '.msj-kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; margin-bottom: 14px; }',
    '.msj-kpi { border-radius: 8px; background: rgba(127,127,127,.14); padding: 10px 14px; }',
    '.msj-kpi b { font-size: 22px; display: block; }',
    '.msj-chart { margin-bottom: 14px; }',
    '.msj-chart-title { font-weight: bold; margin-bottom: 4px; }',
    '.msj-bars { display: block; width: 100%; height: 90px; border-bottom: 1px solid rgba(127,127,127,.5); }',
    '.msj-tiles { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-start; position: relative; }',
    '.msj-tiles .eqLogic-widget { position: relative !important; left: auto !important; top: auto !important; margin: 0 !important; max-width: 100%; }',
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

  function show(value, unit, decimals) {
    if (value === null || value === undefined) {
      return EMPTY
    }
    return Number(value).toLocaleString('fr-FR', { minimumFractionDigits: decimals || 0, maximumFractionDigits: decimals || 0 }) + (unit ? ' ' + unit : '')
  }

  function signed(value, unit, decimals) {
    return (value === null || value === undefined) ? EMPTY : (value > 0 ? '+' : '') + show(value, unit, decimals)
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
    return parse(text).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric', month: 'short' })
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
    var state = { tab: 'car', day: isoDate(new Date()), start: isoDate(new Date(Date.now() - 30 * 86400000)), end: isoDate(new Date()) }
    var bar = el('div', 'msj-bar')
    var vehicle = el('select')
    var tabs = {}
    var dayBar = el('div', 'msj-bar')
    var periodBar = el('div', 'msj-bar')
    var message = el('div', 'msj-message')
    var content = el('div')
    bar.appendChild(vehicle)
    ;[['car', '{{Véhicule}}'], ['day', '{{Journée}}'], ['map', '{{Carte}}'], ['trips', '{{Trajets}}'], ['charges', '{{Recharges}}'], ['stats', '{{Statistiques}}']].forEach(function(tab) {
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

    function card(row) {
      var isTrip = (row.type == 'trip')
      var node = el('div', 'msj-card')
      var badge = el('div', 'msj-icon ' + (isTrip ? 'msj-trip' : 'msj-charge'))
      badge.appendChild(el('i', isTrip ? 'fas fa-car-side' : U.icon))
      node.appendChild(badge)
      var body = el('div', 'msj-card-body')
      var main = el('div', 'msj-card-main')
      // Un plein est un instant, pas une période
      main.appendChild(el('b', null, (!isTrip && U.fuel) ? hour(row.start) : hour(row.start) + ' → ' + (row.ongoing ? '{{en cours}}' : hour(row.end)) + ' · ' + tripDuration(row)))
      if (isTrip) {
        main.appendChild(el('div', null, (row.from || EMPTY) + (row.ongoing ? '' : '  →  ' + (row.to || EMPTY))))
      } else {
        main.appendChild(el('div', null, row.place || EMPTY))
      }
      main.appendChild(el('div', null, U.level + ' ' + battery(row)))
      body.appendChild(main)
      var figures = el('div', 'msj-card-figures')
      if (isTrip) {
        figures.appendChild(el('b', null, show(row.distance, 'km')))
        figures.appendChild(el('div', null, [show(row.speed, 'km/h'), show(row.consumption, U.consumption, 1)].filter(function(t) { return t != EMPTY }).join(' · ')))
        figures.appendChild(el('div', null, row.rangeUsed === null ? '' : '{{Autonomie}} ' + signed(-row.rangeUsed, 'km')))
      } else {
        figures.appendChild(el('b', null, row.energy === null ? signed(row.rangeGained, 'km') : signed(row.energy, U.energy, 1)))
        figures.appendChild(el('div', null, [row.energy === null ? EMPTY : signed(row.rangeGained, 'km'), show(row.maxPower, 'kW', 1), show(row.cost, '€', 2)].filter(function(t) { return t != EMPTY }).join(' · ')))
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
      content.appendChild(el('div', 'msj-note', '{{Points gris : tous les lieux où la voiture a déjà stationné, quel que soit le jour. Point vert : sa position actuelle. Traits bleus : trajets du jour choisi seulement, tracés à vol d\'oiseau entre le départ et l\'arrivée, car Škoda ne fournit pas le parcours. Cercle bleu en pointillés : aller-retour revenu à son point de départ. Points rouges : recharges ou pleins du jour choisi.}}'))
      loadLeaflet(function() {
        var chart = L.map(holder)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>' }).addTo(chart)
        var bounds = []
        map.places.forEach(function(place) {
          L.circleMarker([place.lat, place.lng], { radius: 7, color: '#6f7a80', fillOpacity: 0.8 }).addTo(chart)
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

      content.appendChild(el('h4', null, '{{Lieux de stationnement}}'))
      if (map.canEdit && map.places.length > 0) {
        content.appendChild(el('div', 'msj-note', U.fuel
          ? '{{Les deux cases sont facultatives. Nom : pour voir « Maison » ou « Travail » à la place de l\'adresse. Prix : à remplir seulement pour un lieu où vous faites le plein, s\'il diffère du prix général.}}'
          : '{{Les deux cases sont facultatives. Nom : pour voir « Maison » ou « Travail » à la place de l\'adresse. Prix : à remplir seulement pour un lieu où vous rechargez, s\'il diffère du prix général.}}'))
      }
      if (map.places.length == 0) {
        content.appendChild(el('div', 'msj-message', '{{Aucun lieu connu pour le moment : ils s\'ajoutent à chaque stationnement.}}'))
      }
      map.places.forEach(function(place, index) {
        var row = el('div', 'msj-place')
        if (map.canEdit) {
          var name = el('input')
          name.type = 'text'
          name.value = place.name || ''
          name.placeholder = '{{Nom (Maison, Travail…)}}'
          var price = el('input', 'msj-price')
          price.type = 'text'
          price.value = place.price ? String(place.price).replace('.', ',') : ''
          // Case vide : c'est le prix général de la configuration qui s'applique, il est donc montré en filigrane
          price.placeholder = (map.price > 0) ? show(map.price, U.price, 2) + ' ({{prix général}})' : '{{Prix}} (' + U.price + ')'
          price.title = U.fuel ? '{{Facultatif : prix du litre à cet endroit, si vous y faites le plein}}' : '{{Facultatif : prix du kWh à cet endroit, si vous y rechargez}}'
          name.title = '{{Facultatif : nom donné à ce lieu, par exemple Maison ou Travail}}'
          var save = function() {
            request({ action: 'renamePlace', id: vehicle.value, energy: state.energy, index: index, name: name.value, price: price.value }, function() {
              message.textContent = '{{Lieu enregistré.}}'
            }, fail)
          }
          name.addEventListener('change', save)
          price.addEventListener('change', save)
          row.appendChild(name)
          row.appendChild(price)
        } else if (place.name) {
          row.appendChild(el('b', null, place.name))
        }
        row.appendChild(el('span', null, place.address))
        content.appendChild(row)
      })
      if (map.canEdit && map.places.length > 0) {
        content.appendChild(el('div', 'msj-note', '{{Le nom d\'un lieu remplace son adresse dans le journal, sur la tuile et dans la commande « Lieu », utilisable dans les scénarios. Le prix saisi sur un lieu s\'applique aux recharges ou aux pleins faits à cet endroit ; ailleurs, c\'est le prix de la configuration du plugin.}}'))
      }
    }

    function table(headers, lines) {
      var wrapper = el('div', 'msj-scroll')
      var node = el('table')
      var head = el('tr')
      headers.forEach(function(header) {
        head.appendChild(el('th', header[1] || null, header[0]))
      })
      node.appendChild(head)
      lines.forEach(function(line) {
        var tr = el('tr', line.group ? 'msj-group' : null)
        line.cells.forEach(function(cell, index) {
          tr.appendChild(el('td', headers[index][1] || null, cell))
        })
        node.appendChild(tr)
      })
      wrapper.appendChild(node)
      return wrapper
    }

    // ----- Export vers un tableur (CSV lisible par Excel : point-virgule, virgule décimale, UTF-8 avec marque)
    function csvCell(value) {
      if (value === null || value === undefined) {
        return ''
      }
      if (typeof value == 'number') {
        return String(value).replace('.', ',')
      }
      var text = String(value)
      // Un texte commençant par = + - @ serait pris pour une formule
      if (/^[=+\-@]/.test(text)) {
        text = "'" + text
      }
      return '"' + text.replace(/"/g, '""') + '"'
    }

    function exportCsv(name, headers, lines) {
      var csv = '\uFEFF' + [headers].concat(lines).map(function(line) { return line.map(csvCell).join(';') }).join('\r\n')
      var link = el('a')
      link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
      link.download = 'myskoda-' + name + '_' + state.start + '_' + state.end + '.csv'
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
          ['Départ', 'Arrivée', 'Lieu de départ', 'Lieu d\'arrivée', 'Durée (min)', 'Distance (km)', 'Vitesse moyenne (km/h)', U.level + ' au départ (%)', U.level + ' à l\'arrivée (%)', 'Consommé (' + U.energy + ')', 'Consommation (' + U.consumption + ')', 'Autonomie utilisée (km)', 'Coût estimé (€)'],
          rows.map(function(row) {
            return [row.start, row.end, row.from, row.to, row.minutes, row.distance, row.speed, row.levelStart, row.levelEnd, row.energy, row.consumption, row.rangeUsed, row.cost]
          }))
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

    function chargeCells(label, place, rows, isGroup) {
      var energy = has(rows, 'energy') ? sum(rows, 'energy') : null
      var minutes = sum(rows, 'minutes')
      var cost = has(rows, 'cost') ? sum(rows, 'cost') : null
      return refillColumns([
        label, place, duration(minutes),
        isGroup ? rows.length + ' ' + U.refills : battery(rows[0]),
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
      return parse(key + '-01').toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
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
          lines.push({ cells: chargeCells(dayLabel(row.start) + ' ' + hour(row.start), row.place || EMPTY, [row]) })
        })
      })
      content.appendChild(headline(rows.length == 0 ? U.noRefill : rows.length + ' ' + U.refills, rows.length == 0 ? null : function() {
        exportCsv(U.file,
          refillColumns(['Début', 'Fin', 'Lieu', 'Durée (min)', U.level + ' au début (%)', U.level + ' à la fin (%)', 'Quantité (' + U.energy + ')', 'Puissance moyenne (kW)', 'Puissance maximale (kW)', 'Autonomie gagnée (km)', 'Prix (' + U.price + ')', 'Coût (€)', 'Distance depuis le précédent (km)', 'Consommation depuis le précédent (' + U.consumption + ')'], [1, 3, 7, 8]),
          rows.map(function(row) {
            return refillColumns([row.start, row.end, row.place, row.minutes, row.levelStart, row.levelEnd, row.energy, row.power, row.maxPower, row.rangeGained, row.price, row.cost, row.sinceDistance, row.sinceConsumption], [1, 3, 7, 8])
          }))
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
      var max = Math.max.apply(null, values.map(function(value) { return value || 0 }))
      var box = el('div', 'msj-chart')
      box.appendChild(el('div', 'msj-chart-title', title + (max > 0 ? ' — {{maximum}} ' + show(max, unit, decimals) : '')))
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
        tip.textContent = bucketLabel(key) + ' : ' + show(value, unit, decimals)
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
        ? parse(key + '-01').toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' })
        : parse(key).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
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
      grid.appendChild(kpi(show(tripCost, '€', 2), '{{coût estimé des trajets}}', (tripCost !== null && distance > 0) ? show(tripCost / distance * 100, '€', 2) + ' {{aux 100 km}}' : ''))
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
          ['Mois', 'Trajets', 'Distance (km)', 'Durée (min)', 'Consommé (' + U.energy + ')', 'Consommation (' + U.consumption + ')', 'Coût estimé des trajets (€)', U.refillTab, U.refillChart + ' (' + U.energy + ')', 'Coût (€)'],
          monthly.map(function(m) {
            return [m.key, m.trips, m.distance, m.minutes, m.energy === null ? null : Math.round(m.energy * 10) / 10, m.consumption === null ? null : Math.round(m.consumption * 10) / 10, m.tripCost === null ? null : Math.round(m.tripCost * 100) / 100, m.charges, m.charged === null ? null : Math.round(m.charged * 10) / 10, m.chargeCost === null ? null : Math.round(m.chargeCost * 100) / 100]
          }))
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
      periodBar.style.display = (byDay || state.tab == 'car') ? 'none' : ''
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

    request({ action: 'getVehicles' }, function(list) {
      vehicles = list
      vehicles.forEach(function(item) {
        var option = el('option', null, item.name)
        option.value = item.id
        vehicle.appendChild(option)
      })
      if (vehicles.length == 0) {
        fail('{{Aucun véhicule actif. Ajoutez un équipement dans le plugin MySkoda.}}')
      }
      refresh()
    }, fail)
  }

  return { mount: mount }
})()
