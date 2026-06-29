# Architecture des Engines

**Statut :** Brouillon v1.0

DBG Platform est organisée en moteurs métier indépendants.

## Identity Engine

Gère les Organisations, les utilisateurs, les rôles, les permissions et les journaux d'activité.

## Project Engine

Gère les espaces de travail, les projets, les contextes et l'historique.

## Asset Engine

Gère les logos, BAT, documents, produits, modèles, versions et relations entre actifs.

## Commerce Engine

Gère le catalogue, l'assistant de création, le panier, le prix, le paiement et la préparation commerciale.

## Production Engine

Gère les travaux de production, les statuts, les délais, les contrôles qualité et la livraison.

## Workflow Engine

Gère les événements, les automatisations, les validations, les notifications et les changements d'état.

## Analytics Engine

Gère les tableaux de bord, indicateurs et rapports.

## Règle

Les moteurs communiquent par événements et contrats. Ils ne doivent pas être fortement couplés.
