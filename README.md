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

| Branche | Contenu | Etat des tests |
|---|---|---|
| `main` | Socle du projet, code legacy a lire | vert (suite de fumee) |
| `atelier-1` | Materiel de l'atelier 1 (entretien metier). Pas de code a ecrire. | vert |
| `atelier-1-corrige` | Une cartographie possible du domaine | vert |
| `atelier-2` | Squelettes + 14 tests **rouges** a faire passer | **rouge** |
| `atelier-2-corrige` | Agregat Commande complet, value objects, repository | vert |
| `atelier-3` | Squelettes + tests **rouges** du jour 3 | **rouge** |
| `atelier-3-corrige` | Ports et adaptateurs, view model, test de cas d'usage | vert |

```bash
git checkout atelier-2
composer test            # 17 echecs : c'est le point de depart
```

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
