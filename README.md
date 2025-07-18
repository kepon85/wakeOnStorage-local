# WakeOnStorage Local

<img src="WakeOnStorage-FulltLogoBan.png" style="zoom:50%;" />

Ce projet fournit une petite API REST permettant d'allumer ou d'éteindre des services de stockage à distance et d'en connaître l'état. Il n'utilise aucun framework PHP externe et peut être déployé sur un serveur web léger comme lighttpd.

## Installation

1. Clonez le dépôt sous `/opt/wakeOnStorage-local`.
2. Générez l'autoload Composer :

```bash
composer dump-autoload --no-dev
```

3. Copiez le dossier `config_dist` en `config` puis éditez `config/app.yaml` et `config/services.yaml`.
4. Publiez le dossier `public/` sur `/api` de votre serveur web.
5. Ajoutez une règle sudoers permettant à `www-data` d'exécuter `bin/service` en sudo :

```bash
www-data ALL=(root) NOPASSWD: /opt/wakeOnStorage-local/bin/service
```

## Configuration

Le fichier `config/app.yaml` gère l'authentification :

```yaml
auth:
  token: mysecrettoken       # Jeton Bearer requis pour l'API
  allowed_ips:
    - 127.0.0.1             # Liste des IP autorisées (laisser vide pour aucune restriction)
log:
  file: /var/log/wakeonstorage.log
  level: 3                # 0 rien, 1 erreur, 2 warning, 3 info, 4 debug
  max_size: 1048576       # Rotation à 1 Mo
base_path: /api           # Prefix de l'URL à ignorer
sudo_path: /usr/bin/sudo
service_script: /usr/bin/php /opt/wakeOnStorage-local/bin/service
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
      count: ssh root@192.168.1.2 "..."
      status: ssh root@192.168.1.2 "echo status"
  pi0ddusb1:
    type: uhubctl
    commands:
      up:
        - uhubctl -l 1-1 -p 4 -a on
        - mount /dev/xxxxx /mnt/xxxxxx
      down:
        - umount /mnt/xxxxxx
        - uhubctl -l 1-1 -p 4 -a off
      count: lsof +D /mnt/xxxxxx | wc -l
      status: "echo check"
```

Les commandes sont exécutées via le script `bin/service` qui peut être appelé avec `sudo` par l'API.

## Utilisation de l'API

### Liste des services

```http
GET /services
```

Réponse : liste des services configurés.

Exemple d'appel avec `curl` :

```bash
curl -s -X GET -H "Authorization: Bearer mysecrettoken" http://127.0.0.1:52000/api/services
```

### État d'un service

```http
GET /{service}/status
GET /{service}/count
```

### Allumer ou éteindre un service

```http
POST /{service}/up
POST /{service}/down
POST /{service}/down-force
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

## Exemple de configuration lighttpd

```lighttpd
server.modules += ( "mod_alias", "mod_cgi", "mod_rewrite" )

# 1) API PHP sous /api → répertoire /opt/wakeOnStore-local, sans listing, ni auth
$HTTP["url"] =~ "^/api($|/)" {
    alias.url += (
        "/api" => "/opt/wakeOnStore-local/public"
    )
    url.rewrite-if-not-file = (
        "^/api/(.*)$" => "/api/index.php"
    )
    dir-listing.activate = "disable"
    auth.require = ( )
    cgi.assign = ( ".php" => "/usr/bin/php-cgi" )
}
```

## Lancement depuis la ligne de commande

Le script `bin/service` peut être utilisé directement :

```bash
sudo php /opt/wakeOnStorage-local/bin/service nas up
```

