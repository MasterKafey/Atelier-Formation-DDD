# La langue du code

Ce depot code son domaine **en francais**. Ce n'est pas une preference de style,
c'est l'application directe du langage ubiquitaire : le metier de Bookshelf se
parle en francais, donc le modele se lit en francais. Une commande s'appelle
`Commande`, elle se `passer()`, elle se `payer()`, elle se `annuler()`.

L'interet se mesure a l'atelier 1 : l'entretien avec Camille est en francais, et
le code qui en sort n'a besoin d'aucune traduction. Personne ne se demande si
« retirer de la vente » se dit `hide`, `disable`, `unpublish` ou `archive`.

## Ce qui est en francais

| Quoi | Exemple |
|---|---|
| Les classes du domaine et de l'application | `Commande`, `TauxDeTva`, `PasserCommandeService` |
| Les methodes de comportement | `passer()`, `ajouterLigne()`, `totalTtc()` |
| Les proprietes et les variables qui designent du metier | `$identifiantCommande`, `$prixUnitaire` |
| Les evenements | `CommandePassee`, `CommandePayee` |
| Les exceptions et leurs constructeurs nommes | `PaiementImpossible::carDelaiDepasse()` |
| Les cas d'enumeration | `EtatCommande::EnAttente` |
| Les scenarios Gherkin et les noms de tests | `une_commande_payee_ne_peut_plus_etre_annulee` |

## Ce qui reste en anglais, et pourquoi

| Quoi | Exemple | Raison |
|---|---|---|
| Les couches | `Domain`, `Application`, `Infrastructure` | Vocabulaire d'architecture, pas de metier. Ce sont les mots du livre et du cours. |
| Les patterns | `Repository`, `Service`, `Builder`, `Type` | Meme raison : ils nomment une solution technique connue. |
| Les ports techniques | `Mailer`, `EventDispatcher`, `Translator`, `UserErrorMessage` | Ils ne portent aucun concept metier. Envoyer un e-mail n'est pas une regle de gestion. |
| L'infrastructure | `Router`, `EntityManagerFactory`, `CurlVatApiTransport` | Elle parle a des machines, pas au metier. |
| Ce que PHP impose | `->value` d'un enum, `convertToPHPValue()` | Ce sont les mots du langage et de Doctrine. |
| Les contrats de sortie | colonnes SQL, `order.insufficient_stock`, cles du jeu d'essai | Lus par une machine ou par un tiers. Les traduire casse un contrat sans rien apporter. |
| `legacy/` | `OrderController` | C'est l'existant : son anglais approximatif fait partie de l'enonce. |

## La seule regle qui compte vraiment

Ce qui coute cher, ce n'est pas de choisir l'anglais ou le francais : c'est de ne
pas choisir. Un depot ou l'on trouve `Commande` a cote de `OrderLine` oblige a
traduire mentalement a chaque lecture, ce qui est exactement ce que le langage
ubiquitaire cherche a supprimer.

Si vous reprenez ce depot pour votre propre domaine et que vous preferez tout
coder en anglais, faites-le : tenez alors, a cote du code, la table de
correspondance entre les mots du metier et les vos classes, et maintenez-la.
