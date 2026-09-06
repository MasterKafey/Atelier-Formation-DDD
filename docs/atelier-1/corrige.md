# Atelier 1 : une cartographie possible

> **Une** cartographie, pas **la** cartographie. Si votre carte differe, ce n'est pas
> qu'elle est fausse : c'est qu'elle repose sur d'autres hypotheses. Ce qui compte, c'est
> que vous puissiez defendre ou vous avez place la frontiere et pourquoi.

---

## 1. Glossaire

| Terme metier | Definition | Dans le code actuel | Remarque |
|---|---|---|---|
| Titre | Un ouvrage publie au catalogue | `ebooks` | Camille dit « titre », jamais « e-book » |
| Catalogue | L'ensemble des titres **en vente** | `SELECT ... WHERE is_hidden = 0` | Notion absente du code |
| Retirer de la vente | Rendre invisible sans supprimer, historique conserve | `is_hidden = 1` | Verbe metier, pas un booleen |
| Commande | **Homonyme, voir plus bas** | `orders` | Le point le plus important de l'atelier |
| Client | Celui qui achete. Identifie par son e-mail, pas de compte | `email` | Pas d'entite Client aujourd'hui |
| Pays de l'acheteur | Determine le taux de TVA applicable | absent | Donnee metier non stockee |
| Taux de TVA | Depend du pays et de la nature du bien | `vat_rate INT NULL` | Le `NULL` n'a aucun sens metier |
| Payer | Transition d'etat de la commande | `status = 'paid'` | Pose n'importe ou |
| Annuler | Transition d'etat, interdite apres paiement | `status = 'cancelled'` | Aucun controle aujourd'hui |
| Facture | Emise apres paiement, dans les minutes qui suivent | absent | Sous-domaine a part entiere |
| Remboursement | Geste commercial, procedure distincte de l'annulation | absent | Hors perimetre, mais a noter |
| Exemplaire | Unite physique en stock, non reapprovisionnable | absent | N'existe que pour le papier |
| Livraison partielle | Deux factures pour une meme commande | absent | Nait de la contrainte de stock |

## 2. L'homonyme

C'est le coeur de l'atelier. **« Commande » a deux sens.**

| | Contexte **Vente** | Contexte **Facturation** |
|---|---|---|
| Qui parle | Julien, au support ; les clients | Camille, en compta |
| Definition | Ce que le client a valide sur le site | Ce qui est facturable |
| Une commande annulee | existe, avec l'etat « annulee » | **n'existe pas** |
| Une commande partiellement livree | **une** commande | **deux** commandes, car deux factures |
| Comportement | `payer()`, `annuler()`, `ajouterLigne()` | aucun : c'est une source de donnees |
| Etat | oui | non |

Consequence de conception : **deux classes `Commande`**, dans deux namespaces. Ce n'est pas
de la duplication. Une classe unique servant les deux accumulerait les methodes des deux
contextes et deviendrait impossible a modifier sans risquer l'autre usage.

Citation a garder sous la main pour la restitution :

> « Quand il me dit "j'ai regarde la commande 4412", des fois on ne parle pas de la meme
> chose. Enfin, ca depend a qui vous demandez. »

## 3. Sous-domaines

| Sous-domaine | Type | Pourquoi | Decision |
|---|---|---|---|
| Catalogue et vente | **Coeur** | C'est le metier. Prix, disponibilite, commande, annulation. | Construire, et y mettre les meilleurs |
| Facturation | Support | Necessaire, reglemente, non differenciant | Construire simple, ou integrer |
| Gestion de stock (papier) | Support | Recent, marginal en volume, mais porteur de regles | Construire simple |
| **Calcul de TVA** | **Generique** | Probleme resolu par l'industrie, change tout le temps | **Acheter** |
| Envoi d'e-mails | Generique | Symfony Mailer + un fournisseur | Acheter |
| Authentification | Generique | Pas meme necessaire aujourd'hui : pas de compte client | Ne rien faire |

