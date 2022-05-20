# Projet le Blog de Batman

## Installation

```
git clone https://github.com/jgilot/leblogdebatman.git
```

### Modifier les paramètres d'environnement dans le fichier .env pour les faire correspondre à votre environnement(Accès a la base de données, clés google recaptcha)

```
#Accès base de données a modifier
DATABASE_URL="mysql://root:@127.0.0.1:3306/leblogdebatman?serverVersion=5.7&charset=utf8mb4"

#Clés Google recaptcha a modifier
GOOGLE_RECAPTCHA_SITE_KEY= XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
GOOGLE_RECAPTCHA_PRIVATE_KEY= XXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

### Déplacer le terminal dans le dossier cloné du projet
```
cd leblogdebatman
```

### Taper les commandes suivantes :
```
composer install
symfony console doctrine:database:create
symfony console make:migration
symfony console doctrine:migration:migrate
symfony console doctrine:fixtures:load
symfony console assets:install public
```

Les fixtures créeront :
* Un compte admin ( email: admin@a.a, mot de passe : Azerty7/ )
* 10 comptes utilisateurs ( emails aléatoires, mot de passe : Azerty7/  )
* 200 articles
* Entre 0 et 10 commentaires par article

### Démarrer le serveur Symfony :
```
symfony serve
```