<p align="center">
  <img src="public/screenshots/home.png" alt="Aperçu du site" width="700">
</p>

# Site vitrine – Navarro Jean-Pierre

## Description

Application web développée avec Symfony permettant de présenter les œuvres de Jean-Pierre Navarro, peintre impressionniste autodidacte.

Le site expose plus de 300 tableaux organisés par catégories, avec des pages individuelles optimisées pour le référencement. Il comprend une interface publique ainsi qu'un espace d'administration permettant de gérer les contenus.

---

## Démo

https://navarrojeanpierre.com

---

## Fonctionnalités

- Galerie d'œuvres avec mosaïque et lightbox
- Navigation par catégories (nature morte, portrait, paysage, abstraction)
- Page individuelle par œuvre avec données structurées Schema.org
- Slider dynamique en page d'accueil
- Formulaire de contact avec reCAPTCHA v3
- Page biographie de l'artiste
- Système de commentaires avec modération

### Back-office

- Gestion des tableaux (CRUD)
- Gestion des catégories
- Modération des commentaires
- Gestion du slider
- Génération de contenu via IA (description, mots-clés, aria-label)
- Traitement IA par lot avec interface dédiée

### SEO

- Sitemap XML dynamique
- Balises title et meta description optimisées
- Données structurées Schema.org (VisualArtwork)
- URLs propres avec slugs
- Maillage interne automatique

---

## Captures d'écran

<p align="center">
  <img src="public/screenshots/galerie.png" alt="Galerie des œuvres" width="700">
</p>

<p align="center">
  <img src="public/screenshots/oeuvre.png" alt="Page œuvre" width="700">
</p>

---

## Stack technique

- **Back-end** : PHP 8.x, Symfony 6.x
- **Front-end** : Twig, SCSS, Bootstrap 5.3, Webpack Encore
- **Base de données** : MySQL 8
- **Upload d'images** : VichUploaderBundle
- **IA** : API OpenAI (GPT-4o-mini)
- **Hébergement** : OVH mutualisé
- **Déploiement** : FTP (FileZilla)

---

## Prérequis

- PHP >= 8.x
- Composer
- Node.js / npm
- MySQL
- Symfony CLI (optionnel)

---

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/raphael25200/navarrojeanpierre.git
cd navarrojeanpierre
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances front-end

```bash
npm install
npm run build
```

---

## Configuration

Créer un fichier `.env.local` à la racine du projet en se basant sur le fichier `.env.example`.

Configurer les variables suivantes :

```env
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/db_name"

# Mailer (désactivé par défaut)
MAILER_DSN=null://null

# API OpenAI
OPENAI_API_KEY=your_api_key
OPENAI_ORGANIZATION=

# reCAPTCHA v3
RECAPTCHA3_KEY=your_key
RECAPTCHA3_SECRET=your_secret
```

Adapter les valeurs selon votre environnement.

---

## Base de données

Créer la base de données et exécuter les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

---

## Utilisation

Lancer le serveur de développement :

```bash
symfony serve
```

Accéder à l'application :
http://localhost:8000

---

## Gestion des contenus

Les œuvres et leurs images sont gérées via l'interface d'administration.

Les fichiers images sont importés lors de la création ou de la modification d'un tableau depuis le back-office.

---

## Intégration IA

Le site intègre l'API OpenAI (GPT-4o-mini) pour analyser les peintures et générer automatiquement :

- une description factuelle optimisée pour le SEO
- une liste de 10 à 15 mots-clés classés par pertinence
- un aria-label pour l'accessibilité

Le prompt est conçu pour décrire ce qui est visible sans utiliser de vocabulaire de critique d'art. Un traitement par lot est disponible depuis l'interface d'administration.

---

## Structure du projet

- `src/` : logique applicative (controllers, services, entités)
- `templates/` : vues Twig
- `assets/` : fichiers JavaScript et CSS
- `public/` : point d'entrée et fichiers publics
- `migrations/` : structure de la base de données

---

## Auteur

Raphaël Navarro
Développeur web Symfony
