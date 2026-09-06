# language: fr
Fonctionnalité: Commander chez Bookshelf

  Ces scénarios sont écrits une seule fois et exécutés DEUX fois, contre deux
  adaptateurs différents :

    composer behat            les deux suites
    composer behat:hexagone   appels directs a l'hexagone, en memoire, quelques ms
    composer behat:http       vraies requetes HTTP contre un serveur, quelques secondes

  Ils ne parlent ni de HTTP, ni de base de donnees, ni de PHP. C'est ce qui leur permet
  de survivre a une refonte complete de l'infrastructure, et c'est aussi ce qui les rend
  lisibles par quelqu'un qui ne programme pas.

  Scénario: le catalogue expose les titres en vente
    Étant donné un e-book "Architecture hexagonale" à 2500 centimes en vente
    Quand je consulte le catalogue
    Alors le catalogue contient "Architecture hexagonale" au prix affiché "25,00 €"

  Scénario: un titre retiré de la vente n'apparaît plus au catalogue
    Étant donné un e-book "Architecture hexagonale" à 2500 centimes en vente
    Et un e-book "Brouillon" à 1000 centimes retiré de la vente
    Quand je consulte le catalogue
    Alors le catalogue compte 1 titre

  Scénario: commander un e-book
    Étant donné un e-book "Architecture hexagonale" à 2500 centimes en vente
    Quand je commande 2 exemplaires de cet e-book
    Alors la commande est acceptée

  Scénario: on ne vend pas un livre papier qu'on n'a plus
    Étant donné un livre papier avec 2 exemplaires en stock
    Quand je commande 3 exemplaires de ce livre papier
    Alors l'opération est refusée avec le code "order.insufficient_stock"
