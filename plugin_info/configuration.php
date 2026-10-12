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

/* Page de configuration (présentation refaite en 0.84). En tête, l'état d'un coup d'œil (clé, véhicules) et les
   raccourcis ; puis les véhicules, la clé en trois étapes, le rythme de lecture, l'affichage et les coûts, le
   diagnostic. Les champs et leurs clés de configuration n'ont pas changé. Tout ce qui est affiché vient de Jeedom :
   aucune requête à Škoda. */
$mskKey = config::byKey('apikey', 'myskoda') != '';
$mskExpires = '';
$mskVehicles = array();
$mskFresh = 0;
$mskFollowed = 0;
try {
  foreach (eqLogic::byType('myskoda') as $mskEq) {
    $mskValue = function ($_logicalId) use ($mskEq) {
      $cmd = $mskEq->getCmd('info', $_logicalId);
      return is_object($cmd) ? (string) $cmd->execCmd() : '';
    };
    $mskActive = $mskEq->getIsEnable() == 1;
    $mskHasVin = $mskEq->getConfiguration('vin') != '';
    if ($mskExpires === '' && $mskValue('api_key_expires') !== '') {
      $mskExpires = $mskValue('api_key_expires');
    }
    if (!$mskActive) {
      $mskState = 'off';
      $mskText = '{{Désactivé}}';
    } elseif (!$mskHasVin) {
      $mskState = 'off';
      $mskText = '{{Sans numéro de série}}';
    } elseif ($mskValue('services_disabled') !== '') {
      $mskState = 'ko';
      $mskText = '{{Services désactivés chez Škoda}}';
    } elseif ($mskValue('unavailable_since') !== '') {
      $mskState = 'ko';
      $mskText = '{{Données Škoda indisponibles}}';
    } else {
      $mskState = 'ok';
      $mskText = htmlspecialchars($mskValue('data_timestamp'));
    }
    if ($mskActive && $mskHasVin) {
      $mskFollowed++;
      if ($mskState == 'ok') {
        $mskFresh++;
      }
    }
    $mskLevel = '';
    if ($mskValue('battery_level') !== '') {
      $mskLevel = '<i class="fas fa-car-battery"></i> ' . htmlspecialchars($mskValue('battery_level')) . ' %';
    } elseif ($mskValue('fuel_level') !== '') {
      $mskLevel = '<i class="fas fa-gas-pump"></i> ' . htmlspecialchars($mskValue('fuel_level')) . ' %';
    }
    $mskObject = $mskEq->getObject();
    $mskVehicles[] = array(
      'id' => $mskEq->getId(),
      'name' => $mskEq->getName(),
      'object' => is_object($mskObject) ? $mskObject->getName() : '',
      'state' => $mskState,
      'text' => $mskText,
      'level' => $mskLevel,
      'quota' => ($mskActive && $mskHasVin) ? $mskValue('api_quota_remaining') : '',
    );
  }
} catch (Throwable $e) {
}
$mskExpiresTime = ($mskExpires !== '') ? strtotime($mskExpires) : false;
?>
<style>
  .msk-cfg{max-width:1100px;margin:0 auto}
  .msk-cfg-head{display:flex;flex-wrap:wrap;align-items:center;gap:14px;padding:14px 18px;border-radius:14px;background:#554E5E;background:linear-gradient(180deg,#EE6352 0%,#554E5E 62%);color:#fff;margin-bottom:16px}
  .msk-cfg-head img{width:54px;height:auto;flex:0 0 auto}
  .msk-cfg-title{font-size:22px;font-weight:600;line-height:1.1}
  .msk-cfg-title small{display:block;font-size:12px;font-weight:400;opacity:.8;margin-top:4px;color:#fff}
  .msk-cfg-pills{display:flex;flex-wrap:wrap;gap:8px;flex:1 1 300px}
  .msk-cfg-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:99px;font-size:13px;font-weight:600;background:rgba(255,255,255,.14);color:#fff;white-space:nowrap}
  .msk-cfg-pill.ok{background:#2e9e57}
  .msk-cfg-pill.ko{background:#c0392b}
  .msk-cfg-pill.wait{background:#b9770e}
  .msk-cfg-links{display:flex;flex-wrap:wrap;gap:8px}
  .msk-cfg-links a{padding:7px 14px;border-radius:99px;background:#fff;color:#554E5E !important;font-weight:600;font-size:13px;text-decoration:none !important;white-space:nowrap}
  .msk-cfg-links a:hover{filter:brightness(.92)}
  .msk-cfg-block{border:1px solid rgba(127,127,127,.25);border-radius:14px;padding:14px 18px 6px;margin-bottom:16px;background:rgba(127,127,127,.06)}
  .msk-cfg-block h4{margin:0 0 12px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;opacity:.7;font-weight:600}
  .msk-cfg-block h4 i{margin-right:6px}
  .msk-cfg-help{font-size:12px;opacity:.75;margin:0 0 10px}
  .msk-cfg table{width:100%;margin-bottom:10px}
  .msk-cfg th{font-size:11px;letter-spacing:.06em;text-transform:uppercase;opacity:.65;font-weight:600;padding:4px 8px;text-align:left}
  .msk-cfg td{padding:7px 8px;border-top:1px solid rgba(127,127,127,.2);vertical-align:middle}
  .msk-cfg-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:7px;background:#c0392b}
  .msk-cfg-dot.ok{background:#2e9e57}
  .msk-cfg-dot.off{background:rgba(127,127,127,.6)}
  .msk-cfg-steps{list-style:none;margin:0 0 6px;padding:0;counter-reset:mskstep}
  .msk-cfg-steps>li{position:relative;padding:0 0 14px 44px;counter-increment:mskstep;min-height:34px}
  .msk-cfg-steps>li::before{content:counter(mskstep);position:absolute;left:0;top:0;width:30px;height:30px;border-radius:50%;background:#554E5E;color:#fff;font-weight:600;display:flex;align-items:center;justify-content:center}
  .msk-cfg-steps b{display:block;margin-bottom:5px}
  .msk-cfg-line{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
  .msk-cfg-line .form-control{display:inline-block;width:auto;min-width:260px;max-width:420px}
  .msk-cfg details{margin-bottom:10px}
  .msk-cfg summary{cursor:pointer;font-weight:600;margin-bottom:10px}
  .msk-cfg summary{list-style:none}
  .msk-cfg summary::-webkit-details-marker{display:none}
  .msk-cfg summary::before{content:"▸";display:inline-block;width:14px}
  .msk-cfg details[open]>summary::before{content:"▾"}
  .msk-cfg-foot{font-size:12px;opacity:.7;text-align:right;margin:-6px 4px 10px}
</style>
<form class="form-horizontal msk-cfg">
  <fieldset>
    <div class="msk-cfg-head">
      <img src="plugins/myskoda/plugin_info/myskoda_icon.png" alt=""/>
      <div class="msk-cfg-title">MySkoda
        <small>{{Version}} <?php echo htmlspecialchars(myskoda::getVersionLabel()); ?></small>
      </div>
      <div class="msk-cfg-pills">
        <?php if (!$mskKey) { ?>
        <span class="msk-cfg-pill wait"><i class="fas fa-key"></i> {{Clé API à saisir}}</span>
        <?php } elseif ($mskExpiresTime !== false) { ?>
        <span class="msk-cfg-pill <?php echo ($mskExpiresTime < time()) ? 'ko' : (($mskExpiresTime < time() + 30 * 86400) ? 'wait' : 'ok'); ?>"><i class="fas fa-key"></i> <?php echo ($mskExpiresTime < time()) ? '{{Clé API expirée le}}' : '{{Clé API valable jusqu\'au}}'; ?> <?php echo date('d/m/Y', $mskExpiresTime); ?></span>
        <?php } else { ?>
        <span class="msk-cfg-pill ok"><i class="fas fa-key"></i> {{Clé API enregistrée}}</span>
        <?php } ?>
        <span class="msk-cfg-pill <?php echo ($mskFollowed == 0) ? '' : (($mskFresh == $mskFollowed) ? 'ok' : 'wait'); ?>"><i class="fas fa-car"></i> <?php echo $mskFresh . ' / ' . $mskFollowed; ?> {{véhicules à jour}}</span>
      </div>
      <div class="msk-cfg-links">
        <a href="index.php?v=d&m=myskoda&p=panel"><i class="fas fa-book"></i> {{Journal de bord}}</a>
        <a href="index.php?v=d&m=myskoda&p=myskoda"><i class="fas fa-car"></i> {{Véhicules}}</a>
      </div>
    </div>

    <div class="msk-cfg-block">
      <h4><i class="fas fa-car"></i>{{Vos véhicules}}</h4>
      <?php if (count($mskVehicles) == 0) { ?>
      <p class="msk-cfg-help">{{Aucun véhicule pour l'instant. Enregistrez d'abord votre clé API ci-dessous, puis ouvrez la page « Véhicules » pour ajouter le vôtre.}}</p>
      <?php } else { ?>
      <table>
        <thead><tr><th>{{Véhicule}}</th><th>{{Niveau}}</th><th>{{Dernière remontée}}</th><th>{{Requêtes restantes cette heure}}</th></tr></thead>
        <tbody>
        <?php foreach ($mskVehicles as $mskV) { ?>
          <tr>
            <td><span class="msk-cfg-dot <?php echo ($mskV['state'] == 'ko') ? '' : $mskV['state']; ?>"></span><a href="index.php?v=d&m=myskoda&p=myskoda&id=<?php echo intval($mskV['id']); ?>#eqlogictab"><?php echo htmlspecialchars($mskV['name']); ?></a><?php if ($mskV['object'] !== '') { echo ' <span class="msk-cfg-help">(' . htmlspecialchars($mskV['object']) . ')</span>'; } ?></td>
            <td><?php echo $mskV['level']; ?></td>
            <td><?php echo $mskV['text']; ?></td>
            <td><?php echo htmlspecialchars($mskV['quota']); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
      <?php } ?>
    </div>

    <div class="msk-cfg-block">
      <h4><i class="fas fa-key"></i>{{Clé API MyŠkoda}}</h4>
      <p class="msk-cfg-help">{{Le plugin ne demande ni identifiant ni mot de passe Škoda : il utilise une clé que vous créez vous-même dans l'application MyŠkoda.}} <a href="https://public.api.connect.skoda-auto.cz/docs" target="_blank" rel="noopener">{{Documentation de Škoda}}</a></p>
      <ol class="msk-cfg-steps">
        <li>
          <b>{{Créez la clé sur votre téléphone}}</b>
          <span class="msk-cfg-help">{{Application MyŠkoda : Profil, puis Maison intelligente. Cochez-y les véhicules à suivre.}}</span>
        </li>
        <li>
          <b>{{Collez la clé ici}} <sup><i class="fas fa-question-circle tooltips" title="{{Clé créée dans l'application MyŠkoda (Profil, Maison intelligente), en y cochant les véhicules à suivre. Elle expire : sa date de fin est dans la commande « Expiration de la clé »}}"></i></sup></b>
          <div class="msk-cfg-line">
            <input class="configKey form-control inputPassword" data-l1key="apikey" autocomplete="new-password"/>
            <span class="msk-cfg-help" style="margin:0">{{puis enregistrez la configuration (bouton « Sauvegarder » en haut du cadre).}}</span>
          </div>
        </li>
        <li>
          <b>{{Ajoutez votre véhicule}}</b>
          <div class="msk-cfg-line">
            <a class="btn btn-default" href="index.php?v=d&m=myskoda&p=myskoda"><i class="fas fa-car"></i> {{Ouvrir la page « Véhicules »}}</a>
            <span class="msk-cfg-help" style="margin:0">{{Son numéro de série (VIN) suffit : le plugin crée ses commandes à la première lecture.}}</span>
          </div>
        </li>
      </ol>
    <!-- Le code S-PIN se saisit sur la page de chaque véhicule : il est propre au véhicule et à son utilisateur principal -->
    </div>

    <div class="msk-cfg-block">
      <h4><i class="fas fa-sync-alt"></i>{{Lecture des véhicules}}</h4>
      <p class="msk-cfg-help">{{Škoda autorise 20 requêtes par heure et par véhicule, commandes comprises. Ces rythmes partagent ce quota entre les lectures et les commandes.}}</p>
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
      <details>
        <summary>{{Réglages avancés}}</summary>
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
      </details>
    </div>

    <div class="msk-cfg-block">
      <h4><i class="fas fa-book"></i>{{Affichage et coûts}}</h4>
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
        <label class="col-md-4 control-label">{{Monnaie}}
          <sup><i class="fas fa-question-circle tooltips" title="{{Signe affiché à côté des prix et des coûts, dans le journal de bord et ses exports. Aucun montant n'est converti : saisissez vos prix directement dans votre monnaie}}"></i></sup>
        </label>
        <div class="col-md-4">
          <select class="configKey form-control" data-l1key="currency">
            <option value="€">€</option>
            <option value="£">£</option>
            <option value="CHF">CHF</option>
            <option value="$">$</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-4 control-label">{{Prix du kWh}} (<?php echo htmlspecialchars(myskoda::currency()); ?>)
          <sup><i class="fas fa-question-circle tooltips" title="{{Facultatif. Sert au journal de bord pour estimer le coût des trajets et des recharges. Un prix propre à un lieu (borne publique, par exemple) se saisit dans le journal de bord, onglet « Lieux »}}"></i></sup>
        </label>
        <div class="col-md-4">
          <input class="configKey form-control" data-l1key="kwh_price" placeholder="{{par exemple 0,20}}"/>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-4 control-label">{{Prix du litre de carburant}} (<?php echo htmlspecialchars(myskoda::currency()); ?>)
          <sup><i class="fas fa-question-circle tooltips" title="{{Facultatif, pour les véhicules thermiques. Sert au journal de bord pour estimer le coût des trajets et des pleins. Si un véhicule utilise un autre carburant, son propre prix se saisit sur l'équipement}}"></i></sup>
        </label>
        <div class="col-md-4">
          <input class="configKey form-control" data-l1key="fuel_price" placeholder="{{par exemple 1,75}}"/>
        </div>
      </div>
    </div>

    <div class="msk-cfg-block">
      <h4><i class="fas fa-life-ring"></i>{{Diagnostic et support}}</h4>
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
    </div>
    <div class="msk-cfg-foot">{{MySkoda, par mortyre. Licence GPL version 3. Projet indépendant, sans affiliation avec Škoda Auto.}}</div>
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
