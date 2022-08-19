<?php

namespace Senna\Datatable;

use Carbon\Laravel\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Compilers\BladeCompiler;
use Senna\Datatable\Admin\DatatableAdmin;
use Livewire\Livewire;
use Senna\Admin\MainMenu;


class DatatableServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/senna.datatable.php', 'senna.datatable');

        if (class_exists(Livewire::class)) {
            Livewire::component('senna.datatable.livewire.admin.datatable-admin', DatatableAdmin::class);
            // Livewire::component('senna.datatable-manager', DatatableManager::class);
        }
    }

    public function boot() {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'senna.datatable');
        $this->configurePublishing();
        $this->configureRoutes();
        $this->configureComponents();

        Route::matched(function()
        {
            MainMenu::register("datatable", "Datatable", route('senna.datatable'), "cms", false);
        });
    }

    public function configurePublishing() {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/senna.datatable.php' => config_path('senna.datatable.php'),
            ], 'config');

            // Export the migrations
            if (! class_exists('CreateSennaDatatableMigrations')) {
                $dir = __DIR__ . '/../database/migrations/create_senna_datatable_migrations.stub.php' ;
                $this->publishes([
                    $dir => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_senna_datatable_migrations.php'),
                    // you can add any number of migrations here
                ], 'migrations');
            }
        }
    }

    public function configureRoutes() {
        Route::group([
            'middleware' => config('senna.admin_middleware'),
            'prefix' => config('senna.prefix')
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/datatable.php');
        });
    }

    protected function configureComponents()
    {
        $this->callAfterResolving(BladeCompiler::class, function () {
            foreach (File::allFiles(__DIR__ . "/../resources/views/components") as $file) {
                $component = $file->getRelativePathname();
                $component = str_replace(".blade.php", "", $component);
                $component = str_replace("/", ".", $component);

                $this->registerComponent($component);
            }
        });
    }

    /**
     * Register the given component.
     *
     * @param  string  $component
     * @return void
     */
    protected function registerComponent(string $component)
    {
        Blade::component('senna.datatable::components.' . $component, 'senna.datatable.' . $component);
    }
}