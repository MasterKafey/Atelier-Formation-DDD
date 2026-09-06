# Corrigé du palier D : l'horloge

Branche `palier-d-horloge`, posée sur `reference-complete`.

```bash
git checkout palier-d-horloge
composer install          # psr/clock et symfony/clock entrent en jeu
composer test             # 104 tests
```

| Suite | Tests | Ce qu'elle prouve |
|---|---|---|
| `domain` | 16 | Les invariants, dont la règle des 48 heures. **Aucune horloge.** |
| `usecase` | 9 | Les scénarios métier, dont l'expiration, en faisant avancer le temps |
| `adapter` | 75 | Contrat du repository (dates comprises), anticorruption, tests d'architecture |
| `smoke` | 1 | L'autoloader |

---

## La règle ajoutée

> Une commande non payée depuis plus de 48 heures ne peut plus être payée.

Elle a été choisie pour une raison précise : **elle exige deux dates**, celle où la
commande a été passée et celle du paiement. C'est ce qui force les deux techniques de la
section 5 du jour 3, au lieu d'en illustrer une seule.

---

## Le principe, en deux lignes de signature

```php
// Domaine : le temps arrive PAR LES ARGUMENTS
public function payer(ReferenceDePaiement $reference, DateTimeImmutable $payeeLe): void

// Application : le temps est LU par une abstraction injectée
public function __construct(/* ... */, private ClockInterface $horloge)
```

C'est tout le palier. Le reste en découle.

**L'entité reste une fonction pure de ses entrées.** Donnez-lui les mêmes arguments, elle
se comportera toujours de la même façon. Ses tests unitaires n'ont donc aucune horloge à
configurer, et ils restent instantanés :

```php
#[Test]
public function une_commande_ne_peut_plus_etre_payee_apres_quarante_huit_heures(): void
{
    $commande = CommandeBuilder::creer()->confirmee()->construire();

    $this->expectException(PaiementImpossible::class);

    $commande->payer(ReferenceDePaiement::depuisChaine('PAY-004'), $this->horsDelais());
}
```

Pas de `MockClock` ici. Une date est une valeur ; il n'y a rien à simuler.

**Le service applicatif, lui, a le droit de savoir quelle heure il est**, parce qu'il a le
droit de dépendre d'une abstraction d'infrastructure. Il lit l'horloge **une seule fois**
par exécution :

```php
$maintenant = $this->horloge->now();

$commande = Commande::passer($identifiantCommande, /* ... */, $maintenant);
```

> ⚠️ Appeler `now()` plusieurs fois dans le même cas d'usage donne des instants
> différents. Sur une commande, ça ne se voit pas ; sur un traitement par lots, ça produit
> des incohérences que personne ne sait reproduire.

---

## Ce que le test de cas d'usage prouve

```php
$container->horloge()->modify('+49 hours');

$this->expectException(PaiementImpossible::class);

$container->application()->payerCommande(new PayerCommande($identifiantCommande->enChaine(), 'PAY-002'));
```

Une règle qui porte sur **deux jours**, vérifiée en **une milliseconde**. Personne
n'attend, personne ne dort, et le résultat sera le même dans dix ans.

C'est l'aboutissement des trois journées : le temps est devenu une **donnée** du système,
pas un effet de bord subi.

---

## Ce qui a été ajouté, fichier par fichier

| Fichier | Rôle |
|---|---|
| `Domain/Model/Commande/Commande.php` | `$passeeLe`, `$payeeLe`, la constante `DELAI_DE_PAIEMENT`, la garde dans `payer()` |
| `Domain/Model/Commande/PaiementImpossible.php` | `carDelaiDepasse()`, avec les deux dates dans le message |
| `Domain/Model/Commande/CommandePayee.php` | L'événement transporte la date de paiement |
| `Application/PasserCommande/PasserCommandeService.php` | Injection de `ClockInterface`, une seule lecture |
| `Application/PayerCommande/` | Le cas d'usage, **deja fourni sur `atelier-3`**, apprend a lire l'heure |
| `tests/Support/TestServiceContainer.php` | Une `MockClock` figée, exposée aux tests |
| `tests/Support/CommandeBuilder.php` | Des dates **fixes**, jamais `new DateTimeImmutable('now')` |
| `tests/Adapter/NoSystemClockTest.php` | Interdit l'horloge système dans le Domaine et l'Application |

Le mapping Doctrine reçoit deux colonnes, `placed_at NOT NULL` et `paid_at NULL`, et le
test de contrat vérifie désormais que la date de paiement survit à l'aller-retour en base.

---

## Le test d'architecture, et pourquoi il a failli ne servir à rien

`NoSystemClockTest` interdit `new DateTimeImmutable`, `time()`, `date()` et `mktime()`
dans `src/Domain` et `src/Application`. Deux détails d'écriture décident s'il protège
vraiment quelque chose.

**1. Des expressions régulières, pas des recherches de texte.** Un `str_contains($code, 'date(')`
matche aussi `validate(`. Le test devient un faux positif, l'équipe le désactive, et il ne
protège plus rien. Un test d'architecture qui crie au loup est pire que pas de test.

**2. Il faut le vérifier par mutation.** La première version de ce test **ne détectait
rien** : le motif `'/\bnew\s+\\?DateTime/'` en chaîne PHP à guillemets simples produit
`\?`, c'est-à-dire un point d'interrogation **littéral**, et non un antislash optionnel.
Le test passait au vert sur du code fautif.

C'est un exercice à faire faire en salle, parce qu'il porte une leçon plus large que
l'horloge :

```bash
# Injectez volontairement la faute :
#   $commande->payer($intention->referenceDePaiement(), new DateTimeImmutable('now'));
composer test
```

Le test doit **échouer**, et nommer le fichier. S'il reste vert, ce n'est pas le code qui
est bon : c'est le test qui est faux.

> **Un test qu'on n'a jamais vu échouer ne prouve rien.** Cela vaut pour les tests
> d'architecture, pour les tests de repository — voir le piège de la carte d'identité dans
> `docs/reference-complete.md` — et, en réalité, pour tous les autres.

---

## Ce qui n'a pas bougé

- Les tests unitaires du domaine ne connaissent toujours **aucune horloge**.
- Aucun setter n'est apparu.
- `composer deptrac` sort toujours en succès : `ClockInterface` est dans la couche
  Application, ses implémentations dans l'Infrastructure.
- La suite complète s'exécute en un dixième de seconde.

---

## Pour aller plus loin

**L'aléa suit le même raisonnement.** `IdentifiantCommande::generer()` appelle `Uuid::v7()`, qui lit
le générateur d'aléa du système. On l'accepte parce que la génération est confinée dans
`prochainIdentifiant()`, côté repository, donc déjà dans l'infrastructure. Vérifiez-le, et
demandez-vous ce qu'il faudrait changer pour qu'un test puisse imposer les identifiants
qu'il veut.

**Le temps comme concept métier.** « 48 heures » est ici une durée technique. Chez
beaucoup de clients ce serait « deux jours ouvrés », ce qui suppose un calendrier, des
jours fériés, un fuseau de référence. À ce moment-là le délai n'est plus un
`DateInterval` : c'est un **service de domaine**, avec ses propres tests. La frontière se
franchit le jour où la règle cesse d'être une soustraction de dates.
