# DBG Platform — Project Atlas

**DBG Platform** est le socle produit de Design by Greg.

Ce dépôt centralise la documentation, l’architecture, le design system, les maquettes, le thème WordPress, le plugin Realisaprint V8, Base44, l’asset manager et la roadmap du projet.

## Vision

Design by Greg ne doit pas être seulement un site d’impression ou un revendeur Realisaprint.

La vision de DBG Platform est de devenir une plateforme capable de centraliser, organiser et produire toute la communication visuelle des entreprises, associations, clubs, particuliers et revendeurs.

## Principe fondateur

> Toute fonctionnalité doit simplifier la vie du client et/ou celle de l’équipe DBG.

## Architecture cible

```text
Client
  ↓
WordPress / WooCommerce
  ↓
Plugin DBG Realisaprint V8
  ↓
Base44 ERP / CRM / Production
  ↓
Asset Manager
  ↓
Production fournisseur / atelier DBG
  ↓
Réassort / boutique / historique client
```

## Structure du dépôt

```text
DBG-Platform/
├── docs/
│   ├── product-blueprint/
│   ├── business-rules/
│   ├── architecture/
│   ├── ux/
│   ├── ui/
│   ├── api/
│   └── roadmap/
├── design-system/
├── wordpress-theme/
├── plugin-realisaprint-v8/
├── base44/
├── asset-manager/
├── scripts/
├── changelog/
└── README.md
```

## Règles d’architecture

1. Le client ne voit jamais la complexité technique.
2. Le thème affiche ; le plugin traite ; Base44 orchestre.
3. Le plugin ne contient pas de design.
4. Le thème ne contient pas de logique métier fournisseur.
5. Chaque commande validée doit pouvoir devenir un réassort.
6. Le patrimoine graphique est un actif client.
7. La plateforme doit rester multi-fournisseurs.
8. Les templates fournisseurs restent internes.
9. Une donnée doit avoir une seule source de vérité.
10. Toute évolution importante doit être documentée avant développement.

## Modules principaux

- **Commerce** : site public, catalogue, WooCommerce, panier, paiement.
- **Realisaprint Connector** : synchronisation produits, catégories, images, prix, délais, commandes.
- **Base44** : CRM, ERP, BAT, production, suivi client, automatisations.
- **Assets** : logos, fichiers sources, BAT, templates, produits validés, réassorts.
- **Boutiques** : entreprises, clubs, associations, revendeurs.
- **Design System** : composants UI, règles visuelles, responsive.

## Priorité actuelle

Sprint 1 — Product Blueprint.

Objectif : documenter la vision, l’architecture, les règles métier et la roadmap avant de développer le thème ou le plugin.

## Statut

Projet lancé sous le nom interne **Project Atlas**.
