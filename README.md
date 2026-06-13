<p align="center">
  <img src="public/screenshots/home.png" alt="Aperçu du site" width="700">
</p>

# Site vitrine – Navarro Jean-Pierre

## Description

Application web développée avec Symfony permettant de présenter les œuvres d’un peintre.

Le projet comprend une interface publique ainsi qu’un espace d’administration permettant de gérer les contenus (tableaux, catégories, commentaires, slider).

---

## Démo

https://navarrojeanpierre.com

---

## Fonctionnalités

- Affichage des œuvres  
- Navigation par catégories  
- Page d’accueil avec slider  
- Formulaire de contact  

### Back-office

- Gestion des tableaux (CRUD)  
- Gestion des catégories  
- Modération des commentaires  
- Gestion du slider  
- Génération de contenu via IA  

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
git clone https://github.com/raphael25200/navarrojeanpierre
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

Accéder à l’application :  
http://localhost:8000

---

## Gestion des contenus

Les œuvres et leurs images sont gérées via l’interface d’administration.

Les fichiers images sont importés lors de la création ou de la modification d’un tableau depuis le back-office.

---

## Structure du projet

- `src/` : logique applicative (controllers, services, entités)  
- `templates/` : vues Twig  
- `assets/` : fichiers JavaScript et CSS  
- `public/` : point d’entrée et fichiers publics  
- `migrations/` : structure de la base de données  

---

## Améliorations possibles

- Refactorisation des controllers  
- Amélioration de l’architecture (services)  
- Ajout de tests automatisés  
- Optimisation des performances  

---

## Auteur

Raphaël Navarro  
Développeur web Symfony
