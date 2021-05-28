composer install
npm install
npm run dev

# Installation in project
composer require senna/datatable
# Link assets
pa senna:link assets

# Publish config
```
php artisan vendor:publish --provider="Senna\Datatable\DatatableServiceProvider" --tag="config"
```

# Publish migrations
```
php artisan vendor:publish --provider="Senna\Datatable\DatatableServiceProvider" --tag="migrations"
```

```
pa migrate
```

# publish assets to symlinked senna assets dir
```
pa senna:link assets
```

