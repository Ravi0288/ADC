# nal-adc-support NCBI harvest

This ncbi harvest use cypress to download files from NIH website, click buttons to download all search results in a xml file stored in cache/cypress/downloads folder

## Usage

1. Install the required dependencies by running the following command:

``` shell
npm install cypress
cd ncbi/cache
npm install
```
2. Run the following command to cache the file:

```shell
cd ncbi/cache
npx cypress run
python split_files.py
```

3. Run the following command to migrate file and push to figshare on stage:

```shell
cd ncbi/migrate
php harvest.php stage
```
4. Run the following command to migrate file and push to figshare on prod:

```shell
cd ncbi/migrate
php harvest.php prod
```

5. Log events are stored in migrate/logs folder

