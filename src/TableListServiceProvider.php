<?php

/*
 * This file is part of Softerize TableList
 *
 * (c) Oscar Dias <oscar.dias@softerize.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Softerize\TableList;

use Illuminate\Support\ServiceProvider;

class TableListServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'table-list');
        $this->registerHelpers();
        $this->publishAssets();
    }

    /**
     * Publish datatables assets.
     */
    protected function publishAssets()
    {
        $this->publishes([
            __DIR__ . '/resources/views' => base_path('/resources/views/vendor/softerize/table-list'),
        ], 'table-list');
    }

    /**
     * Register helpers files
     */
    public function registerHelpers()
    {
        // Load the helpers in Helpers/helpers.php
        if (file_exists(__DIR__ . '/Helpers/helpers.php'))
        {
            require __DIR__ . '/Helpers/helpers.php';
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
