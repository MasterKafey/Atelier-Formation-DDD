# Atelier 3 : fiche de travail

Duree : 45 min en binomes + 20 min de consolidation individuelle. Branche `atelier-3`.

```bash
git checkout atelier-3
composer test            # 9 echecs sur 28
composer deptrac         # 1 violation
```

La branche contient le corrige de l'atelier 2 : personne n'est bloque par l'etape
precedente.

## 1. Nommer les ports (5 min, sur papier)

Completez la phrase « pour ... » pour chaque port de la tranche « commander un e-book ».

| Port | Entrant / Sortant | Interface | Adaptateurs |
|---|---|---|---|
| pour creer une commande | | | |
| pour lister les e-books disponibles | | | |
| pour enregistrer une commande | | | |
| pour determiner un taux de TVA | | | |
| pour envoyer un e-mail de confirmation | | | |

Le depot contient deja toutes les interfaces : l'exercice est de les RETROUVER et de
comprendre pourquoi chacune est la ou elle est.

Il contient aussi, tout ecrit, le cas d'usage **`PayerCommande` / `PayerCommandeService`**. Lisez-le :
il illustre le patron du service applicatif que vous retrouverez partout — charger
l'agregat, appeler UNE methode metier, enregistrer, publier. Le service n'y decide de
rien, c'est `Commande::payer()` qui sait si le paiement est possible. Il vous servira de point
de depart au palier D, ou il apprendra a lire l'heure.

## 2. Les 6 classes a ecrire

| # | Classe | Ce qu'elle doit garantir |
|---|---|---|
| 1 | `Infrastructure\VatApi\FournisseurDeTauxDeTvaAvecVatApi` | La couche anticorruption. Le vocabulaire du fournisseur s'arrete la. |
| 2 | `Infrastructure\InMemory\TauxDeTvaFixe` | Un second adaptateur du meme port : la preuve que l'abstraction en est une. |
| 3 | `Application\ListerEbooksDisponibles\Ebook::depuisDomaine()` | Le prix devient une chaine affichable ici, et nulle part ailleurs. |
| 4 | `Tests\Support\MailerSpy` | Un espion, pas un mock. |
| 5 | `Tests\Support\TestServiceContainer` | Le conteneur ecrit a la main, coeur uniquement. |
| 6 | `Application\EnvoyerEmailDeConfirmation` | **A corriger** : il contient une violation de la regle de dependance. |

## 3. Trouver la violation

```bash
composer deptrac
```

Une violation Application -> Infrastructure. Elle est realiste : c'est l'erreur la plus
courante du « DDD de surface », ou les repertoires sont bien nommes mais la dependance
pointe vers l'exterieur.

Deux symptomes du meme probleme :

- deptrac signale la violation ;
- le test de cas d'usage ne peut pas injecter d'espion a la place du mailer.

La correction tient en une ligne. Trouvez-la avant de lire le corrige.

## 4. Contraintes verifiees automatiquement

- `composer deptrac` sort en succes.
- Le vocabulaire de vatapi.com (`TBE`, `'ebooks'`, `electronic`, `rate_type`,
  `filter_match`) n'apparait nulle part en dehors de `src/Infrastructure/VatApi/`. C'est
  le test `le_vocabulaire_de_vatapi_ne_sort_pas_de_l_infrastructure` qui le verifie.
- Le view model n'expose que des types primitifs, et se serialise en JSON en une etape.
- Le test de cas d'usage s'execute sans base de donnees.

## Criteres de reussite

- [ ] Les cinq ports sont nommes en « pour ... » et associes a une interface
- [ ] Les 9 tests passent
- [ ] `composer deptrac` sort en succes
- [ ] Le gabarit d'affichage n'aurait rien a formater

## Paliers d'approfondissement

**Palier A : test de contrat.** Ecrivez `CommandeRepositoryContractTest` et executez-le
contre les deux implementations. Trois jeux de donnees : commande nouvelle, annulee,
payee. N'appelez que des methodes de l'interface, sinon vous ne prouvez plus qu'elles
sont interchangeables.

**Palier B : erreur metier.** Implementez `UserErrorMessage` et le cas du stock
insuffisant sur les livres papier : exception dans le service applicatif, message de
formulaire dans le controleur, code JSON dans l'API. Rappel de l'entretien avec Camille :
le metier prefere se rattraper plutot que prevenir.

Corrige sur la branche `palier-b-erreur-metier` (`docs/palier-b-erreur-metier.md`). Le
vrai enseignement n'y est pas le mecanisme d'exception, qui est simple, mais le fait que
cette verification ne PEUT PAS etre une garantie : le stock est une fonction impure.

**Palier C : Gherkin.** Convertissez `PasserCommandeTest` en scenario Behat avec sa classe
`Context`. Puis faites tourner le meme scenario contre un second contexte qui emet de
vraies requetes HTTP.

Corrige sur la branche `palier-c-gherkin` (`docs/palier-c-gherkin.md`). Attention : le
depot n'a aucune couche web avant ce palier, il faut donc l'ecrire. C'est le plus long
des quatre paliers, et le plus demonstratif : un port entrant, deux adaptateurs, le meme
fichier de scenarios.

**Palier D : horloge.** Le coeur ne contient aujourd'hui AUCUNE date : c'est pour ca
qu'il est trivialement deterministe. Introduisez `Psr\Clock\ClockInterface` et la regle
« une commande non payee depuis plus de 48 heures ne peut plus etre payee », sans perdre
ce determinisme. Fiche detaillee : `docs/atelier-3/palier-d-horloge.md`.
