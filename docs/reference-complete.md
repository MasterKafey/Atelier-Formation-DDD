# Branche `reference-complete` : l'état final, persistance comprise

Corrigé du **palier C de l'atelier 2** (la persistance Doctrine), posé sur
**`atelier-3-corrige`**.

C'est donc l'**état de référence complet** du projet : les ports et adaptateurs du jour 3
*plus* la couche de persistance. Le palier reste rattaché à l'atelier 2 dans la
progression pédagogique, mais son corrigé est bâti sur l'état final du code pour qu'il n'y
ait qu'une seule branche à ouvrir pour montrer le résultat.

```bash
git checkout reference-complete
composer install          # doctrine/orm entre en jeu sur cette branche
composer test             # 60 tests, dont un aller-retour réel en base
```

| Suite | Tests | Ce qu'elle prouve |
|---|---|---|
| `domain` | 14 | Les invariants et les transitions d'état, sans aucune infrastructure |
| `usecase` | 7 | Les scénarios métier, adaptateurs sortants remplacés par des doubles |
| `adapter` | 35 | L'intégration technique : contrat du repository, couche anticorruption, non-contamination |
| `smoke` | 1 | L'autoloader |

Le test de contrat s'exécute contre **SQLite en mémoire**, donc sans serveur de base de
données. En projet réel, vous le feriez tourner contre le même moteur qu'en production :
c'est tout l'intérêt d'un test d'adaptateur.

---

## Ce qui a été ajouté

| Fichier | Rôle |
|---|---|
| `Infrastructure/Doctrine/Type/*.php` | Huit types personnalisés, un par value object |
| `Infrastructure/Doctrine/EntityManagerFactory.php` | Racine de composition de la persistance |
| `Infrastructure/Doctrine/CommandeRepositoryAvecDoctrine.php` | L'adaptateur de production |
| `bin/dump-schema.php` | Génère le DDL à partir du mapping |
| `migrations/001-orders.*.sql` | Le schéma généré, pour SQLite et MySQL |
| `tests/Adapter/CommandeRepositoryContractTest.php` | Le test de contrat, sur les deux implémentations |
| `tests/Adapter/MappingContaminationTest.php` | Vérifie que seul le mapping entre dans le domaine |

Le repository de production tient en **trente lignes**. Tout le travail difficile est
ailleurs : dans les types, qui savent traduire chaque value object, et dans l'agrégat, qui
garantit qu'aucune donnée incohérente n'arrive jusque-là.

---

## Les quatre concessions faites à l'ORM

C'est le vrai contenu pédagogique de ce palier. Introduire un ORM n'est jamais gratuit :
ce qui compte est de savoir **exactement** ce qu'on lui cède, et de pouvoir le défendre.

### 1. Des attributs de mapping dans le domaine

`Commande` et `LigneDeCommande` portent des `#[ORM\Column]`, donc des noms de colonnes et des types
SQL.

**Pourquoi c'est acceptable :** l'entité reste du code cœur au sens des deux règles du
jour 1. On peut l'instancier et appeler ses méthodes sans base de données et sans contexte
particulier — c'est d'ailleurs ce que font les 17 tests unitaires de l'atelier 2, qui
tournent toujours en quelques millisecondes.

**L'alternative, et pourquoi on l'écarte :** exposer les propriétés privées de l'entité
pour qu'un mapper externe les lise. Le prix est bien plus élevé — on perd la liberté de
renommer ou restructurer l'intérieur de l'objet, c'est-à-dire précisément ce qui permet
d'améliorer sa conception.

Un test le vérifie automatiquement : `MappingContaminationTest` échoue si un fichier de
`src/Domain` importe autre chose que `Doctrine\ORM\Mapping` ou
`Doctrine\Common\Collections`. Un `EntityManagerInterface` dans le domaine serait une
faute, pas un compromis.

### 2. `Collection` au lieu de `array`

`Commande::$lignes` est devenu une `Doctrine\Common\Collections\Collection`. C'est la
concession la plus visible : l'ORM fait entrer une classe tierce dans le domaine.

**Pourquoi on l'accepte ici :** il s'agit des entités **filles** de l'agrégat, donc d'une
association un-à-plusieurs — la règle n° 2 des quatre règles du jour 2 l'autorise
explicitement.

**Ce qu'on n'accepterait pas :** la même chose pour référencer un **autre agrégat**. Un
`Ebook` reste désigné par son `IdentifiantEbook`, jamais par une association Doctrine. C'est ce qui
empêche `$commande->getLine(1)->getEbook()->getPublisher()` et préserve les frontières.

### 3. Une clé de substitution sur `LigneDeCommande`

