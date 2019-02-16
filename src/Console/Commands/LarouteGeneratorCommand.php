<?php

namespace Jojo\Laroute\Console\Commands;

use Jojo\Laroute\Routes\Collection as Routes;
use Jojo\Laroute\Generators\GeneratorInterface as Generator;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command;

use Jojo\Laroute\Routes\Collection;
use Symfony\Component\Console\Input\InputOption;

class LarouteGeneratorCommand extends Command
{
    const TYPE_JS   = 'js';
    const TYPE_JSON = 'json';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laroute:generate';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'laroute:generate {routes?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a laravel routes file';

    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * An array of all the registered routes.
     *
     * @var \Jojo\Laroute\Routes\Collection
     */
    protected $routes;

    /**
     * The generator instance.
     *
     * @var \Jojo\Laroute\Generators\GeneratorInterface
     */
    protected $generator;

    /**
     * Create a new command instance.
     *
     * @param Config $config
     * @param Routes $routes
     * @param Generator $generator
     */
    public function __construct(Config $config, Routes $routes, Generator $generator)
    {
        $this->config    = $config;
        $this->routes    = $routes;
        $this->generator = $generator;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $filePath = $this->generator->compile(
                $this->getTemplatePath(),
                $this->getTemplateData(),
                $this->getFileGenerationPath()
            );

            $this->info("Created: {$filePath}");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * Get path to the template file.
     *
     * @return string
     * @throws \Exception
     */
    protected function getTemplatePath()
    {
        if ($this->hasOption('tp')) {
            return $this->option('tp');
        }

        $type = $this->getOptionOrConfig('type');
        switch ($type) {
            case self::TYPE_JS || self::TYPE_JSON:
                return $this->config->get("laroute.template.{$type}");
                break;
            default:
                $this->error("Unknown type: {$type}");
                throw new \Exception("Unknown type: {$type}");
                break;
        }
    }

    /**
     * Get the data for the template.
     *
     * @return array
     */
    protected function getTemplateData()
    {
        $namespace = $this->getOptionOrConfig('namespace');
        $routes    = $this->getRouteCollection()->toJSON();
        $absolute  = $this->config->get('laroute.absolute', false);
        $rootUrl   = $this->config->get('app.url', '');
        $prefix    = $this->config->get('laroute.prefix', '');

        return compact('namespace', 'routes', 'absolute', 'rootUrl', 'prefix');
    }

    /**
     * Get the route collection.
     *
     * @return Collection
     */
    protected function getRouteCollection()
    {
        $routes = $this->argument('routes');

        if (!($routes instanceof Collection)) {
            return $this->routes;
        }

        $this->routes = $routes;
        return $this->routes;
    }


    /**
     * Get the path where the file will be generated.
     *
     * @return string
     */
    protected function getFileGenerationPath()
    {
        $path     = $this->getOptionOrConfig('path');
        $filename = $this->getOptionOrConfig('filename');
        $type     = $this->getOptionOrConfig('type');

        return "{$path}/{$filename}.{$type}";
    }

    /**
     * Get an option value either from console input, or the config files.
     *
     * @param $key
     *
     * @return array|mixed|string
     */
    protected function getOptionOrConfig($key)
    {
        if ($this->hasOption($key)) {
            return $this->option($key);
        }

        return $this->config->get("laroute.{$key}");
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            [
                'path',
                'p',
                InputOption::VALUE_OPTIONAL,
                sprintf('Path to the javascript assets directory (default: "%s")', $this->config->get('laroute.path'))
            ],
            [
                'filename',
                'f',
                InputOption::VALUE_OPTIONAL,
                sprintf('Filename of the javascript file (default: "%s")', $this->config->get('laroute.filename'))
            ],
            [
                'namespace',
                null,
                InputOption::VALUE_OPTIONAL, sprintf('Javascript namespace for the functions (think _.js) (default: "%s")', $this->config->get('laroute.namespace'))
            ],
            [
                'prefix',
                'pr',
                InputOption::VALUE_OPTIONAL, sprintf('Prefix for the generated URLs (default: "%s")', $this->config->get('laroute.prefix'))
            ],
            [
                'type',
                't',
                InputOption::VALUE_OPTIONAL, sprintf('Generated file type ("%s":default, "%s")', self::TYPE_JS, self::TYPE_JSON)
            ],
            [
                'template-path',
                'tp',
                InputOption::VALUE_OPTIONAL, sprintf('Custom path to a template (default: "%s")', $this->config->get("laroute.template.{$this->config->get("laroute.type")}"))
            ],
        ];
    }
}
