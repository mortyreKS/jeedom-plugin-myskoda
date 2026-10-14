# Fonctions expérimentales : essais à faire

> Page à jour pour la version 0.853 beta du plugin, le 14/10/2026.

Une fonction expérimentale est écrite d'après la documentation de Škoda, mais personne ne l'a encore essayée sur un véhicule : l'auteur du plugin n'a pas de véhicule électrique. Elle a été vérifiée sans voiture, contre un faux serveur Škoda : ce que le plugin envoie, et ses refus. Ce qui reste à voir, c'est ce que fait la voiture. Merci à ceux qui acceptent de l'essayer.

Pour l'activer : configuration du plugin (Plugins → Gestion des plugins → MySkoda), rubrique « Fonctions expérimentales », cocher « Activer les fonctions expérimentales », puis Sauvegarder. Décochée, les commandes déjà créées restent en place mais sont refusées.

## Heures de charge préférées (version 0.85)

Trois commandes par point de recharge, décrites dans la [documentation](index.md), rubrique « Commandes » : activer une plage, la désactiver, changer ses heures. Elles reprennent les « Heures de charge préférées » de l'écran « Points de recharge » de l'application MyŠkoda.

### Préparer

1. Mettre le plugin en version 0.85 ou plus récente, cocher la case (voir plus haut).
2. Passer le log du plugin en Debug : configuration du plugin, cadre « Logs et surveillance », cocher « Debug », Sauvegarder.
3. Sur la page du véhicule, cliquer « Rafraîchir » (ou attendre la lecture suivante, au plus 10 minutes) : les commandes apparaissent dans l'onglet « Commandes ». Leur nom commence par celui du point de recharge, par exemple « Maison - activer une plage ». Elles sont masquées sur la tuile : on s'en sert dans un scénario.
4. Faire une capture de l'écran « Points de recharge » de l'application MyŠkoda, pour chaque point : c'est l'état de départ, qui servira à vérifier que rien d'autre n'a changé.
5. Sur la tuile, déplier « Voir tous les points » : avec la case cochée, les quatre plages de chaque point sont numérotées (n° 1 à n° 4), les inactives en grisé, dans l'ordre de l'application (vérifié sur un Elroq).
6. Vérifier que le mode de charge est « Heures préférées » (dans l'application : « Par heure », « Heures préférées ») : sinon la voiture ne tient pas compte des plages.

Pour lancer une commande : créer un scénario (Outils → Scénarios → Ajouter), ajouter un bloc « Action », choisir la commande, remplir ses valeurs, sauvegarder, puis « Exécuter ». Pour « heures de la plage », le numéro de la plage va dans le champ titre et les heures dans le champ message, sous la forme 22:00-06:00 (ou 2200-0600, au format hhmm des heures de Jeedom).

Chaque modification coûte trois requêtes du quota de l'heure (relecture du point, envoi, relecture une minute après) : espacer les essais, en gardant de quoi lire le véhicule. Après chaque commande, attendre une à deux minutes avant de regarder l'application.

### Essais

1. **Activer puis désactiver une plage existante.** Choisir une plage inactive, l'activer, vérifier dans l'application ; puis la désactiver, vérifier de nouveau.
2. **Changer les heures d'une plage**, par exemple 21:00-07:00, puis une plage qui passe minuit dans l'autre sens si ce n'était pas le cas (par exemple 23:30-05:30). Vérifier dans l'application.
3. **Vérifier que le reste est intact**, en comparant avec la capture du départ : limite de charge, charge minimale, heures de départ, les autres plages du même point, et les autres points de recharge.
4. **Une modification pendant une charge**, si l'occasion se présente : la voiture accepte-t-elle, la charge continue-t-elle ?
5. Facultatif : **deux modifications à la suite** dans un même scénario (deux blocs action), par exemple activer la plage 3 puis changer les heures de la plage 4. La première part tout de suite, la seconde environ deux minutes plus tard (le journal du plugin indique « mise en attente ») ; les deux doivent se retrouver dans l'application.
6. **Changer de mode de charge depuis Jeedom** avec la commande « Choisir le mode de charge » : « Heures préférées », puis « Recharge immédiate ». L'application montre-t-elle le même choix ? (Que « Recharge immédiate » corresponde bien au mode `MANUAL` de Škoda n'est pas vérifié.)

### Ce qu'il faut renvoyer

- Pour chaque essai : ce qui a marché ou non, et le message affiché en cas de refus.
- Les captures de l'application, avant et après.
- Le **rapport de diagnostic** (configuration du plugin, « Diagnostic et support ») et le **log `myskoda`** (menu Analyse → Logs) de la période des essais. Le plugin y retire le numéro de série, la plaque, l'adresse, les coordonnées et le nom des points de recharge ; la clé et le code S-PIN n'y figurent jamais.

Après les essais : remettre le log au niveau par défaut ; la case peut rester cochée.
