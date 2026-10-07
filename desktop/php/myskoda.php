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
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Déclaration des variables obligatoires
$plugin = plugin::byId('myskoda');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<!-- Boutons de gestion du plugin -->
		<div class="eqLogicThumbnailContainer">
			<!-- L'API de Škoda ne sait pas lister les véhicules d'une clé : on ajoute un véhicule par son numéro de série,
			     le plugin le vérifie auprès de Škoda et crée l'équipement au nom du modèle -->
			<div class="cursor logoPrimary" id="bt_myskodaAddVehicle">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter un véhicule}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
		</div>
		<legend><i class="fas fa-table"></i> {{Mes véhicules}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun véhicule. Saisissez la clé API dans la configuration du plugin, puis cliquez sur "Ajouter un véhicule"}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . htmlspecialchars($eqLogic->getImage()) . '" onerror="this.onerror=null;this.src=\'plugins/myskoda/plugin_info/myskoda_icon.png\'"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>
	</div> <!-- /.eqLogicThumbnailDisplay -->

	<!-- Page de présentation de l'équipement -->
	<div class="col-xs-12 eqLogic" style="display: none;">
		<!-- barre de gestion de l'équipement -->
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<!-- Les balises <a></a> sont volontairement fermées à la ligne suivante pour éviter les espaces entre les boutons. Ne pas modifier -->
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
								</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>
		<!-- Onglets -->
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content">
			<!-- Onglet de configuration de l'équipement -->
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<!-- Partie gauche de l'onglet "Equipements" -->
				<!-- Paramètres généraux et spécifiques de l'équipement -->
				<form class="form-horizontal">
					<fieldset>
						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
										<option value="">{{Aucun}}</option>
										<?php
										$options = '';
										foreach ((jeeObject::buildTree(null, false)) as $object) {
											$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
										}
										echo $options;
										?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked>{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked>{{Visible}}</label>
								</div>
							</div>

							<legend><i class="fas fa-cogs"></i> {{Paramètres spécifiques}} <span id="span_myskodaCarType" class="label label-info" style="margin-left:8px;font-size:0.75em;"></span></legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Numéro de série (VIN)}}
									<sup><i class="fas fa-question-circle tooltips" title="{{17 caractères, visibles sur la carte grise (repère E) et dans l'application MyŠkoda}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="vin" maxlength="17" placeholder="{{TMB...}}">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Code S-PIN de ce véhicule}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Facultatif. Code à 4 chiffres de ce véhicule, celui que son utilisateur principal a défini pour lui. Škoda ne l'exige que pour démarrer le chauffage auxiliaire (chauffage stationnaire de certains véhicules thermiques et hybrides). Inutile pour la charge et la climatisation d'un véhicule électrique. Enregistré chiffré, jamais écrit dans les logs}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control inputPassword" data-l1key="configuration" data-l2key="spin" autocomplete="new-password" placeholder="{{seulement pour le chauffage auxiliaire}}">
								</div>
							</div>
							<!-- data-ms-energy : le champ n'est montré que si le véhicule remonte cette énergie (voir printEqLogic) -->
							<div class="form-group" data-ms-energy="electric">
									<label class="col-sm-4 control-label">{{Recharge pilotée}}
										<sup><i class="fas fa-question-circle tooltips" title="{{À cocher si la charge de ce véhicule est démarrée et arrêtée par un scénario : surplus de panneaux solaires, batterie domestique, heures creuses. Le journal de bord compte alors une seule recharge par branchement du câble, toutes reprises comprises, au lieu d'une recharge à chaque reprise. Sans effet sur une recharge ordinaire}}"></i></sup>
									</label>
									<div class="col-sm-6">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="managed_charging">{{Charge pilotée par un scénario (surplus solaire, batterie, heures creuses)}}</label>
									</div>
								</div>
								<div class="form-group" data-ms-energy="electric">
									<label class="col-sm-4 control-label">{{Préchauffage sans branchement}}
										<sup><i class="fas fa-question-circle tooltips" title="{{Décoché (par défaut) : le plugin ne demande rien de particulier, c'est le réglage de la voiture qui décide. Coché : quand le plugin démarre la climatisation, il demande à la voiture de chauffer ou rafraîchir même si elle n'est pas branchée, donc sur sa batterie, ce qui réduit l'autonomie}}"></i></sup>
									</label>
									<div class="col-sm-6">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="climate_without_power">{{Autoriser la climatisation sur la batterie, voiture non branchée}}</label>
									</div>
								</div>
								<div class="form-group" data-ms-energy="electric">
								<label class="col-sm-4 control-label">{{Capacité utile de la batterie (kWh)}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Facultatif. Sert au panneau pour convertir les pourcentages de batterie en kWh : Škoda ne fournit pas cette valeur}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="battery_capacity" placeholder="{{par exemple 77}}">
								</div>
							</div>
							<div class="form-group" data-ms-energy="fuel">
								<label class="col-sm-4 control-label">{{Contenance du réservoir (L)}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Facultatif, pour un véhicule thermique. Sert au journal pour convertir les pourcentages de carburant en litres}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="tank_capacity" placeholder="{{par exemple 50}}">
								</div>
							</div>
							<div class="form-group" data-ms-energy="fuel">
								<label class="col-sm-4 control-label">{{Prix du litre pour ce véhicule (€)}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Facultatif. À remplir si ce véhicule n'utilise pas le carburant du prix général de la configuration du plugin (diesel, E85…)}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="fuel_price" placeholder="{{vide : prix général du plugin}}">
								</div>
							</div>
						</div>

						<!-- Partie droite de l'onglet "Équipement" -->
						<!-- Affiche un champ de commentaire par défaut mais vous pouvez y mettre ce que vous voulez -->
						<div class="col-lg-6">
							<!-- Photo du véhicule fournie par Škoda, affichée dès que le véhicule a été lu une première fois (voir printEqLogic).
							     Elle montre la voiture petite au milieu d'un grand vide : elle est recadrée, comme sur la tuile. -->
							<div id="div_myskodaPhoto" style="display:none;max-width:460px;margin:0 auto 12px;text-align:center;">
								<div style="aspect-ratio:2.3/1;overflow:hidden;border-radius:8px;background:#fff;">
									<img id="img_myskodaPhoto" alt="" style="display:block;width:100%;height:100%;object-fit:cover;transform:scale(1.22);">
								</div>
								<!-- Désignation du véhicule telle que Škoda la donne (commande « Nom ») -->
								<div id="div_myskodaPhotoTitle" style="margin-top:6px;font-weight:bold;font-size:1.1em;"></div>
							</div>
							<legend><i class="fas fa-info"></i> {{Informations}}</legend>
							<div class="form-group">
								<label class="col-sm-4 control-label">{{Description}}</label>
								<div class="col-sm-6">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							</div>

							<legend><i class="fas fa-wrench"></i> {{Entretien (saisie manuelle)}}</legend>
							<div class="form-group">
								<div class="col-sm-12">
									{{Škoda ne transmet pas les échéances d'entretien : recopiez ici celles de l'application MyŠkoda (Inspecter, Service). Le plugin décompte ensuite les kilomètres et les jours restants et vous prévient à l'approche. Saisissez soit l'échéance, soit « dans tant de km / jours » : cette seconde forme est convertie à l'enregistrement.}}
								</div>
							</div>
							<?php
							foreach (array('inspection' => '{{Révision}}', 'oil' => '{{Vidange}}', 'mot' => '{{Contrôle technique}}') as $key => $label) {
								echo '<div class="form-group">';
								echo '<label class="col-sm-3 control-label">' . $label . '</label>';
								echo '<div class="col-sm-9">';
								if ($key != 'mot') {
									echo '<input type="text" class="eqLogicAttr form-control input-sm" style="display:inline-block;width:23%;" data-l1key="configuration" data-l2key="service_' . $key . '_km" placeholder="{{au compteur (km)}}" title="{{Kilométrage auquel l\'entretien est dû}}"> ';
									echo '<input type="text" class="eqLogicAttr form-control input-sm" style="display:inline-block;width:23%;" data-l1key="configuration" data-l2key="service_' . $key . '_in_km" placeholder="{{ou dans … km}}" title="{{Kilomètres restants indiqués par l\'application : convertis en échéance à l\'enregistrement}}"> ';
								}
								echo '<input type="date" class="eqLogicAttr form-control input-sm" style="display:inline-block;width:26%;" data-l1key="configuration" data-l2key="service_' . $key . '_date" title="{{Date à laquelle l\'entretien est dû}}"> ';
								echo '<input type="text" class="eqLogicAttr form-control input-sm" style="display:inline-block;width:23%;" data-l1key="configuration" data-l2key="service_' . $key . '_in_days" placeholder="{{ou dans … jours}}" title="{{Jours restants indiqués par l\'application : convertis en date à l\'enregistrement}}">';
								echo '</div>';
								echo '</div>';
							}
							?>
							<div class="form-group">
								<label class="col-sm-3 control-label">{{Prévenir à moins de}}</label>
								<div class="col-sm-9">
									<input type="text" class="eqLogicAttr form-control input-sm" style="display:inline-block;width:23%;" data-l1key="configuration" data-l2key="service_warn_km" placeholder="{{1000 km}}">
									<input type="text" class="eqLogicAttr form-control input-sm" style="display:inline-block;width:23%;" data-l1key="configuration" data-l2key="service_warn_days" placeholder="{{30 jours}}">
								</div>
							</div>
						</div>
					</fieldset>
				</form>
			</div><!-- /.tabpanel #eqlogictab-->

			<!-- Onglet des commandes de l'équipement -->
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<br>
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div><!-- /.tabpanel #commandtab-->

		</div><!-- /.tab-content -->
	</div><!-- /.eqLogic -->
</div><!-- /.row row-overflow -->

<!-- Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
<?php include_file('desktop', 'myskoda', 'js', 'myskoda'); ?>
<!-- Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
<?php include_file('core', 'plugin.template', 'js'); ?>
