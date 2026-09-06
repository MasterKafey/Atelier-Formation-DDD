# Corrigé du palier B : l'erreur métier

Branche `palier-b-erreur-metier`, posée sur `palier-d-horloge`.

```bash
git checkout palier-b-erreur-metier
composer install
composer test             # 123 tests
composer deptrac          # 0 violation
```

| Suite | Tests | Ce qu'elle prouve |
|---|---|---|
| `domain` | 16 | Les invariants, sans aucune infrastructure |
| `usecase` | 14 | Les scénarios métier, dont le stock insuffisant |
| `adapter` | 89 | Intégration technique et contrats d'architecture |
| `smoke` | 1 | L'autoloader |

---

## Le problème

Bookshelf vend quelques livres papier. Le stock est fini et non réapprovisionnable : on
ne vend que ce qu'on a.

Cette règle ne peut pas vivre dans l'entité `Commande` : une commande ne voit qu'elle-même
et ignore tout du stock. C'est donc le **service applicatif** qui vérifie — le troisième
emplacement de validation vu au jour 2, après le value object et l'entité.

Mais le service applicatif ne peut pas *rendre une erreur de formulaire* : le formulaire
est une notion web, et ce service doit rester appelable depuis une API, une CLI ou un
test. D'où la question du palier : **comment le cœur signale-t-il un problème destiné à
un humain, sans savoir à quel humain il parle ?**

---

## Le mécanisme, en une interface

```php
interface UserErrorMessage extends Throwable
{
    public function translationKey(): string;

    /** @return array<string, string> */
    public function translationParameters(): array;
}
```

Elle marque les exceptions dont le message a vocation à être **montré**, et transporte de
quoi le formuler. Elle ne transporte pas le texte : traduire dépend de qui lit, dans
quelle langue, sur quel média.

Toutes les autres exceptions du domaine restent invisibles à l'utilisateur. Une
`AnnulationImpossible` signale soit un abus, soit un défaut de l'interface : elle se traite
dans les logs, pas devant le client.

> Une interface ne peut pas étendre `Exception`. D'où le couple
> `UserErrorMessage` + `BaseUserErrorMessage extends RuntimeException` : l'interface pour
> le typage, la classe abstraite pour éviter de réécrire le constructeur à chaque fois.

Le service se contente de lever :

```php
if ($disponibles < $intention->quantite) {
    throw CommandeDeLivrePapierImpossible::carStockInsuffisant($disponibles, $intention->quantite);
}
```

---

## Une exception, trois rendus

C'est la démonstration de la séparation. Le cœur a dit **ce qui** s'est mal passé ; trois
adaptateurs en tirent trois choses différentes, sans qu'une ligne de code métier ne
change.

| Adaptateur | Rendu | Pourquoi celui-là |
|---|---|---|
| `Http\FormErrorPresenter` | `['general' => ['Il ne reste que 2 exemplaire(s)…']]` | L'utilisateur lit une phrase, dans sa langue |
| `Api\ApiErrorPresenter` | `{"error":{"code":"order.insufficient_stock","parameters":{…}}}` | Un partenaire veut brancher un `switch`, pas afficher notre français |
| `Cli\ConsoleErrorPresenter` | `[erreur] Il ne reste que 2 exemplaire(s)…` | Une ligne pour `stderr`, sans balisage |

`tests/Adapter/ErrorPresentationTest.php` exerce les trois sur **la même instance
d'exception**. Ajouter un quatrième canal — un SMS, un webhook — ne toucherait pas
davantage au cœur.

Le cas de l'API mérite qu'on s'y arrête : lui envoyer du français traduit serait lui
rendre un mauvais service. Un code machine et des paramètres, c'est ce qui permet à
l'intégrateur d'écrire son propre message. C'est aussi pourquoi la clé et les paramètres
sont séparés plutôt que pré-assemblés.

---

## Le test d'architecture, et ce qu'il empêche

`UserErrorMessageContractTest` découvre par réflexion toutes les exceptions implémentant
`UserErrorMessage`, appelle chacun de leurs constructeurs nommés, et vérifie que la clé
rendue **ressemble à une clé et non à une phrase** :

```php
private const FORMAT_DE_CLE = '/^[a-z0-9_]+(\.[a-z0-9_]+)+$/';
```

Vérifié par mutation. Remplacez la clé par une phrase :

```php
return new self('Il ne reste que ' . $disponibles . ' exemplaires', [...]);
```

