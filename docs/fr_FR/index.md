# Plugin MySkoda

Suivi et pilotage d'un véhicule Škoda par l'API publique officielle MyŠkoda.

Prérequis : Jeedom 4.4 ou plus récent, sous Debian 12 ou Debian 13. Debian 11, qui n'est plus maintenue, n'est pas prise en charge. Le plugin n'a ni démon ni dépendance.

## Mise en service

### 1. Créer une clé dans l'application MyŠkoda

Sur le téléphone, dans l'application MyŠkoda : Profil, puis Maison intelligente. Créez une clé et copiez-la.

![Écran Maison intelligente de l'application MyŠkoda](images/cle-api.png)

Cet écran liste vos clés (cinq au plus), permet d'en créer une et affiche le numéro de série du véhicule (VIN), à copier pour l'étape 3. Une clé vaut pour les véhicules choisis à sa création, et elle expire : sa date de fin est indiquée sous son nom.

### 2. Saisir la clé dans Jeedom

Dans la configuration du plugin, collez la clé. Les deux rythmes de lecture (à l'arrêt, et en déplacement ou en charge) peuvent rester tels quels.

### 3. Ajouter le véhicule

Dans le plugin, cliquez sur « Ajouter un véhicule » et saisissez son numéro de série (17 caractères). Le plugin vérifie auprès de Škoda que la clé couvre ce véhicule, puis crée l'équipement au nom du modèle, dans la catégorie Énergie. Il reste à lui choisir un objet parent.

Škoda ne permet pas à un plugin de lister les véhicules d'un compte : c'est pourquoi le numéro de série est demandé, une fois par véhicule.

Les commandes apparaissent à la première lecture, dans la minute qui suit l'enregistrement : seules celles que le véhicule fournit sont créées.

> **À LIRE : le journal de bord démarre vide.**
> **Škoda ne fournit aucun historique aux plugins. Les trajets, les recharges, les pleins et les statistiques sont reconstitués par le plugin à partir de ce qu'il relève lui-même, à compter de son installation. Juste après l'installation, ces onglets sont donc vides : ils se remplissent au fil des trajets. L'historique affiché par l'application MyŠkoda n'est pas repris.**

## Informations

Batterie, autonomie, état et puissance de charge, temps restant, câble branché et verrouillé, limite et mode de charge, climatisation et température de consigne, verrouillage, portes, vitres, coffre, capot, feux, kilométrage, déplacement, position et adresse de stationnement ; carburant et autonomie pour les véhicules thermiques et hybrides.

## Commandes

Rafraîchir, démarrer et arrêter la charge, régler la limite de charge, choisir le mode de charge, démarrer la climatisation (avec ou sans température) et l'arrêter, démarrer et arrêter la ventilation, démarrer et arrêter le chauffage auxiliaire.

Une commande est transmise au véhicule ; son effet se lit à la relecture suivante, une minute plus tard.

**Chauffer la voiture avant de partir.** Sur un véhicule électrique, c'est la commande « Démarrer la climatisation » (ou « Démarrer la climatisation à », avec une température) : elle chauffe ou rafraîchit l'habitacle selon la consigne, et ne demande aucun code. Elle s'utilise dans un scénario, par exemple le matin si la température extérieure est basse. 

**Préchauffage voiture non branchée : c'est vous qui décidez.** Par défaut, le plugin ne demande rien de particulier : si la voiture n'est pas branchée, c'est son propre réglage (dans la voiture ou dans l'application MyŠkoda) qui décide si elle chauffe sur sa batterie ou si elle refuse. La commande info « Climatisation sans branchement autorisée » montre ce réglage. Pour que le plugin demande lui-même le chauffage sur batterie, cochez sur la page de l'équipement la case « Autoriser la climatisation sur la batterie, voiture non branchée ». Elle est décochée par défaut, pour qu'un scénario ne consomme jamais votre autonomie sans que vous l'ayez voulu : chauffer sur la batterie réduit l'autonomie disponible au départ.

**Chauffage auxiliaire et code S-PIN.** Certains véhicules thermiques et hybrides ont un chauffage auxiliaire (chauffage stationnaire). Škoda exige le code S-PIN du compte MyŠkoda pour le démarrer : saisissez-le dans la configuration du plugin, champ « Code S-PIN ». Il est enregistré chiffré dans Jeedom et n'est jamais écrit dans les logs. Les commandes « Démarrer le chauffage auxiliaire » et « Arrêter le chauffage auxiliaire » n'apparaissent que si le véhicule en est équipé ; la durée de chauffe est celle réglée sur le véhicule. C'est la seule commande qui demande ce code.

## Tuile

La tuile montre l'image du véhicule fournie par Škoda, les états (verrouillage, portes, vitres, câble), la batterie et l'autonomie, la charge et la climatisation avec leurs boutons, l'adresse de stationnement.

- Chaque élément suit la case « Afficher » de sa commande : la décocher le retire de la tuile.
- Un thermique affiche une jauge de carburant à la place de la batterie.
- Si l'état du verrouillage et des ouvrants est nettement plus ancien que le reste, ses icônes sont estompées et sa date est indiquée.
- Les détails de la charge (puissance, gain, temps restant, fin prévue) n'apparaissent que pendant une charge.
- La limite de charge se règle directement dans la liste de la tuile.
- Pour revenir à l'affichage standard de Jeedom : configuration avancée de l'équipement, onglet Informations, option « Template de widget ».

## Journal de bord (panneau)

**Le journal démarre vide à l'installation du plugin et se construit au fil des trajets : Škoda ne fournit aucun historique.**

Activez « Afficher le panneau desktop » dans la configuration du plugin : une entrée MySkoda apparaît dans le menu Accueil. « Afficher le panneau mobile » fait de même dans l'application mobile et la version mobile de Jeedom. Le journal est le même sur les deux.

Avec plusieurs véhicules, une rangée de boutons en haut du journal choisit celui que l'on regarde : tous les onglets portent sur ce véhicule. Le journal s'adapte à son énergie : batterie, kWh et recharges pour un électrique ; carburant, litres et pleins pour un thermique.

- **Véhicule** : la tuile de la voiture choisie, avec ses boutons, comme sur le dashboard.
- En haut à droite, pour les administrateurs : raccourcis vers les équipements et vers la configuration du plugin.
- **Journée** : état actuel du véhicule (stationné, en déplacement ou en charge, depuis quand et où), résumé du jour (temps à l'arrêt ; trajets : durée, distance, vitesse moyenne, consommation ; recharges : durée, autonomie et énergie gagnées, coût), courbe de la batterie et frise de 0 h à 24 h, puis une carte par trajet et par recharge avec les lieux. Les flèches passent d'un jour à l'autre.
- **Carte** : lieux de stationnement connus, position actuelle, trajets et recharges du jour choisi. Sous la carte, la liste des lieux permet de leur donner un nom (Maison, Travail…). Le fond de carte vient d'OpenStreetMap : pour l'afficher, votre navigateur interroge les serveurs d'OpenStreetMap, qui voient donc la zone regardée. Rien d'autre n'est transmis, et aucune donnée du véhicule ne leur est envoyée par le plugin.
- **Trajets** : tableau sur une période, avec lieux de départ et d'arrivée.
- **Recharges** (électrique) : tableau sur une période, avec totaux par mois, puissance moyenne, prix et coût.
- **Pleins** (thermique) : un plein est reconnu quand le niveau de carburant remonte d'au moins 5 points entre deux lectures ; tableau avec lieu, quantité, prix et coût.
- **Statistiques** : sur une période (7 jours, 30 jours, 12 mois, année en cours ou dates libres), les chiffres clés (distance, trajets, vitesse et consommation moyennes, énergie consommée et rechargée, coûts), cinq histogrammes par jour ou par mois (distance, consommation, énergie rechargée, temps de conduite, vitesse moyenne) et un tableau par mois.

Les onglets Trajets, Recharges et Statistiques ont un bouton **Exporter (Excel)** : il produit un fichier CSV que Excel et LibreOffice ouvrent directement, avec une ligne par trajet, par recharge ou par mois.

Trajets et recharges sont reconstitués à partir de l'historique Jeedom des commandes « En déplacement » et « En charge ». La voiture envoie ses données à Škoda au départ et à l'arrivée ; le plugin date chaque valeur de l'heure de cet envoi, si bien que les heures des trajets sont exactes quel que soit le rythme de lecture. Un trajet fait en entier entre deux lectures est reconnu à l'avancée du compteur : sa distance est juste, sa durée est indiquée comme inconnue. Ne modifiez pas le mode d'historisation des commandes du plugin.

Les lieux sont les adresses de stationnement données par Škoda, que le plugin mémorise au fil des arrêts. Škoda ne donne aucune position pendant qu'un véhicule roule : sur la carte, un trajet est un trait droit entre son départ et son arrivée, pas son parcours.

Le nom donné à un lieu remplace son adresse dans le journal et sur la tuile. La commande « Lieu » le porte aussi : elle sert de déclencheur ou de condition dans un scénario (par exemple « Lieu » égal à « Maison »).

**Consommation.** Škoda ne donne le niveau de batterie ou de carburant qu'en pourcentage entier. Une consommation n'est donc affichée que si le niveau a baissé d'au moins 5 points : pour un trajet isolé, cela demande un trajet assez long ; pour une journée, un mois ou une période, le calcul porte sur le total (ce qui a été consommé, divisé par la distance), ce qui le rend fiable. Dans l'onglet Recharges ou Pleins, chaque ligne donne aussi la distance parcourue et la consommation depuis la recharge ou le plein précédent : c'est le chiffre le plus juste.

Des valeurs que Škoda ne fournit pas sont à saisir pour obtenir tous les chiffres : sur l'équipement, la **capacité utile de la batterie** (pour les kWh) ou la **contenance du réservoir** (pour les litres) ; et le **prix du kWh** ou **du litre de carburant** (pour les coûts). Le prix se saisit à deux endroits : un prix général dans la configuration du plugin, et, sous la carte, un prix propre à un lieu (votre domicile, une borne publique). Une recharge est comptée au prix du lieu où elle a été faite, à défaut au prix général ; le coût d'un trajet est estimé au prix général.

## Entretien

Škoda ne transmet aux plugins ni les échéances de révision et de vidange, ni le rapport d'état du véhicule. Le plugin propose donc un suivi à saisir à la main, dans la page de l'équipement : révision, vidange et contrôle technique.

Pour chacun, indiquez l'échéance (kilométrage au compteur et date), ou recopiez ce qu'affiche l'application MyŠkoda (Inspecter, Service) sous la forme « dans tant de km » et « dans tant de jours » : le plugin convertit à l'enregistrement. Il décompte ensuite les kilomètres et les jours restants (commandes « Révision : kilomètres restants », etc., utilisables dans les scénarios), les affiche sur la tuile, et prévient dans le centre de messages quand une échéance passe sous le seuil d'alerte (1 000 km ou 30 jours par défaut, réglables). Après un entretien, saisissez la nouvelle échéance.

## Données désactivées sur le véhicule

Škoda peut répondre qu'une partie des données (verrouillage et ouvrants, carburant, kilométrage, position…) existe sur le véhicule mais est désactivée. Le plugin l'affiche sur la tuile, dans la commande « Services désactivés » et dans le centre de messages. Cela ne se règle pas dans Jeedom : il faut vérifier dans l'application MyŠkoda que les services en ligne du véhicule sont actifs (licence en cours de validité), que le consentement au partage des données est donné, et que le véhicule n'est pas en mode privé.

## Signaler un problème

Indiquez la version du plugin (affichée dans sa configuration et à droite des onglets du journal de bord), votre modèle de véhicule, et joignez le log `myskoda` au niveau Debug (configuration du plugin, cadre « Logs et surveillance », puis menu Analyse → Logs). Ce log contient les réponses de Škoda, dont le plugin retire le numéro de série, la plaque, l'adresse et les coordonnées de stationnement avant de les écrire. La clé API et le code S-PIN n'y figurent jamais. Le nom que vous avez donné à l'équipement, lui, y apparaît.

## Limites

- Škoda autorise 20 requêtes par heure et par véhicule, lectures et commandes comprises. Avec les rythmes par défaut (10 minutes à l'arrêt, 5 minutes en déplacement ou en charge), il reste au moins huit requêtes par heure pour les commandes, et le plugin garde toujours les trois dernières pour elles ; les commandes « Requêtes restantes » et « Expiration de la clé » permettent de suivre le quota et la validité de la clé.
- L'API ne réveille pas le véhicule : les valeurs sont celles de sa dernière remontée (commande « Dernière remontée du véhicule »).
- La clé expire : le plugin le signale dans le centre de messages de Jeedom.
- Non proposés par l'API : verrouillage à distance, klaxon et appel de phares, état porte par porte.
