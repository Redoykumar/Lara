<?php

namespace InfyOm\Generator\Common;

use Illuminate\Support\Str;

class GeneratorConfig
{
    /* Namespace variables */
    public static $availableOptions = [
        'fieldsFile',
        'jsonFromGUI',
        'tableName',
        'fromTable',
        'save',
        'primary',
        'prefix',
        'paginate',
        'skip',
        'datatables',
        'views',
    ];
    public $nsApp;
    public $nsRepository;
    public $nsModel;
    public $nsDataTables;
    public $nsModelExtend;
    public $nsApiController;
    public $nsApiRequest;
    public $nsRequest;
    public $nsRequestBase;
    public $nsController;

    /* Path variables */
    public $nsBaseController;
    public $pathRepository;
    public $pathModel;
    public $pathDataTables;
    public $pathApiController;
    public $pathApiRequest;
    public $pathApiRoutes;
    public $pathApiTests;
    public $pathApiTestTraits;
    public $pathController;
    public $pathRequest;
    public $pathRoutes;

    /* Model Names */
    public $pathViews;
    public $mName;
    public $iconName;
    public $iconColor;
    public $mPlural;
    public $mCamel;
    public $mCamelPlural;
    public $mSnake;
    public $mSnakePlural;

    /* Generator Options */
    public $forceMigrate;

    /* Prefixes */
    public $options;

    /* Command Options */
    public $prefixes;
    public $tableName;

    /* Generator AddOns */
    public $addOns;

    protected $primaryName;

    public function init(CommandData &$commandData, $options = null)
    {
        if (! empty($options)) {
            self::$availableOptions = $options;
        }

        $this->mName = $commandData->modelName;
        $this->prepareAddOns();
        $this->prepareOptions($commandData);
        $this->prepareModelNames();
        $this->preparePrefixes();
        $this->loadPaths();
        $this->prepareTableName();
        $this->prepareIconName();
        $this->prepareIconColor();
        $this->preparePrimaryName();
        $this->loadNamespaces($commandData);
        $commandData = $this->loadDynamicVariables($commandData);
    }

    public function prepareAddOns()
    {
        $this->addOns['swagger'] = config('infyom.laravel_generator.add_on.swagger', false);
        $this->addOns['tests'] = config('infyom.laravel_generator.add_on.tests', false);
        $this->addOns['datatables'] = config('infyom.laravel_generator.add_on.datatables', true);
        $this->addOns['menu.enabled'] = config('infyom.laravel_generator.add_on.menu.enabled', false);
        $this->addOns['menu.menu_file'] = config('infyom.laravel_generator.add_on.menu.menu_file', 'layouts.menu');
    }

    public function prepareOptions(CommandData &$commandData)
    {
        foreach (self::$availableOptions as $option) {
            $this->options[$option] = $commandData->commandObj->option($option);
        }

        if (isset($options['fromTable']) and $this->options['fromTable']) {
            if (! $this->options['tableName']) {
                $commandData->commandError('tableName required with fromTable option.');
                exit;
            }
        }

//        $this->options['softDelete'] = config('infyom.laravel_generator.options.softDelete', false);
        if (! empty($this->options['skip'])) {
            $this->options['skip'] = array_map('trim', explode(',', $this->options['skip']));
        }

        if (! empty($this->options['datatables'])) {
            if (strtolower($this->options['datatables']) === 'true') {
                $this->addOns['datatables'] = true;
            } else {
                $this->addOns['datatables'] = false;
            }
        }
    }

    public function prepareModelNames()
    {
        $this->mPlural = Str::plural($this->mName);
        $this->mCamel = Str::camel($this->mName);
        $this->mCamelPlural = Str::camel($this->mPlural);
        $this->mSnake = Str::snake($this->mName);
        $this->mSnakePlural = Str::snake($this->mPlural);
    }