Le métier n'a aucun besoin d'identifier une ligne de commande. C'est Doctrine qui exige
une identité pour toute entité. On ajoute donc un `id` auto-incrémenté, gardé **privé et
sans accesseur** : il n'existe que pour la persistance et ne fuit nulle part.

Même chose pour l'association retour `LigneDeCommande::$commande`, exigée par le `mappedBy` : aucune
méthode métier ne l'utilise.

### 4. `IdentifiantCommande::__toString()`

Celle-ci ne se découvre qu'à l'exécution, et c'est la plus instructive.

Doctrine construit sa carte d'identité en convertissant l'identifiant en chaîne. Un value
object en clé primaire doit donc être convertible en chaîne, sans quoi `persist()` échoue
avec :

```
Object of class Bookshelf\Domain\Model\Commande\IdentifiantCommande could not be converted to string
```

C'est du code purement technique dans une classe de domaine. Le compromis reste acceptable
— la méthode n'introduit aucune dépendance et ne demande aucun contexte — mais il fallait
le nommer.

---

## Le cas `Montant` : trois options, un arbitrage

`Montant` porte **deux** valeurs, un montant et une devise. Un type Doctrine personnalisé
mappe une propriété vers **une** colonne. Il faut donc choisir.

| Option | Ce qu'on gagne | Ce qu'on perd |
|---|---|---|
| `#[ORM\Embedded]` | Deux colonnes propres, `SUM()` possible | La règle « mapping simple uniquement » |
| Deux propriétés primitives dans l'entité | Mapping trivial | L'encapsulation : le domaine remanipule des entiers |
| **Une colonne texte `"2500 EUR"`** | **Mapping simple, encapsulation intacte** | **Aucune agrégation SQL possible** |

La troisième a été retenue, pour une raison précise : **les totaux ne sont jamais
persistés**, ils sont calculés par l'agrégat. Nous n'avons donc aucun besoin d'agréger des
montants en SQL, et c'est la seule chose que cette option nous interdise.

> Le jour où un `SUM()` sur ces montants devient nécessaire, ce compromis ne tient plus. Il
> faudra passer à l'embeddable, ou construire un read model dédié alimenté par événements.
> **Notez-le dans le code** : c'est exactement le genre de décision qu'on oublie d'écrire,
> et qu'un successeur redécouvre trois ans plus tard en croyant à une négligence.

---

## Le piège du test de repository

Le test de contrat contient une leçon qui ne s'apprend qu'en le ratant une fois.

Écrit naïvement, il ressemble à ceci :

```php
$repository->enregistrer($commande);
$rechargee = $repository->parIdentifiant($commande->identifiantCommande());
self::assertEquals($commande->totalTtc(), $rechargee->totalTtc());
```

**Ce test passe, et il ne prouve rien.** Doctrine garde une carte d'identité : `parIdentifiant()`
vous rend l'objet qu'il a déjà en mémoire, sans faire le moindre aller-retour en base. Le
mapping peut être entièrement faux, le test reste vert.

D'où le `oublier()` du test de contrat, qui appelle `$entityManager->clear()` avant la
relecture. C'est un no-op pour l'implémentation en mémoire — et c'est honnête : ce double
ne sérialise rien, cette limite doit être connue de ceux qui s'en servent.

Vérifiez-le vous-même : commentez l'appel à `$oublier()` et cassez volontairement un type
personnalisé. Le test restera vert.

---

## Régénérer le schéma

```bash
php bin/dump-schema.php sqlite > migrations/001-orders.sqlite.sql
php bin/dump-schema.php mysql  > migrations/001-orders.mysql.sql
```

En projet réel vous utiliseriez `doctrine/migrations`. Ce script évite une dépendance de
plus dans un dépôt de formation, tout en montrant l'essentiel : **le schéma se déduit du
modèle**. On ne le maintient pas à la main en parallèle, sans quoi les deux divergent.

---

## Ce qui n'a pas bougé, et c'est le point

- Les **17 tests unitaires** de l'atelier 2 et les **7 tests de cas d'usage** de l'atelier 3
  passent sans modification, toujours sans base de données.
- Aucun setter n'est apparu.
- Les cinq invariants sont protégés par les mêmes gardes.
- Les domain events ne sont pas mappés : ils sont transitoires, ils ne survivent pas à un
  rechargement, et c'est voulu.

Autrement dit : **la persistance a été ajoutée par-dessus le modèle, pas dedans.** C'est
l'ordre qui compte. L'inverse — concevoir les tables puis en déduire les classes — est ce
qui produit les modèles anémiques du jour 2.
