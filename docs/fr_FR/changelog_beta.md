# Journal des versions du plugin MySkoda (beta)

>**IMPORTANT**
>
>Version beta. Le plugin tourne sur un véhicule à essence (trajets, carburant, position, entretien) et sur deux véhicules électriques, deux Elroq (batterie, autonomie, points de recharge ; sur l'un des deux, détection de la recharge et commandes de charge et de climatisation). Pas encore essayés sur un véhicule réel : un hybride, le chauffage auxiliaire, la présentation « Carte » d'une électrique (nouveauté de la 0.8, mise au point sur des données d'exemple). Les retours sont les bienvenus.

# 0.82 — 11/10/2026

- Les « profils de charge » s'appellent maintenant « Points de recharge », comme dans l'application MyŠkoda : titre du bloc sur la tuile, ligne « Point actuel », bandeau « Voir tous les points ». Dans la liste, « Minuteries » devient « Heures de départ » et « Plages de charge » devient « Heures de charge préférées ». Merci aux deux testeurs qui l'ont signalé.
- Les quatre commandes d'information correspondantes se nomment « Point de recharge actuel », « Point de recharge : limite », « Point de recharge : charge minimale » et « Point de recharge : prochaine charge » sur un véhicule ajouté à partir de cette version. Sur un véhicule déjà en place, elles gardent leur ancien nom (« Profil de charge… ») : rien ne change dans vos scénarios, et vous pouvez les renommer vous-même.
- Rapport de diagnostic : la ligne « profilsDeCharge » devient « pointsDeRecharge ».
- Journal de bord sur un écran étroit, onglet Recharges : dans les fiches « Total » et de chaque mois, le nombre de recharges était précédé du mot « Batterie ». Il est maintenant précédé de « Nombre ».
- L'affichage des points de recharge est confirmé sur un Elroq, et la version 0.81 tourne sous Debian 11.11 avec PHP 7.4.33. Debian 11 est en fin de vie et la logique reste de passer à Debian 12 au minimum, mais le plugin y fonctionne aujourd'hui.

# 0.81 — 10/10/2026

- Dashboard : en dépliant « Voir tous les profils », la tuile restait à sa hauteur d'origine et la liste était coupée (en présentation « Carte », elle débordait sur la tuile du dessous). La tuile s'agrandit maintenant et le dashboard se range de nouveau. Corrige aussi le bas de la tuile parfois rogné de quelques lignes quand la photo du véhicule se chargeait après l'affichage.

# 0.8 — 10/10/2026

- Nouvelle présentation « Carte », en option sur la page de chaque véhicule (« Présentation de la tuile ») : une carte de largeur fixe au fond de couleur, pensée pour un design. Elle porte tout ce que porte la tuile, demande une confirmation avant toute commande envoyée à la voiture, et sa couleur de fond se choisit (« Couleur de fond de la carte »). La tuile habituelle reste la présentation par défaut : rien ne change si vous ne touchez pas à l'option.
- Profils de charge (électriques et hybrides rechargeables), en lecture seule : la tuile indique le profil du lieu où la voiture est garée et sa prochaine charge, puis, dans une liste à déplier, chaque profil défini dans l'application MyŠkoda avec sa limite, sa charge minimale, ses plages de charge et ses minuteries actives. Quatre nouvelles commandes d'information, utilisables dans un scénario : « Profil de charge du lieu » (vide quand la voiture n'est dans aucun lieu enregistré), sa limite, sa charge minimale et sa prochaine charge. Les profils se modifient toujours dans l'application MyŠkoda. Essayé sur les données d'un Elroq, pas encore sur un véhicule relié au plugin.
- Log au niveau Debug : le nom des profils de charge, souvent celui d'un lieu, n'y est plus écrit.
- Nouvelle rubrique « Diagnostic et support » dans la configuration du plugin : le bouton « Générer un rapport de diagnostic » prépare un texte à joindre quand vous signalez un problème (versions, réglages, état de chaque véhicule, dernières erreurs), à copier ou à télécharger. Il ne contient ni clé, ni code S-PIN, ni numéro de série, ni plaque, ni nom, ni adresse, ni position.
- Journal de bord, tableaux des onglets Trajets, Recharges et Statistiques : sur un écran trop étroit pour le tableau (téléphone, tablette, fenêtre réduite), les dernières colonnes sortaient de l'écran. Le tableau est maintenant resserré, puis, s'il ne tient toujours pas, présenté en fiches, une par trajet ou par recharge, avec toutes ses valeurs.
- Journal de bord sur ordinateur, export pour Excel (onglets Trajets, Recharges, Statistiques) : le téléchargement du fichier pouvait être remplacé par une erreur « Failed to fetch » de Jeedom ; corrigé.
- Plugin désactivé : une page du plugin restée ouverte (journal de bord) affiche désormais « Le plugin MySkoda est désactivé », avec l'endroit où le réactiver, au lieu de rester vide.

# 0.7 — 08/10/2026

- Option « Recharge pilotée » confirmée sur un Elroq rechargé sur surplus solaire : une journée de huit reprises de charge apparaît comme une seule recharge, avec son énergie et sa durée réelle.
- Fiche d'une recharge (onglets Journée et Carte) : la puissance affichée est désormais la puissance moyenne, comme dans le tableau de l'onglet Recharges, et non plus la puissance maximale relevée.
- Tableau des recharges : une recharge dont l'énergie n'a pas pu être mesurée n'affiche plus « 0,0 kW » de puissance moyenne, mais un tiret.

- Onglet « Lieux » : quand vous ouvrez un lieu sur la carte et validez son point, déplacé ou non, le plugin cherche l'adresse de ce point (auprès d'OpenStreetMap, par votre Jeedom) et vous la propose si elle diffère de l'adresse affichée ; vous l'acceptez ou vous gardez l'adresse actuelle. Un lieu bien placé dont l'adresse est fausse se corrige ainsi en deux clics.
- Onglet « Lieux » : un lieu ordinaire qui se trouve dans le rayon d'un favori, et auquel vous n'avez donné ni nom, ni prix, ni adresse, est retiré de la liste : il faisait doublon avec le favori. S'il porte une saisie, il est gardé et signalé « couvert par le favori ».
- Documentation relue ligne par ligne face au plugin et corrigée : où se saisit le prix d'un lieu, ce que coûte réellement chaque commande, signalement d'une clé expirée (après coup, pas à l'avance), saisie du contrôle technique, lieux de même nom ; ajouts sur l'hybride rechargeable, la réserve de quota et les commandes masquées par défaut.
- Documentation illustrée de captures d'écran : tuile d'un électrique et d'un thermique, onglets Journée, Carte, Lieux, Trajets, Recharges et Statistiques.
- Journal de bord : une autonomie inchangée s'affiche « 0 km » et non plus « -0 km ».
- Au-delà de 300 lieux mémorisés, ce sont les lieux ordinaires les plus anciens qui sont retirés, jamais un favori.

# 0.6 — 07/10/2026

- Nouvelle option par véhicule, « Recharge pilotée » (surplus solaire, batterie domestique, heures creuses), décochée par défaut : quand un scénario démarre et arrête la charge, le journal compte une seule recharge par branchement du câble, avec la durée de charge réelle, le nombre de reprises et le temps de branchement, au lieu d'une recharge à chaque reprise.

- Page de l'équipement : la photo du véhicule y est affichée, avec sa désignation, dès qu'il a été lu une première fois.
- Le code S-PIN se saisit désormais sur la page de chaque véhicule, et non plus dans la configuration générale : il est propre au véhicule et à son utilisateur principal. Il reste enregistré chiffré. Un code saisi auparavant dans la configuration générale continue de servir à défaut.
- Version mobile : en bas du journal, un administrateur trouve deux boutons vers la version ordinateur de Jeedom, seul endroit où ajouter un véhicule et régler le plugin.
- Si Škoda ajoute une commande à son API (verrouillage, réveil du véhicule…) et qu'un véhicule l'annonce, le plugin le signale une fois dans le centre de messages, pour qu'elle puisse être prise en charge.
- Configuration : le délai de relecture après une commande devient réglable (20 secondes à 2 minutes, 1 minute par défaut), pour les scénarios qui ont besoin de voir vite l'effet d'une commande.

# 0.5 — 07/10/2026

- **Nouvel onglet « Lieux »** dans le journal de bord, pour gérer les lieux où la voiture se gare :
  - **lieux favoris** (Maison, Travail, École…) : tout stationnement fait dans le rayon d'un favori lui est rattaché, même si la position transmise par la voiture s'écarte un peu. Rayon général dans la configuration du plugin (200 m par défaut), rayon propre à chaque favori si besoin ;
  - **ajout d'un favori** par son adresse (recherche faite par votre Jeedom auprès d'OpenStreetMap, à la demande) ou par un clic sur la carte ; le favori « Maison » peut aussi être créé d'un clic aux coordonnées du domicile connues de Jeedom ;
  - **adresse affichée corrigeable**, et emplacement d'un lieu existant affinable sur la carte ;
  - favoris rangés dans l'ordre choisi (glisser-déposer par la poignée, ou flèches), chacun avec sa couleur, reprise sur les cartes ; étoile jaune pour passer un lieu en favori ;
  - les autres arrêts de la voiture sont listés à part, les plus récents en tête ; un lieu créé par erreur se supprime ;
  - la case « Nom » propose les noms déjà donnés ; deux lieux de même nom comptent pour un seul.
- Onglet « Carte » : sous la carte, le détail des trajets et des recharges ou pleins du jour.
- Fiches des trajets et des recharges : chacune montre la photo du véhicule concerné et, s'il y en a plusieurs, son nom.
- Quota : pendant une recharge, le plugin lit désormais au rythme de l'arrêt (10 minutes) et non plus au rythme rapide, pour laisser des requêtes aux commandes ; le rythme rapide ne dure que les 10 minutes qui suivent le début ou la fin de la charge. La documentation détaille ce que coûte chaque commande.
- Journal de bord ouvert en version ordinateur sur un téléphone : la tuile de l'onglet « Véhicule » ne dépasse plus de l'écran.
- Quand Škoda répond sans aucune donnée du véhicule (« indisponible »), la tuile et le journal de bord l'indiquent, avec l'heure depuis laquelle cela dure. Nouvelle commande info « Données Škoda indisponibles depuis », vide en temps normal, utilisable dans un scénario.


# 0.4 — 06/10/2026

Première lecture réelle d'un véhicule électrique (Elroq) par un testeur le 06/10/2026 : batterie, autonomie, verrouillage, câble, limite de charge et climatisation conformes à l'application MyŠkoda. Recharges et commandes restent à essayer.

Le même jour, sur ce véhicule : recharge détectée dès le branchement, et commandes de charge (démarrer, arrêter) et de climatisation (démarrer, arrêter) exécutées par la voiture.

- Tuile : la consigne de climatisation se règle dans une liste (16 à 30 °C). Elle est mémorisée et utilisée au démarrage suivant ; si la climatisation tourne déjà, elle est relancée à la nouvelle température. Nouvelle commande « Régler la consigne de climatisation », utilisable dans les scénarios.
- Carte : un aller-retour revenu à son point de départ, qui n'a pas de trait à tracer, est signalé par un cercle bleu autour du lieu et par un message.
- Journal de bord : un avertissement nomme les commandes dont l'historisation a été décochée et qui l'empêchent de se remplir.
- Documentation : pourquoi des arrêts courts ne découpent pas un trajet.
- Documentation : l'information « Climatisation sans branchement autorisée » n'existe que si le véhicule la communique.

- Carte : quand le jour courant n'a aucun trajet, la carte s'ouvre sur le dernier jour où la voiture a roulé. Le bouton « Aujourd'hui » y revient.

# 0.3 — 06/10/2026

Version de consolidation, après une relecture complète du code.

- Debian 12 minimum (Debian 11 n'est plus maintenue).
- Un véhicule ne peut plus être porté par deux équipements : le numéro de série en double est refusé, et le bouton « Dupliquer » est retiré.
- Enregistrer un équipement (le renommer, le changer d'objet) ne consomme plus une requête du quota.
- Le log au niveau Debug n'écrit plus le numéro de série, la plaque, l'adresse ni les coordonnées de stationnement : il peut être transmis tel quel.
- Si le serveur de Škoda ne répond pas, le plugin n'attend plus qu'une fois, 15 secondes au plus, au lieu de 30 secondes par véhicule.
- Une donnée inattendue de Škoda pour un véhicule n'empêche plus la lecture des autres.
- Commandes sans contenu (démarrer ou arrêter la charge, par exemple) : envoi corrigé. Toujours pas essayé sur un véhicule réel.
- Entretien : le décompte des jours ne se décale plus d'un jour au changement d'heure.
- Carte : lien vers la licence d'OpenStreetMap ; la carte est remesurée après l'affichage de la page et cadrée aussi sur les trajets du jour (traits de trajet absents sur mobile).
- Carte : un message indique quand le jour choisi n'a aucun trajet à tracer ; mention « OpenStreetMap » remise à une taille normale sur mobile.
- Licence unifiée : GNU GPL version 3 ou ultérieure, même en-tête dans tous les fichiers.
- Page de l'équipement : l'icône de suppression des commandes, sans effet, est retirée.
- Nettoyage : fichiers hérités du modèle de plugin retirés.

# 0.2 — 05/10/2026

- Chauffage auxiliaire (véhicules qui en sont équipés) : commandes de démarrage et d'arrêt, bloc sur la tuile. Škoda exige le code S-PIN pour le démarrer : nouveau champ « Code S-PIN » dans la configuration du plugin, enregistré chiffré, jamais écrit dans les logs. Jamais essayé sur un véhicule réel.
- Préchauffage voiture non branchée : nouvelle case par véhicule, « Autoriser la climatisation sur la batterie, voiture non branchée », décochée par défaut. Décochée, le réglage de la voiture décide.
- Nouvelles informations : « Chauffage auxiliaire actif » et « Climatisation sans branchement autorisée ».
- Le numéro de version du plugin est affiché dans sa configuration et à droite de la barre d'onglets du journal de bord.
- Documentation : comment chauffer la voiture avant de partir.

# 0.1 — 04/10/2026

Première version, écrite pour l'API publique officielle MyŠkoda (version 1.1.0), qui remplace l'ancienne API arrêtée en octobre 2026.

- Connexion par clé API créée dans l'application MyŠkoda ; plus d'identifiant ni de mot de passe dans Jeedom.
- Un équipement par véhicule, identifié par son numéro de série (VIN).
- Informations : batterie, autonomie, état et puissance de charge, temps restant, câble, limite et mode de charge, climatisation, verrouillage, ouvrants, kilométrage, position et adresse de stationnement ; carburant pour les thermiques et hybrides.
- Commandes : rafraîchir, démarrer et arrêter la charge, limite et mode de charge, démarrer et arrêter la climatisation et la ventilation. Seules les commandes annoncées par le véhicule sont créées.
- Tuile dédiée : image du véhicule, états, batterie, charge et climatisation avec leurs boutons.
- Journal de bord, sur ordinateur et sur mobile : résumé du jour, courbe de batterie, frise, trajets et recharges avec leurs lieux, carte des stationnements, récapitulatif des recharges avec le coût.
- Véhicules thermiques : jauge de carburant, consommation en litres, détection des pleins, prix du litre.
- Plusieurs véhicules : choix du véhicule en haut du journal, valable pour tous les onglets.
- Données désactivées sur le véhicule (services en ligne expirés) signalées sur la tuile et dans le centre de messages.
- Suivi d'entretien saisi à la main : révision, vidange, contrôle technique, avec décompte et alerte.
- Statistiques sur une période : chiffres clés, histogrammes, tableau par mois.
- Export des trajets, des recharges et des statistiques vers Excel (CSV).
- Coût des recharges et des trajets : prix du kWh général, et prix propre à un lieu.
- Lieux nommés (Maison, Travail…) et commande « Lieu » pour les scénarios.
- Rythme de lecture adaptatif : plus rapide en déplacement et en charge.
- Respect du quota de 20 requêtes par heure : trois requêtes gardées pour les commandes.
- Clé expirée, refusée ou véhicule inconnu : message dans le centre de messages de Jeedom.
- Ni démon ni dépendance à installer.
- Ajout d'un véhicule par son numéro de série, avec vérification de la clé auprès de Škoda.
- Réglages de l'équipement filtrés selon la motorisation (électrique, thermique, hybride).

Pas encore disponible : modification des profils de charge. Non permis par l'API de Škoda : parcours des trajets sur la carte, suivi en direct, verrouillage à distance.