    public function preparePrefixes()
    {
        $this->prefixes['route'] = explode('/', config('infyom.laravel_generator.prefixes.route', ''));
        $this->prefixes['path'] = explode('/', config('infyom.laravel_generator.prefixes.path', ''));
        $this->prefixes['view'] = explode('.', config('infyom.laravel_generator.prefixes.view', ''));
        $this->prefixes['public'] = explode('/', config('infyom.laravel_generator.prefixes.public', ''));
        $prefixexits = json_decode($this->options['jsonFromGUI'])->prefix;
        if (! empty($prefixexits)) {
            $multiplePrefixes = explode(',', $prefixexits);
            $this->prefixes['route'] = array_merge($this->prefixes['route'], $multiplePrefixes);
            $this->prefixes['path'] = array_merge($this->prefixes['path'], $multiplePrefixes);
            $this->prefixes['view'] = array_merge($this->prefixes['view'], $multiplePrefixes);
            $this->prefixes['public'] = array_merge($this->prefixes['public'], $multiplePrefixes);
        }

        $this->prefixes['route'] = array_diff($this->prefixes['route'], ['']);
        $this->prefixes['path'] = array_diff($this->prefixes['path'], ['']);
        $this->prefixes['view'] = array_diff($this->prefixes['view'], ['']);
        $this->prefixes['public'] = array_diff($this->prefixes['public'], ['']);

        $routePrefix = '';
        foreach ($this->prefixes['route'] as $singlePrefix) {
            $routePrefix .= Str::camel($singlePrefix) . '.';
        }

        if (! empty($routePrefix)) {
            $routePrefix = substr($routePrefix, 0, strlen($routePrefix) - 1);
        }

        $this->prefixes['route'] = $routePrefix;

        $nsPrefix = '';

        foreach ($this->prefixes['path'] as $singlePrefix) {
            $nsPrefix .= Str::title($singlePrefix) . '\\';
        }

        if (! empty($nsPrefix)) {
            $nsPrefix = substr($nsPrefix, 0, strlen($nsPrefix) - 1);
        }

        $this->prefixes['ns'] = $nsPrefix;

        $pathPrefix = '';

        foreach ($this->prefixes['path'] as $singlePrefix) {
            $pathPrefix .= Str::title($singlePrefix) . '/';
        }

        if (! empty($pathPrefix)) {
            $pathPrefix = substr($pathPrefix, 0, strlen($pathPrefix) - 1);
        }

        $this->prefixes['path'] = $pathPrefix;

        $viewPrefix = '';

        foreach ($this->prefixes['view'] as $singlePrefix) {
            $viewPrefix .= Str::camel($singlePrefix) . '/';
        }

        if (! empty($viewPrefix)) {
            $viewPrefix = substr($viewPrefix, 0, strlen($viewPrefix) - 1);
        }

        $this->prefixes['view'] = $viewPrefix;

        $publicPrefix = '';

        foreach ($this->prefixes['public'] as $singlePrefix) {
            $publicPrefix .= Str::camel($singlePrefix) . '/';
        }

        if (! empty($publicPrefix)) {
            $publicPrefix = substr($publicPrefix, 0, strlen($publicPrefix) - 1);
        }

        $this->prefixes['public'] = $publicPrefix;
    }

