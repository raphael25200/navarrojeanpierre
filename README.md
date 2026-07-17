![Aperçu du site](https://github.com/raphael25200/navarrojeanpierre/raw/main/public/screenshots/home.png)

# Site vitrine – Navarro Jean-Pierre

## Description

Application web développée avec **Symfony** permettant de présenter et valoriser l'œuvre du peintre Jean-Pierre Navarro : plus de 300 tableaux (paysages, portraits, natures mortes) présentés à travers une galerie publique optimisée pour le référencement naturel.

Le projet comprend une interface publique ainsi qu'un espace d'administration permettant de gérer les contenus (tableaux, catégories, commentaires, slider), avec génération assistée par IA des descriptions et métadonnées des œuvres.

Développé dans le cadre d'un projet professionnel (diplôme de développeur web), le site est en **production** et continue d'être enrichi régulièrement.

> Projet présenté et validé dans le cadre du Titre Professionnel Développeur Web et Web Mobile (Formagraph Design, Besançon, 2025).

---

## Démo

<https://www.navarrojeanpierre.com>

---

## Fonctionnalités

### Public

- Galerie d'œuvres avec recherche et filtres (titre, catégorie, année, disponibilité, orientation)
- Pages œuvres individuelles dédiées (`/oeuvre/{slug}`) : fil d'Ariane, navigation précédente/suivante par date, œuvres similaires (maillage interne)
- Page d'accueil avec slider des œuvres phares
- Mosaïque en effet Masonry avec lightbox (zoom, plein écran, informations détaillées, navigation entre œuvres)
- Formulaire de contact
- Système de commentaires/avis sur les œuvres (AJAX, sans rechargement de page), avec modération

### SEO & performance

- Sitemap XML dynamique, robots.txt
- Données structurées Schema.org (`VisualArtwork`, `BreadcrumbList`) validées via l'outil de test des résultats enrichis Google
- Meta descriptions et balises title optimisées par page
- Canonicalisation www (redirection 301) et balises canonical auto-référentes
- Images servies en WebP avec fallback JPEG (`<picture>`), variante haute définition dédiée aux pages œuvres, préservation d'une image source haute résolution pour l'indexation Google Images
- Protection anti-hotlinking sur les images

### Back-office

- Gestion des tableaux (CRUD), catégories, slider
- Modération des commentaires
- Génération assistée par IA (OpenAI GPT-4o-mini) des descriptions, mots-clés et attributs d'accessibilité des œuvres, via une interface de traitement par lots (sélection, barre de progression, reprise sans re-générer l'existant)
- Génération par lots des variantes d'images optimisées (WebP, haute définition)

---

## Modèle de données

Quatre entités principales structurent l'application :

- **Tableau** : cœur du modèle, centralise l'ensemble des informations d'une œuvre (titre, description, mots-clés, dimensions, disponibilité, slug, catégorie, images...). Alimente la galerie, les filtres de recherche et les fonctionnalités de gestion.
- **Category** : structure les thématiques du site (relation OneToMany avec Tableau), gérée depuis l'administration et chargée dynamiquement dans les formulaires.
- **User** : gère les comptes, l'authentification, la vérification d'e-mail et les rôles/permissions (accès à l'administration). Mots de passe hachés.
- **Avis** : commentaires des visiteurs sur les œuvres, avec validation obligatoire avant publication et notification à l'administrateur.

---

## Sécurité et authentification

- Inscription, connexion et vérification d'e-mail via le `SecurityController`
- Mots de passe hachés et stockés de façon sécurisée
- Rôles et accès différenciés (`ROLE_ADMIN`, utilisateur standard)
- Routes sensibles (`/admin/*`) protégées par annotation `#[IsGranted('ROLE_ADMIN')]`
- Protection CSRF sur toutes les actions de suppression ou modification
- Validation des formulaires côté serveur (`$form->isSubmitted() && $form->isValid()`) et contraintes `Assert` sur les données
- Limitation des tentatives de connexion pour prévenir les attaques par force brute

---

## Prérequis

- PHP >= 8.1
- Composer
- Node.js / npm
- MySQL
- Symfony CLI (optionnel)

---

## Installation

### 1. Cloner le projet

```
git clone https://github.com/raphael25200/navarrojeanpierre
cd navarrojeanpierre
```

### 2. Installer les dépendances PHP

```
composer install
```

### 3. Installer les dépendances front-end

```
npm install
npm run build
```

---

## Configuration

Créer un fichier `.env.local` à la racine du projet en se basant sur le fichier `.env.example`.

Configurer les variables suivantes :

```
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name"

# Mailer (désactivé par défaut)
MAILER_DSN=null://null

# API OpenAI
OPENAI_API_KEY=your_api_key
OPENAI_ORGANIZATION=
```

Adapter les valeurs selon votre environnement.

---

## Base de données

Créer la base de données et exécuter les migrations :

```
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

---

## Utilisation

Lancer le serveur de développement :

```
symfony serve
```

Accéder à l'application :
<http://localhost:8000>

---

## Gestion des contenus

Les œuvres et leurs images sont gérées via l'interface d'administration.

Les fichiers images sont importés lors de la création ou de la modification d'un tableau depuis le back-office ; les variantes optimisées (WebP, haute définition) ainsi que les descriptions et mots-clés peuvent être générées individuellement ou en lot via les interfaces dédiées.

---

## Structure du projet

- `src/` : logique applicative (controllers, services, entités, repositories)
- `templates/` : vues Twig
- `assets/` : fichiers JavaScript et SCSS (Webpack Encore)
- `public/` : point d'entrée, fichiers publics, sitemap, robots.txt
- `migrations/` : structure de la base de données

---

## Tests

Tests unitaires sur les routes et la validation des formulaires, pour sécuriser les actions critiques (authentification, gestion des tableaux).

---

## Améliorations en cours / prévues

- En-têtes de sécurité HTTP (Strict-Transport-Security, X-Frame-Options, etc.)
- Ajout de tests automatisés supplémentaires
- Refactorisation progressive des controllers vers une couche de services
- Complément des attributs d'accessibilité (`alt`) restants via le back-office
- Déploiement via Docker pour faciliter la portabilité

---

## Auteur

Raphaël Navarro
Développeur web Symfony
