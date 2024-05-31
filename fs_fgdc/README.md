# nal-adc-support FS FGDC harvest

This FS FGDC harvest uses OAI-PMH service to request data.

## Usage

1. Run the following command to cache the file:

```shell
cd fs_fgdc/cache
php -f cache.php stage #to cache file for staging
php -f cache.php prod #to cache file for production
```

3. Run the following command to read file from local directory and push to figshare on stage:

```shell
cd fs_fgdc/migrate
php harvest.php stage
```
4. Run the following command to migrate file from local directory and push to figshare on prod:

```shell
cd fs_fgdc/migrate
php harvest.php prod
```

5. Log events are stored in migrate/logs folder