    public function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }

        return false;
    }

    public function loadPaths()
    {
        $prefix = $this->prefixes['path'];

        if (! empty($prefix)) {
            $prefix .= '/';
        }

        $viewPrefix = $this->prefixes['view'];

        if (! empty($viewPrefix)) {
            $viewPrefix .= '/';
        }

        $this->pathRepository = config(
            'infyom.laravel_generator.path.repository',
            app_path('Repositories/')
        ) . $prefix;

        $this->pathModel = config('infyom.laravel_generator.path.model', app_path('Models/')) . $prefix;

        $this->pathDataTables = config('infyom.laravel_generator.path.datatables', app_path('DataTables/')) . $prefix;

        $this->pathApiController = config(
            'infyom.laravel_generator.path.api_controller',
            app_path('Http/Controllers/API/')
        ) . $prefix;

        $this->pathApiRequest = config(
            'infyom.laravel_generator.path.api_request',
            app_path('Http/Requests/API/')
        ) . $prefix;

        $this->pathApiRoutes = config('infyom.laravel_generator.path.api_routes', base_path('routes/api_builder.php'));

        $this->pathApiTests = config('infyom.laravel_generator.path.api_test', base_path('tests/'));

        $this->pathApiTestTraits = config('infyom.laravel_generator.path.test_trait', base_path('tests/traits/'));

        $this->pathController = config(
            'infyom.laravel_generator.path.controller',
            app_path('Http/Controllers/Admin/')
        ) . $prefix;

        $this->pathRequest = config('infyom.laravel_generator.path.request', app_path('Http/Requests/')) . $prefix;

        $this->pathRoutes = config('infyom.laravel_generator.path.routes', base_path('routes/web_builder.php'));

        $this->pathViews = config(
            'infyom.laravel_generator.path.views',
            base_path('resources/views/')
        ) . $viewPrefix . $this->mCamelPlural . '/';
    }

    public function prepareTableName()
    {
        $tablexits = json_decode($this->options['jsonFromGUI'])->tableName;

        if ($tablexits !== '') {
            $this->tableName = $tablexits;
        } else {
            $this->tableName = $this->mSnakePlural;
        }
    }

    public function prepareIconName()
    {
        $icon = json_decode($this->options['jsonFromGUI'])->iconName;
        if ($icon !== '') {
            $this->iconName = $icon;
        } else {
            $this->iconName = 'home';
        }
    }

    public function prepareIconColor()
    {
        $iconColor = json_decode($this->options['jsonFromGUI'])->iconColor;
        if ($iconColor !== '') {
            $this->iconColor = $iconColor;
        } else {
            $this->iconColor = '#EF6F6C';
        }
    }

    public function preparePrimaryName()
    {
        $primary = json_decode($this->options['jsonFromGUI'])->primary;

        if (! empty($primary)) {
            $this->primaryName = $primary;
        } else {
            $this->primaryName = 'id';
        }
    }

    public function loadNamespaces(CommandData &$commandData)
    {
        $prefix = $this->prefixes['ns'];

        if (! empty($prefix)) {
            $prefix = '\\' . $prefix;
        }

        $this->nsApp = $commandData->commandObj->getLaravel()->getNamespace();
        $this->nsRepository = config('infyom.laravel_generator.namespace.repository', 'App\Repositories') . $prefix;
        $this->nsModel = config('infyom.laravel_generator.namespace.model', 'App\Models') . $prefix;
        $this->nsDataTables = config('infyom.laravel_generator.namespace.datatables', 'App\DataTables') . $prefix;
        $this->nsModelExtend = config(
            'infyom.laravel_generator.model_extend_class',
            'Illuminate\Database\Eloquent\Model'
        );

        $this->nsApiController = config(
            'infyom.laravel_generator.namespace.api_controller',
            'App\Http\Controllers\API'
        ) . $prefix;
        $this->nsApiRequest = config('infyom.laravel_generator.namespace.api_request', 'App\Http\Requests\API') . $prefix;

        $this->nsRequest = config('infyom.laravel_generator.namespace.request', 'App\Http\Requests') . $prefix;
        $this->nsRequestBase = config('infyom.laravel_generator.namespace.request', 'App\Http\Requests');
        $this->nsBaseController = config('infyom.laravel_generator.namespace.controller', 'App\Http\Controllers\Admin');
        $this->nsController = config('infyom.laravel_generator.namespace.controller', 'App\Http\Controllers\Admin') . $prefix;
    }

    public function loadDynamicVariables(CommandData &$commandData)
    {
        $commandData->addDynamicVariable('$NAMESPACE_APP$', $this->nsApp);
        $commandData->addDynamicVariable('$NAMESPACE_REPOSITORY$', $this->nsRepository);
        $commandData->addDynamicVariable('$NAMESPACE_MODEL$', $this->nsModel);
        $commandData->addDynamicVariable('$NAMESPACE_DATATABLES$', $this->nsDataTables);
        $commandData->addDynamicVariable('$NAMESPACE_MODEL_EXTEND$', $this->nsModelExtend);

        $commandData->addDynamicVariable('$NAMESPACE_API_CONTROLLER$', $this->nsApiController);
        $commandData->addDynamicVariable('$NAMESPACE_API_REQUEST$', $this->nsApiRequest);

        $commandData->addDynamicVariable('$NAMESPACE_BASE_CONTROLLER$', $this->nsBaseController);
        $commandData->addDynamicVariable('$NAMESPACE_CONTROLLER$', $this->nsController);
        $commandData->addDynamicVariable('$NAMESPACE_REQUEST$', $this->nsRequest);
        $commandData->addDynamicVariable('$NAMESPACE_REQUEST_BASE$', $this->nsRequestBase);
        $commandData->addDynamicVariable('$TABLE_NAME$', $this->tableName);
        $commandData->addDynamicVariable('$PRIMARY_KEY_NAME$', $this->primaryName);
        $commandData->addDynamicVariable('$MODEL_NAME$', $this->mName);
        $commandData->addDynamicVariable('$ICON_NAME$', $this->iconName);
        $commandData->addDynamicVariable('$ICON_COLOR$', $this->iconColor);
        $commandData->addDynamicVariable('$MODEL_NAME_CAMEL$', $this->mCamel);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL$', $this->mPlural);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL_CAMEL$', $this->mCamelPlural);
        $commandData->addDynamicVariable('$MODEL_NAME_SNAKE$', $this->mSnake);
        $commandData->addDynamicVariable('$MODEL_NAME_PLURAL_SNAKE$', $this->mSnakePlural);

        if (! empty($this->prefixes['route'])) {
            $commandData->addDynamicVariable('$ROUTE_NAMED_PREFIX$', $this->prefixes['route'] . '.');
            $commandData->addDynamicVariable('$ROUTE_PREFIX$', str_replace('.', '/', $this->prefixes['route']) . '/');
        } else {
            $commandData->addDynamicVariable('$ROUTE_PREFIX$', '');
            $commandData->addDynamicVariable('$ROUTE_NAMED_PREFIX$', '');
        }

        if (! empty($this->prefixes['ns'])) {
            $commandData->addDynamicVariable('$PATH_PREFIX$', $this->prefixes['ns'] . '\\');
        } else {
            $commandData->addDynamicVariable('$PATH_PREFIX$', '');
        }

        if (! empty($this->prefixes['view'])) {
            $commandData->addDynamicVariable('$VIEW_PREFIX$', str_replace('/', '.', $this->prefixes['view']) . '.');
        } else {
            $commandData->addDynamicVariable('$VIEW_PREFIX$', '');
        }

        if (! empty($this->prefixes['public'])) {
            $commandData->addDynamicVariable('$PUBLIC_PREFIX$', $this->prefixes['public']);
        } else {
            $commandData->addDynamicVariable('$PUBLIC_PREFIX$', '');
        }

        $commandData->addDynamicVariable(
            '$API_PREFIX$',
            config('infyom.laravel_generator.api_prefix', 'api')
        );

        $commandData->addDynamicVariable(
            '$API_VERSION$',
            config('infyom.laravel_generator.api_version', 'v1')
        );

        return $commandData;
    }

    public function overrideOptionsFromJsonFile($jsonData)
    {
        $options = self::$availableOptions;

        foreach ($options as $option) {
            if (isset($jsonData['options'][$option])) {
                $this->setOption($option, $jsonData['options'][$option]);
            }
        }

        $addOns = ['swagger', 'tests', 'datatables'];

        foreach ($addOns as $addOn) {
            if (isset($jsonData['addOns'][$addOn])) {
                $this->addOns[$addOn] = $jsonData['addOns'][$addOn];
            }
        }
    }

    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }

    public function getAddOn($addOn)
    {
        if (isset($this->addOns[$addOn])) {
            return $this->addOns[$addOn];
        }

        return false;
    }
}
