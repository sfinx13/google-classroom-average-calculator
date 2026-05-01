# Google Classroom Average Calculator

Ce projet répond à plusieurs limites fonctionnelles de Google Classroom dans le cadre du suivi pédagogique et de l’analyse des performances des élèves.

En particulier :

- **Absence de périodes pédagogiques personnalisables** : Google Classroom ne permet pas de définir des périodes d’évaluation (par exemple : trimestre, semestre ou période libre) basées sur une plage de dates. Cela limite la capacité à analyser les résultats sur des segments temporels cohérents.
- **Manque de flexibilité dans le calcul des moyennes** : Les moyennes sont généralement exprimées en pourcentage ou dépendantes de la configuration des devoirs, ce qui ne correspond pas toujours aux systèmes de notation utilisés (ex : notes sur 10, 20, 40, etc.).
- **Absence de normalisation des résultats** : Il n’existe pas de mécanisme natif permettant d’agréger et de normaliser les notes provenant de devoirs hétérogènes afin de produire une moyenne cohérente et comparable.

Ce projet apporte une couche de traitement permettant :
- de définir des périodes d’analyse personnalisées
- de normaliser les notes
- de calculer des moyennes adaptées au contexte pédagogique

## 🚀 Technologies utilisées

*   **PHP 8.4** 
*   **Symfony 8.x**
*   **MySQL 8.0**
*   **Docker & Docker Compose**
*   **Google Classroom API** (via `google/apiclient`)
*   **PHPUnit** (pour les tests)

## 🛠 Installation et Configuration

### 1. Pré-requis
*   Docker et Docker Compose installés.
*   Un compte Google avec accès à Google Classroom.

### 2. Clonage et initialisation
```bash
# Lancer les conteneurs (si vous utilisez Docker)
docker compose up -d
```

# Installer les dépendances
```
composer install

# Créer la base de données et lancer les migrations
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
```

### 3. Configuration de l'API Google (Crucial)
Pour que l'application puisse communiquer avec Google, vous devez configurer vos identifiants dans le fichier `.env`.

#### A. Obtenir le Client ID et le Client Secret
1.  Allez sur la [Console Google Cloud](https://console.cloud.google.com/).
2.  Créez un nouveau projet.
3.  Activez l'API **Google Classroom**.
4.  Configurez l'écran de consentement OAuth (choisissez "Externe" ou "Interne" selon vos besoins).
    *   Ajoutez le scope : `https://www.googleapis.com/auth/classroom.courses.readonly`
    *   Ajoutez le scope : `https://www.googleapis.com/auth/classroom.coursework.me.readonly`
    *   Ajoutez le scope : `https://www.googleapis.com/auth/classroom.coursework.students.readonly`
    *   Ajoutez le scope : `https://www.googleapis.com/auth/classroom.topics.readonly`
    *   Ajoutez le scope : `https://www.googleapis.com/auth/classroom.rosters.readonly`
5.  Allez dans **Identifiants** > **Créer des identifiants** > **ID de client OAuth**.
6.  Sélectionnez **Application de bureau**.
7.  Copiez votre `Client ID` et votre `Client Secret` dans votre fichier `.env` :
    ```env
    GOOGLE_CLIENT_ID=votre_id
    GOOGLE_CLIENT_SECRET=votre_secret
    ```

#### B. Générer le Refresh Token
Une fois le ID et Secret configurés, utilisez la commande intégrée pour obtenir votre token de rafraîchissement permanent :

```bash
php bin/console app:google:generate-refresh-token
```

1.  Ouvrez l'URL affichée dans votre navigateur.
2.  Connectez-vous et validez les autorisations.
3.  Copiez le code fourni par Google et collez-le dans votre terminal.
4.  Copiez le `Refresh Token` obtenu et ajoutez-le à votre `.env` :
    ```env
    GOOGLE_REFRESH_TOKEN=votre_refresh_token
    ```

## 📖 Commandes disponibles

### 🏫 Gestion des classes et élèves

#### Lister les cours
Affiche la liste de tous vos cours Google Classroom avec leurs IDs.
```bash
php bin/console app:classroom:list-courses
```

#### Lister les élèves d'un cours
Affiche les élèves d'un cours spécifique et les enregistre/met à jour en base de données locale.
```bash
php bin/console app:classroom:list-students [courseId]
```
*   `courseId` : L'ID du cours (ex: 809867299753).

### 📊 Calcul des moyennes

#### Calculer la moyenne d'un élève
Calcule la moyenne par matière et la moyenne générale.
```bash
php bin/console app:classroom:compute-average "Nom de l'élève" --courseId=[ID]
```

**Options :**
*   `--courseId` : ID du cours Google Classroom.
*   `--start-date` : Date de début (format Y-m-d, ex: 2025-01-01).
*   `--end-date` : Date de fin (format Y-m-d, ex: 2025-03-31).

> **Note sur le cache :** Si un calcul a déjà été effectué pour un élève sur la même période, l'application récupérera le résultat en base de données au lieu d'appeler l'API Google.

## 🧮 Barèmes appliqués
Les moyennes sont calculées selon les barèmes spécifiques suivants :
*   **Lecture** : /20
*   **Grammaire** : /40
*   **Dictée** : /10
*   **Qissas** : /35
*   **Traduction** : /10
*   **Conjugaison** : /20
*   **Vocabulaire** : /20
*   **Devoir** : /10

La moyenne générale est calculée en normalisant chaque matière sur 20.

## 🧪 Tests
Pour lancer les tests unitaires :
```bash
vendor/bin/phpunit
```
