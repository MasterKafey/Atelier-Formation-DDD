# Bookshelf

> **Langue du code.** Le domaine est ecrit en francais, l'infrastructure en anglais.
> La regle complete et ses exceptions : [`docs/langue-du-code.md`](docs/langue-du-code.md).

Projet fil rouge de la formation **Architecture Symfony & Domain-Driven Design** (3 jours),
Dyosis. Le contexte metier est decrit dans le support de cours, fichier `00-fil-rouge.md`.

## Installation

```bash
composer install
composer test          # ou : make test
```

`composer test` doit passer sur la branche `main`.

> Sous Windows, `make` n'est generalement pas installe. Toutes les cibles du `Makefile`
> ont un equivalent en script composer : `composer test`, `composer test:domain`,
> `composer test:usecase`, `composer deptrac`.

## Les branches

Le depot est une suite lineaire de branches. Chaque atelier a sa branche de depart et sa
branche de corrigé, ce qui permet de reprendre la formation a n'importe quelle etape sans
etre bloque par l'atelier precedent.

| Branche | Contenu | `composer test` | `composer deptrac` |
|---|---|---|---|
| `main` | Socle du projet, code legacy a lire | vert, 1 test | sans objet |
| `atelier-1` | Entretien metier et fiche de travail. Pas de code a ecrire. | vert, 1 test | sans objet |
| `atelier-1-corrige` | Une cartographie possible du domaine | vert, 1 test | sans objet |
| `atelier-2` | 8 classes a ecrire, 17 tests **rouges** | **17 echecs sur 18** | sans objet |
| `atelier-2-corrige` | Agregat Commande, value objects, repository en memoire | vert, 18 tests | sans objet |
| `atelier-3` | 6 elements a ecrire, 9 tests **rouges**, 1 violation de couche | **9 echecs sur 28** | **1 violation** |
| `atelier-3-corrige` | Ports et adaptateurs, view model, test de cas d'usage | vert, 28 tests | 0 violation |
| `reference-complete` | Etat final : atelier 3 + persistance Doctrine | vert, 60 tests | 0 violation |
| `palier-d-horloge` | Corrige du palier D : le temps entre dans le modele | vert, 104 tests | 0 violation |
| `palier-b-erreur-metier` | Corrige du palier B : parler a l'utilisateur depuis le coeur | vert, 123 tests | 0 violation |
| `palier-c-gherkin` | Corrige du palier C : un scenario, deux adaptateurs | vert, 123 tests + 8 scenarios | 0 violation |

```bash
git checkout atelier-2
composer test            # 17 echecs : c'est le point de depart
```

Chaque atelier a sa fiche de travail dans `docs/atelier-N/`.

```
main
 |- atelier-1 - atelier-1-corrige
     |- atelier-2 - atelier-2-corrige
         |- atelier-3 - atelier-3-corrige
             |- reference-complete
                 |- palier-d-horloge
                     |- palier-b-erreur-metier
                         |- palier-c-gherkin
```

`reference-complete` est la branche a ouvrir pour montrer le resultat final : elle porte
tout l'atelier 3 plus la couche de persistance Doctrine, corrige du palier C de
l'atelier 2. Les quatre concessions faites a l'ORM y sont documentees dans
`docs/reference-complete.md`.

`palier-d-horloge` va un cran plus loin : elle fait entrer le temps dans le modele sans
lui faire perdre son determinisme, corrige du palier D de l'atelier 3. Voir
`docs/palier-d-horloge.md`.

`palier-b-erreur-metier` traite la question de savoir comment le coeur signale un probleme
destine a un humain sans savoir a quel humain il parle. Voir
`docs/palier-b-erreur-metier.md`.

`palier-c-gherkin` ferme la serie, et c'est la branche la plus demonstrative : le meme
fichier de scenarios Gherkin est execute par DEUX adaptateurs, l'un en memoire en 0,06 s,
l'autre contre un vrai serveur HTTP en 0,79 s. C'est aussi la seule branche qui contient
une couche web. Voir `docs/palier-c-gherkin.md`.

Les paliers sont empiles pour n'avoir qu'une branche a ouvrir quand on veut montrer
l'etat le plus abouti ; ils restent independants les uns des autres dans la progression
pedagogique.

Ces deux branches ajoutent des dependances (doctrine/orm, puis psr/clock et
symfony/clock) : relancez `composer install` en arrivant dessus, et en les quittant.

## Structure cible

```
src/
├── Domain/          entites, value objects, domain events, interfaces de repository
├── Application/     services applicatifs, DTO, view models, event subscribers
└── Infrastructure/  controleurs, implementations de repository, clients d'API
legacy/              le code de depart, a lire, pas a modifier
tests/
├── Domain/          tests unitaires (aucune infrastructure)
├── UseCase/         tests de cas d'usage (hexagone interne)
└── Support/         builders, espions, conteneur de test
```

## Regles du jeu

- Aucune dependance vers `Doctrine`, `Symfony\Component\HttpFoundation` ou `PDO` dans
  `src/Domain`.
- La suite `domain` doit s'executer **sans base de donnees**, en moins de 100 ms.
- A partir de l'atelier 3, `composer deptrac` doit sortir en succes.
