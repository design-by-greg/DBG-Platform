# ADR-005 — Product Library

**Status:** Accepted

## Context

Le catalogue global contient des produits sources. Une fois personnalisés et validés, ces produits deviennent des actifs propres à l'Organisation.

## Decision

Chaque Organisation possède sa propre **Product Library**.

La Product Library contient :

- les produits validés ;
- les versions personnalisées ;
- les anciennes versions ;
- les variantes ;
- les BAT associés ;
- les fichiers associés ;
- les règles de réassort.

## Consequences

Le réassort ne repart jamais du catalogue global. Il repart d'un produit validé dans la bibliothèque privée de l'Organisation.

## Principle

Le catalogue sert à créer. La Product Library sert à réassortir et à gérer dans le temps.
