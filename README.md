# WakeOnStorage Local

<img src="WakeOnStorage-FulltLogoBan.png" style="zoom:50%;" />

Ce projet fournit une petite API REST permettant d'allumer ou d'éteindre des services de stockage à distance et d'en connaître l'état. Il n'utilise aucun framework PHP externe et peut être déployé sur un serveur web léger comme lighttpd.

## Installation

1. Clonez le dépôt sous `/opt/wakeOnStorage-local`.
2. Générez l'autoload Composer :

```bash
composer dump-autoload --no-dev
```

3. Configurez vos services dans `config/services.yaml` et l'authentification dans `config/app.yaml`.
4. Publiez le dossier `public/` sur `/api` de votre serveur web.

## Configuration

Le fichier `config/app.yaml` gère l'authentification :

```yaml
auth:
  token: mysecrettoken       # Jeton Bearer requis pour l'API
  allowed_ips:
    - 127.0.0.1             # Liste des IP autorisées (laisser vide pour aucune restriction)
```

Le fichier `config/services.yaml` déclare les services disponibles et les commandes à exécuter :

```yaml
services:
  nas:
    type: relai
    commands:
      up:
        - touch /tmp/backup
      down:
        - ssh root@192.168.1.2 "shutdown -h now"
        - touch /tmp/backup
      status:
        - ssh root@192.168.1.2 "LACOMMANDE"
  pi0ddusb1:
    type: uhubctl
    commands:
      up:
        - uhubctl -l 1-1 -p 4 -a on
        - mount /dev/xxxxx /mnt/xxxxxx
      down:
        - umount /mnt/xxxxxx
        - uhubctl -l 1-1 -p 4 -a off
      status:
        - "echo 'check status'"
```

Les commandes sont exécutées via le script `bin/service` qui peut être appelé avec `sudo` par l'API.

## Utilisation de l'API

### Liste des services

```http
GET /services
```

Réponse : liste des services configurés.

### État d'un service

```http
GET /{service}/status
```

### Allumer ou éteindre un service

```http
POST /{service}/up
POST /{service}/down
```

### Exécuter la commande `status` (POST)

```http
POST /{service}/status
```

Chaque commande retourne un JSON de succès ou d'erreur.

## Authentification

Toutes les requêtes doivent contenir l'en-tête HTTP :

```
Authorization: Bearer <token>
```

Le jeton est celui défini dans `config/app.yaml`.

## Documentation OpenAPI

Une description de l'API est disponible dans `docs/openapi.yaml`.

## Lancement depuis la ligne de commande

Le script `bin/service` peut être utilisé directement :

```bash
sudo php /opt/wakeOnStorage-local/bin/service nas up
```