La phrase de l'entretien qui tranche pour la TVA :

> « Franchement, refaire ca nous-memes, ce serait absurde, ca change tout le temps et on
> serait responsables des erreurs. »

Camille a fait le travail de classification a votre place. Il fallait l'entendre.

## 4. Carte des contextes

```
                       amont                    aval
   ┌──────────────────────────┐  CommandePayee  ┌──────────────────────────┐
   │          VENTE           │ ─────────────► │       FACTURATION        │
   │      (coeur)             │                │       (support)          │
   │                          │                │                          │
   │  Commande (avec etat)    │                │  Facture                 │
   │  Titre, Catalogue        │                │  Commande (lecture seule)│
   │  Client                  │                │                          │
   └──────────────────────────┘                └──────────────────────────┘
              │      ▲                                      │
   couche     │      │  conformiste                         │
   anticorrup-│      │  (on subit leur format)              │ conformiste
   tion       ▼      │                                      ▼
   ┌──────────────┐  ┌────────────────────┐      ┌────────────────────┐
   │  API TVA     │  │  Librairie belge   │      │  Serveur SMTP      │
   │ (generique)  │  │  (systeme externe) │      │   (generique)      │
   └──────────────┘  └────────────────────┘      └────────────────────┘
```

**Le sens Vente → Facturation n'est pas negociable.** La commande vient d'abord et
determine ce qu'il y a a facturer. Donc la dependance de code va de Facturation vers
Vente, jamais l'inverse. Concretement : l'abonne `CreateInvoice` vit dans Facturation et
ecoute `CommandePayee`. Vente ignore que Facturation existe. (Jour 2, section 7.3.)

**La librairie belge** est un acteur primaire externe. Aujourd'hui l'integration est
« bricolee par Julien » : c'est du **conformiste** subi. A terme, une couche
anticorruption vaudrait mieux, mais ce n'est pas la priorite.

## 5. Classification en couches

| # | Element | Couche | Justification |
|---|---|---|---|
| 1 | Calcul du montant TTC | **Domaine** | Aucune dependance externe, aucun contexte requis. C'est une regle metier. |
| 2 | Appel a vatapi.com | **Infrastructure** | Regle 1 violee : il faut une connexion et que l'API reponde. |
| 3 | Interface d'enregistrement d'une commande | **Domaine** | Une abstraction qui exprime une intention (`save`), pas une implementation. |
| 4 | Controleur du formulaire | **Infrastructure** | Regle 2 violee : concu pour un contexte web uniquement. |
| 5 | DTO du formulaire vers le metier | **Application** | Objet de donnees primitives, sans dependance ; il porte l'entree d'un cas d'usage. |
| 6 | Abonne qui envoie l'e-mail | **Application** | Le subscriber lui-meme est du code coeur : il delegue a une interface `Mailer`, dont l'implementation, elle, est en Infrastructure. |

Le 6 est celui qui fait debat en restitution, et c'est normal : **l'abonne est du code
coeur, l'envoi effectif ne l'est pas**. C'est exactement la separation que le jour 3
formalisera en ports et adaptateurs.

## 6. Palier : la couche anticorruption TVA

| | |
|---|---|
| Interface | `FournisseurDeTauxDeTva::tauxDeTvaPourEbooksDansLePays(CodePays $c): TauxDeTva` |
| Ou elle vit | `Application\PasserCommande` (code coeur) |
| Vocabulaire a l'interieur | `TauxDeTva`, `CodePays` : le votre |
| Vocabulaire a l'exterieur | `rate_type=TBE`, `filter=ebooks`, `rates.electronic.rate` : le leur |
| Ou passe la frontiere | A l'implementation `FournisseurDeTauxDeTvaAvecVatApi`, dans `Infrastructure\VatApi` |

Test de validation de l'abstraction : **pourriez-vous en ecrire une implementation qui lit
un fichier JSON local, sans que le nom des methodes devienne absurde ?** Oui. C'est donc
une vraie abstraction. Vous l'implementerez a l'atelier 3.
