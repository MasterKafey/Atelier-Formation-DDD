# Atelier 1 : fiche de travail

Duree : 40 min en binomes, 20 min de restitution. **Paperboard et post-its, pas de code.**

Materiel : `entretien-camille.md`, le code de depart dans `legacy/`, et le schema de base
dans `legacy/schema.sql`.

---

## 1. Glossaire (15 min)

Dix a quinze termes. Signalez explicitement ceux ou plusieurs personnes ne disent pas la
meme chose.

| Terme metier | Definition en une phrase | Terme dans le code actuel | Synonyme / homonyme ? |
|---|---|---|---|
| | | | |

## 2. Sous-domaines (10 min)

**Contrainte : au moins un sous-domaine doit etre classe generique**, et vous devez
justifier de ne pas le developper.

| Sous-domaine | Coeur / Support / Generique | Pourquoi | Construire ou acheter ? |
|---|---|---|---|
| | | | |

## 3. Carte des contextes (10 min)

Dessinez les contextes bornes, les relations, le **sens amont / aval** de chacune, et le
pattern d'integration retenu (conformiste, couche anticorruption, client/fournisseur,
voies separees...).

Repere : le mot « commande » n'a pas le meme sens partout dans l'entretien. C'est votre
frontiere.

## 4. Classification en couches (5 min)

Pour un contexte au choix, classez ces six elements en Domaine / Application /
Infrastructure, avec **une justification par ligne**, appuyee sur l'une des deux regles
du code coeur.

| # | Element | Couche | Justification |
|---|---|---|---|
| 1 | la classe qui calcule le montant TTC d'une commande | | |
| 2 | la classe qui appelle vatapi.com | | |
| 3 | l'interface qui permet d'enregistrer une commande | | |
| 4 | le controleur qui traite le formulaire de commande | | |
| 5 | l'objet qui transporte les donnees du formulaire vers le metier | | |
| 6 | l'abonne qui envoie l'e-mail de confirmation | | |

---

## Criteres de reussite

- [ ] Au moins un homonyme identifie, avec les deux sens explicites
- [ ] Au moins un sous-domaine generique, avec une decision « acheter »
- [ ] Le sens amont / aval est indique sur chaque relation de la carte
- [ ] Les six elements sont classes, chacun justifie par l'une des deux regles

## Palier d'approfondissement

Proposez une **couche anticorruption** pour l'integration a l'API de TVA : quelle
interface, quel vocabulaire a l'interieur, quel vocabulaire a l'exterieur, et ou passe
exactement la frontiere.
