# ADR-002 — Core Entity: Organisation

**Status:** Accepted

## Context

DBG Platform doit fonctionner pour plusieurs types d'acteurs sans dupliquer l'architecture : entreprises, clubs, associations, collectivités et partenaires commerciaux.

## Decision

La notion centrale est **Organisation**.

Une Organisation peut représenter :

- une entreprise ;
- un club ;
- une association ;
- une collectivité ;
- un partenaire commercial ;
- une école ;
- une franchise.

## Consequences

Les espaces Entreprise, Club, Association ou Partenaire ne sont pas des systèmes séparés. Ce sont des variantes d'une même entité Organisation, pilotées par des types, rôles, paramètres et vues.

## Core structure

Organisation contient : Brands, Users, Projects, Assets, Product Library, Views, Orders, Workflows et Settings.