Le test échoue, nomme la classe et la méthode, et explique :

> `CommandeDeLivrePapierImpossible::carStockInsuffisant()` rend « Il ne reste que 1
> exemplaires » : cela ressemble à une phrase, pas à une clé. Formuler est le travail de
> l'adaptateur.

Sans ce garde-fou, la première phrase française glissée dans une exception passe
inaperçue. Le jour où l'API doit répondre à un partenaire néerlandais, il est trop tard :
la traduction est figée dans le cœur, à un endroit que personne ne pense à regarder.

---

## Le vrai enseignement : cette vérification n'est pas une garantie

C'est contre-intuitif, et c'est le point du palier.

`nombreExemplairesDisponibles()` est une **fonction impure** : elle ne rend pas la même réponse
à deux instants différents. Sa réponse n'est jamais fausse — elle est juste au moment du
calcul — mais elle change tout le temps.

Entre la vérification et l'enregistrement, un autre client peut avoir pris le dernier
exemplaire. Un test le met en scène explicitement :

```php
// Deux clients consultent le stock au même instant : il reste un exemplaire.
$vuParClientA = $container->stock()->nombreExemplairesDisponibles($identifiantLivre);   // 1
$vuParClientB = $container->stock()->nombreExemplairesDisponibles($identifiantLivre);   // 1

// Les deux passeront la vérification. Un seul sera servi.
```

> **Règle : ne validez qu'avec des fonctions pures.** Si une donnée est valide maintenant,
> elle doit l'être à la validation suivante. Sinon vous n'avez pas une validation, vous
> avez une vérification de meilleur effort — ce qui est très bien, à condition de le
> savoir.

### Ce que le métier en dit

Relisez l'entretien avec Camille (`docs/atelier-1/entretien-camille.md`) :

> « Ça arrive. Rarement, mais ça arrive. On appelle le deuxième, on s'excuse, on lui
> propose autre chose ou on lui fait un geste. Ce n'est pas un drame. Le pire ce serait de
> bloquer la vente par peur que ça arrive, on perdrait plus de commandes qu'on n'en
> sauverait. »

Le métier a déjà tranché, et personne ne le lui avait demandé. Il ne veut pas d'un
verrouillage transactionnel du stock : il veut **se rattraper** plutôt que **prévenir**.

C'est le glissement de posture à faire faire en salle : de *empêcher que ça arrive* à
*savoir s'en remettre*. Les trois stratégies possibles :

| Stratégie | Quand |
|---|---|
| Accepter l'imprécision, vérifier quand même | La collision est rarissime. **Le choix de Bookshelf.** |
| Accepter puis traiter : « nous traitons votre commande » | Volume élevé, réponse différée acceptable |
| Verrouiller le stock en base | Vraie contrainte de rareté, et on accepte le coût |

Le corrigé implémente la première, parce que c'est ce que le métier a demandé — pas parce
que c'est la plus simple.

---

## Ce qui a été ajouté

| Fichier | Rôle |
|---|---|
| `Application/UserErrorMessage.php` | L'interface qui marque les erreurs destinées à l'utilisateur |
| `Application/BaseUserErrorMessage.php` | La classe de base, pour éviter le boilerplate |
| `Application/CommanderLivrePapier/` | Le cas d'usage, ses deux ports et son exception |
| `Domain/Model/Commande/IdentifiantLivre.php` | Identité d'un livre papier, distincte de `IdentifiantEbook` |
| `Infrastructure/Http`, `Api`, `Cli` | Les trois présentateurs |
| `Infrastructure/Translation/` | Le port de traduction et un catalogue français minimal |
| `Infrastructure/InMemory/StockEnMemoire.php` | Une classe pour les deux ports, lecture et écriture |
| `tests/UseCase/CommanderLivrePapierTest.php` | Le scénario, dont le test sur la fonction impure |
| `tests/Adapter/ErrorPresentationTest.php` | Une exception, trois rendus |
| `tests/Adapter/UserErrorMessageContractTest.php` | Le cœur ne formule jamais de phrase |

> Pourquoi `IdentifiantLivre` et non un `ItemId` générique partagé avec `IdentifiantEbook` : un e-book et un
> livre papier n'obéissent pas aux mêmes règles. L'un est duplicable à l'infini, l'autre a
> un stock fini. Les confondre reviendrait à perdre cette distinction dans le typage, et à
> la voir ressurgir en `if` un peu partout.
