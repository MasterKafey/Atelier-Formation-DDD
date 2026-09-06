# Palier D : l'horloge

**Atelier 3, palier d'approfondissement. Environ 30 min. Branche `atelier-3`.**

---

## L'état de départ, et pourquoi il compte

Lancez ceci avant toute chose :

```bash
grep -rn "DateTime" src/
```

**Aucun résultat.** Il n'y a pas une seule date dans le cœur de l'application.

Ce n'est pas un oubli, c'est la raison pour laquelle les 20 tests de `domain` et
`usecase` s'exécutent en quelques millisecondes et donnent toujours le même résultat. Un
modèle sans temps est trivialement déterministe.

L'exercice n'est donc pas de remplacer des appels existants : c'est d'**introduire le
temps sans perdre ce déterminisme**. C'est plus difficile, et c'est le vrai sujet.

> ⚠️ `new DateTimeImmutable('now')` est un appel à un système externe : l'horloge de la
> machine. Au sens de la règle n° 1 du jour 1, tout code qui l'appelle est du code
> d'infrastructure. Il en va de même pour `time()`, `date()`, `rand()`, `uniqid()` et
> `random_bytes()`.

---

## Ce qu'on veut obtenir

Une règle métier qui n'existe pas encore chez Bookshelf, et qui est plausible : on ne
garde pas indéfiniment une commande non payée, parce que le prix affiché peut changer.

> **Une commande non payée depuis plus de 48 heures ne peut plus être payée.**

Cette règle a une propriété intéressante pour nous : **elle a besoin de deux dates**, celle
où la commande a été passée et celle du paiement. C'est ce qui va forcer les deux
techniques de la section 5 du jour 3.

---

## Le travail

### 1. Installer l'abstraction (2 min)

```bash
composer require psr/clock symfony/clock
```

**N'écrivez pas votre propre interface `Clock`.** PSR-20 existe, elle tient en une
méthode, et tout l'écosystème la connaît :

```php
namespace Psr\Clock;

interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}
```

C'est le réflexe « sous-domaine générique » du jour 1 : le problème est résolu, on
intègre.

`symfony/clock` fournit les deux implémentations dont vous avez besoin :

| Classe | Usage |
|---|---|
| `Symfony\Component\Clock\NativeClock` | Production. Rend l'heure système. |
| `Symfony\Component\Clock\MockClock` | Tests. Rend l'heure que vous lui donnez, et sait avancer (`modify()`, `sleep()`). |

> `MockClock::now()` rend un `DatePoint`, qui étend `DateTimeImmutable`. Vous pouvez donc
> le typer `DateTimeImmutable` partout : rien à adapter.

### 2. Donner une date à la commande (10 min)

Dans `Domain\Model\Commande\Commande` :

- `passer()` reçoit un `DateTimeImmutable $passeeLe` et le conserve ;
- `payer()` reçoit un `DateTimeImmutable $payeeLe` en plus de la référence de paiement ;
- `payer()` refuse si plus de 48 heures séparent `$passeeLe` de `$payeeLe`, en levant
  `PaiementImpossible::carDelaiDepasse()` — ajoutez ce constructeur nommé ;
- `CommandePayee` transporte la date de paiement.

**La date arrive en argument de méthode. L'entité ne reçoit pas d'horloge.** Voir les
pièges, plus bas.

Une constante pour le délai, nommée d'après le métier :

```php
private const DELAI_DE_PAIEMENT = 'PT48H';
```

### 3. Fournir l'heure depuis la couche application (10 min)

C'est le service applicatif qui sait quelle heure il est, parce que lui a le droit de
dépendre d'une abstraction d'infrastructure.

Dans `Application\PasserCommande\PasserCommandeService` :

- injectez `Psr\Clock\ClockInterface` comme dépendance de constructeur ;
- appelez `$this->horloge->now()` **une seule fois** par exécution, et passez la valeur
  obtenue à `Commande::passer()`.

> ⚠️ Appeler `now()` plusieurs fois dans le même cas d'usage vous donne des instants
> différents. Sur une commande, ça se voit peu ; sur un traitement par lots, ça produit
> des incohérences que personne ne sait reproduire. **Une lecture de l'horloge par cas
> d'usage.**

Ajoutez ensuite un service applicatif `PayerCommandeService` qui charge la commande, appelle
`payer()` avec `$this->horloge->now()`, enregistre et publie les événements.

### 4. Câbler et tester (8 min)

Dans `Tests\Support\TestServiceContainer` :

- exposez une `MockClock` figée à une date lisible, par exemple
  `new MockClock('2026-03-01 10:00:00')` ;
- rendez-la publique pour que les tests puissent la faire avancer.

Écrivez ensuite :

