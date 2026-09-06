# Atelier 2 : fiche de travail

Duree : 45 min en binomes. Branche `atelier-2`.

```bash
git checkout atelier-2
composer test          # 17 echecs : c'est le point de depart
```

## Ce qui est fourni

| Fichier | Pourquoi c'est fourni |
|---|---|
| `IdentifiantEbook` | Modele de value object d'identite. `IdentifiantCommande` a la meme forme. |
| `CodePays`, `ReferenceDePaiement` | Aucune regle metier a ecrire |
| `EtatCommande` | L'enum et le diagramme de la machine a etats |
| `CommandeRepository` | L'interface du port sortant |
| `CommandePassee`, `CommandePayee`, `CommandeAnnulee` | De simples objets immuables |
| Les exceptions | Constructeurs nommes, deja ecrits |
| `CommandeBuilder` | Le builder utilise par les tests |
| Les 17 tests | C'est votre cahier des charges |

## Les 8 classes a ecrire

1. `Domain\Model\Commande\IdentifiantCommande`
2. `Domain\Model\Common\AdresseEmail`
3. `Domain\Model\Commande\Quantite`
4. `Domain\Model\Common\TauxDeTva`
5. `Domain\Model\Common\Montant`
6. `Domain\Model\Commande\LigneDeCommande`
7. `Domain\Model\Commande\Commande`
8. `Infrastructure\InMemory\CommandeRepositoryEnMemoire`

Chacune porte un bloc de commentaire qui dit ce qu'elle doit garantir. Les corps de
methode levent `RuntimeException('TODO atelier 2')` : remplacez-les.

## Contraintes

- Aucun setter. Aucune methode dont le nom commence par `set`.
- Aucun getter sur `Commande` autre que `identifiantCommande()`, `relacherEvenements()` et les deux totaux.
- Aucun `if` portant sur l'etat de `Commande` en dehors de la classe `Commande`.
- Aucune dependance vers `Doctrine`, `Symfony\Component\HttpFoundation` ou `PDO`
  dans `src/Domain`.

## Criteres de reussite

- [ ] Les 17 tests fournis passent
- [ ] Au moins 4 tests supplementaires sur des transitions interdites
- [ ] Aucun setter, aucun getter superflu
- [ ] La suite `domain` s'execute sans base de donnees
- [ ] `payer()` refuse une commande jamais confirmee, et `ajouterLigne()` une commande confirmee
- [ ] `$commande->annuler()` sur une commande payee leve `AnnulationImpossible`, avec un
      message qui nomme la commande

## Piege frequent

`Montant::multipliePar()` ecrit en modifiant `$this`. Un value object est **immuable** :
chaque operation retourne une nouvelle instance. Le test
`le_montant_d_origine_n_est_pas_modifie` est la pour ca.

## Paliers d'approfondissement

**Palier A : la table des transitions.** Quatre etats, quatre actions : seize cases, chacune
permise ou interdite. Dessinez la table au tableau, puis ecrivez UN test parametre qui la
parcourt entierement, avec un fournisseur de donnees. Les tests fournis n'en couvrent qu'un peu
plus de la moitie ; les cases restantes tiennent aujourd'hui par construction, pas par un test,
et c'est exactement comme ca qu'une regle se perd a la refonte suivante. Une case merite
discussion : faut-il vraiment interdire d'annuler une commande deja annulee, ou l'accepter sans
rien reemettre ?

**Palier B : le service applicatif.** Ecrivez `PasserCommandeService` et le DTO
`PasserCommande`, avec `depuisDonneesRequete()` qui gere les cles absentes et le transtypage.
Testez-le avec `CommandeRepositoryEnMemoire`. (Vous le retrouverez tout fait a l'atelier 3.)

**Palier C : Doctrine.** Ecrivez le type `IdentifiantCommandeType`, le mapping par attributs de
`Commande` et `LigneDeCommande`, et la migration. Verifiez que `Commande.php` ne reference rien
d'autre que `Doctrine\ORM\Mapping`.
