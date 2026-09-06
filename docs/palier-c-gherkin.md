# Corrigé du palier C : Gherkin, et le même scénario deux fois

Branche `palier-c-gherkin`, posée sur `palier-b-erreur-metier`.

```bash
git checkout palier-c-gherkin
composer install

composer behat:hexagone   # 4 scénarios, 0,06 s
composer behat:http       # les mêmes 4 scénarios, 0,79 s, vrai serveur
composer behat            # les deux suites
composer test             # 123 tests PHPUnit, inchangés
```

---

## Ce que le palier demande, et pourquoi c'est le bon exercice

> Convertissez le test de cas d'usage en scénario Behat. Puis faites tourner **le même
> scénario** contre un second contexte qui émet de vraies requêtes HTTP.

La deuxième phrase est tout l'intérêt. Écrire du Gherkin est facile ; le faire exécuter
par deux adaptateurs différents est la **démonstration de l'architecture hexagonale au
niveau des tests** : un port entrant, deux adaptateurs, aucune ligne de règle métier
dupliquée.

---

## Le scénario, écrit une fois

`features/commander.feature` ne parle ni de HTTP, ni de base de données, ni de PHP :

```gherkin
Scénario: on ne vend pas un livre papier qu'on n'a plus
  Étant donné un livre papier avec 2 exemplaires en stock
  Quand je commande 3 exemplaires de ce livre papier
  Alors l'opération est refusée avec le code "order.insufficient_stock"
```

C'est ce qui lui permet de survivre à une refonte complète de l'infrastructure, et c'est
aussi ce qui le rend lisible par quelqu'un qui ne programme pas. Un scénario qui
mentionnerait « l'utilisateur clique sur le bouton » casserait à la première refonte du
front.

## Exécuté deux fois

| | `HexagoneContext` | `HttpContext` |
|---|---|---|
| Comment il appelle | `ApplicationInterface` directement | `POST /physical-books/orders` |
| Adaptateurs sortants | Doubles en mémoire | SQLite réelle, jeu d'essai JSON |
| Ce qu'il vérifie | L'exception levée et sa clé | Le statut 422 et le code JSON |
| Durée | **0,06 s** | **0,79 s**, soit treize fois plus |
| Quand le lancer | À chaque sauvegarde | Avant de livrer |

Les définitions d'étapes diffèrent ; les scénarios sont le même fichier. C'est
`behat.yml` qui monte les deux suites sur les mêmes `features/`.

---

## Ce qu'il a fallu construire, et ce que ça enseigne

Le dépôt n'avait **aucune couche web** jusqu'ici — c'était volontaire, pour que les trois
journées ne dépendent d'aucun framework. La suite HTTP a donc exigé de l'écrire :

| Fichier | Rôle |
|---|---|
| `public/index.php` | Le point d'entrée. La seule ligne du projet qui appelle le conteneur. |
| `Infrastructure/Http/Router.php` | Méthode, chemin, statuts, JSON. Une traduction, pas une architecture. |
| `Infrastructure/Http/HttpServiceContainer.php` | La racine de composition du processus web |
| `tests/Behat/HttpServer.php` | Démarre et arrête `php -S`, attend le port |

Deux choses méritent d'être montrées en salle.

**Le routeur applique la règle du palier B.** Une `UserErrorMessage` devient un 422 avec
un code machine ; tout le reste devient un 500 sans aucun détail. La distinction que le
cœur a établie se retrouve intacte dans les codes HTTP, sans que le cœur connaisse HTTP.

**Le point d'entrée est le seul endroit qui appelle le conteneur.** C'est la racine de
composition du jour 2 : là, le conteneur assemble le graphe d'objets ; au-delà, plus
personne n'y cherche quoi que ce soit.

---

## Deux pièges rencontrés en écrivant ce corrigé

### 1. La suite verte qui teste le mauvais serveur

Pendant l'écriture, un `php -S` d'un essai précédent était resté sur le port 8321. La
suite HTTP s'y est connectée, a répondu correctement, et **est passée au vert en testant
un code qui n'était pas celui du dépôt**. Rien dans le rapport ne le signalait.

D'où le garde-fou dans `HttpServer::demarrer()` : si le port répond déjà, on refuse de
démarrer et on le dit.

```php
if (is_resource($occupe)) {
    throw new RuntimeException(sprintf('Le port %d est deja utilise…', $port));
}
```

Le port est configurable par `BOOKSHELF_PORT`, pour que deux personnes puissent lancer la
suite en même temps sur la même machine.

> C'est la même leçon que le piège de la carte d'identité Doctrine
> (`docs/reference-complete.md`) et que le test d'architecture du palier D
> (`docs/palier-d-horloge.md`) : **un test qu'on n'a jamais vu échouer ne prouve rien.**
> Ici, c'est le test lui-même qui mentait, pas le code.

### 2. Un test d'architecture qui criait au loup

En ajoutant la couche HTTP, le test
`le_vocabulaire_de_vatapi_ne_sort_pas_de_l_infrastructure` s'est mis à échouer :
`HttpServiceContainer` contenait la chaîne `'ebooks'`… qui était une clé du fichier de jeu
d'essai, pas le filtre de vatapi.com.

`'ebooks'` est à la fois du vocabulaire fournisseur et un mot du domaine : c'est un jeton
**ambigu**, impossible à chercher sans faux positifs. Il a été retiré de la liste ; les
quatre autres (`TBE`, `electronic`, `rate_type`, `filter_match`) n'ont, eux, aucune raison
d'exister ailleurs que dans l'adaptateur.

C'est exactement le défaut corrigé au palier D avec `date(` qui attrapait `validate(`. Un
test d'architecture qui produit des faux positifs finit désactivé, donc inutile.

---

## Ce que la suite HTTP ne prouve pas

Elle prouve que le câblage tient : routage, désérialisation, appel du cas d'usage,
persistance réelle, sérialisation, codes de statut. C'est beaucoup.

Elle ne prouve pas les règles métier — celles-ci sont couvertes par les tests unitaires et
les tests de cas d'usage, qui sont **cent fois plus rapides et ne cassent pas parce qu'un
port est occupé**. C'est pourquoi on écrit peu de tests de bout en bout, et pourquoi on ne
leur fait pas porter ce qu'un test rapide sait démontrer.

Rapport de forces sur cette branche :

| Niveau | Nombre | Durée |
|---|---|---|
| Tests unitaires + cas d'usage + adaptateur (PHPUnit) | 120 | 0,10 s |
| Scénarios hexagone (Behat) | 4 | 0,06 s |
| Scénarios HTTP (Behat) | 4 | 0,79 s |

---

## Pour aller plus loin

**Écrire les scénarios avant le code.** Ici ils ont été dérivés de tests existants, ce qui
est l'ordre inverse du workflow recommandé au jour 3, section 7.7. Reprenez une
fonctionnalité à venir de vos projets et écrivez d'abord les scénarios, avec le métier.

**Un troisième adaptateur.** Le même fichier de scénarios pourrait être exécuté par un
`CliContext` qui appelle une commande console. Trois adaptateurs, un scénario : à ce
stade, plus personne dans l'équipe ne discute l'intérêt des ports.