**Deux tests unitaires** dans `tests/Domain/CommandeTest.php`, sans aucune horloge — les
dates y sont des arguments, donc de simples valeurs :

1. une commande payée dans les 48 heures enregistre bien sa date de paiement ;
2. une commande payée après 48 heures lève `PaiementImpossible`.

**Un test de cas d'usage** dans `tests/UseCase/` qui, lui, fait avancer l'horloge :

```php
$container = new TestServiceContainer();
$identifiantEbook = $container->catalogue()->ajouter('Architecture hexagonale', 2500);

$identifiantCommande = $container->application()->passerCommande(
    new PasserCommande($identifiantEbook->enChaine(), 1, 'paul@exemple.fr', 'FR'),
);

$container->horloge()->modify('+49 hours');

$this->expectException(PaiementImpossible::class);

$container->application()->payerCommande(new PayerCommande($identifiantCommande->enChaine(), 'PAY-001'));
```

Trois jours de formation tiennent dans ce test : le temps y est une **donnée**, pas un
effet de bord. Il s'exécute en une milliseconde et prouve une règle métier qui porte sur
deux jours.

---

## Contraintes

- Aucun `new DateTimeImmutable`, `time()` ou `date()` dans `src/Domain` ni dans
  `src/Application`. Vérifiez : `grep -rn "new DateTimeImmutable\|time()\|date(" src/Domain src/Application`
- `composer deptrac` sort toujours en succès.
- Les tests existants passent **sans modification autre que l'ajout des nouveaux
  arguments**. Si vous devez réécrire la logique d'un test, c'est que la conception a
  dérivé.

---

## Les trois pièges

### 1. Injecter l'horloge dans l'entité

```php
// NON
final class Commande
{
    public function __construct(private ClockInterface $horloge) { }

    public function payer(ReferenceDePaiement $reference): void
    {
        $payeeLe = $this->horloge->now();
    }
}
```

Une entité est une donnée à état, pas un service. Lui injecter une dépendance la rend
impossible à instancier simplement, complique sa persistance, et surtout : son
comportement ne s'explique plus par ses seuls arguments. Vous perdez exactement la
propriété qui rendait vos tests unitaires rapides et fiables.

**La date se passe en argument.** L'entité reste une fonction pure de ses entrées.

### 2. Utiliser la façade statique

`symfony/clock` propose `Clock::get()->now()`, accessible de partout. C'est commode, et
c'est un **localisateur de services** : le code exige qu'un contexte global ait été
préparé avant de tourner. Règle n° 2 du jour 1, violée. Section 5.4 du jour 2, pour le
détail de ce que ça coûte.

### 3. Croire qu'on a fini

Votre `MockClock` prouve que la règle des 48 heures est correcte. Elle ne prouve pas que
`NativeClock` rend l'heure juste, ni que le fuseau est le bon, ni que la date survit à un
aller-retour en base. Ce sont trois questions d'**adaptateur**, pas de cas d'usage.

La troisième est la plus sournoise : un `DateTimeImmutable` stocké sans fuseau et relu
dans un autre fuseau ne vaut plus la même chose. Sur la branche `reference-complete`,
ajoutez les deux dates au mapping Doctrine et faites-les passer par le test de contrat
existant. Vous verrez tout de suite si votre colonne est correcte.

---

## Critères de réussite

- [ ] `Psr\Clock\ClockInterface` est utilisée, aucune interface maison n'a été écrite
- [ ] Aucune horloge n'est injectée dans une entité
- [ ] `now()` est appelée une seule fois par cas d'usage
- [ ] Les deux tests unitaires ne référencent aucune horloge
- [ ] Le test de cas d'usage fait avancer le temps sans jamais attendre
- [ ] `grep -rn "new DateTimeImmutable" src/Domain src/Application` ne rend rien
- [ ] `composer test` et `composer deptrac` passent

---

## Pour aller plus loin

**L'aléa suit exactement le même raisonnement.** `IdentifiantCommande::generer()` appelle
`Uuid::v7()`, qui lit le générateur d'aléa du système : c'est aussi un appel à un système
externe. On l'accepte aujourd'hui parce que la génération est confinée dans
`prochainIdentifiant()`, côté repository, c'est-à-dire déjà dans l'infrastructure. Vérifiez-le,
et demandez-vous ce qu'il faudrait changer pour qu'un test puisse imposer les
identifiants qu'il veut.

**Le temps comme concept métier.** « 48 heures » est ici une durée technique. Chez
beaucoup de clients, ce serait « deux jours ouvrés », ce qui suppose un calendrier, des
jours fériés, un fuseau de référence. À ce moment-là le délai n'est plus un
`DateInterval` : c'est un **service de domaine**, avec ses propres tests. Demandez-vous à
partir de quand vous franchiriez cette frontière.
