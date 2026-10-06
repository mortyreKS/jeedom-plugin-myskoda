# Journal des versions du plugin MySkoda (beta)

>**IMPORTANT**
>
>Version beta : le plugin est validé sur un véhicule à essence. La partie électrique (recharges, commandes de charge et de climatisation) et le chauffage auxiliaire n'ont pas encore été essayés sur un véhicule réel. Les retours sont les bienvenus.

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
