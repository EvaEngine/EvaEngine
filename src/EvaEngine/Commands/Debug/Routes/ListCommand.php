<?php
// +----------------------------------------------------------------------
// | wallstreetcn
// +----------------------------------------------------------------------
// | Author: Mr.5 <mr5.simple@gmail.com>
// +----------------------------------------------------------------------
// + Datetime: 15/6/19 下午4:47
// +----------------------------------------------------------------------
// + DebugListRoutesCommand.php
// +----------------------------------------------------------------------
namespace Eva\EvaEngine\Commands\Debug\Routes;

use Eva\EvaEngine\Console\CommandBase;
use Eva\EvaEngine\Mvc\WebApplication;
use Symfony\Component\Console\Input\InputOption;

class ListCommand extends CommandBase
{
    public function configure()
    {
        $this->setDescription('列出所有路由');
        $this->addOption('sort-by', null, InputOption::VALUE_OPTIONAL, '排序依据，支持 pattern 和 definition', 'pattern');
    }

    public function fire()
    {
        $moduleManager = eva_get('moduleManager');
        $router = eva_get('router');
        WebApplication::initializeRouter($moduleManager, $router);
//        $routes = $moduleManager->getAllWebRoutes();

//        dd($routes);
        $methodStyles = array(
            'PUT' => '<comment>%s</comment>',
            'POST' => '<info>%s</info>',
            'DELETE' => '<error>%s</error>'
        );
        $header = array(
            'method',
            'pattern',
            'handler',
            '_dispatch_cache',
            'paths'
        );
        $rows = array();
        foreach ($router->getRoutes() as $route) {
            $style = !empty($methodStyles[$route->getHttpMethods()])
                ? $methodStyles[$route->getHttpMethods()]
                : '%s';
//            $this->output->writeln();
//            $this->error($route->getCompiledPattern());
//            $this->info($route->getName());
            $paths = $route->getPaths();
            $module = isset($paths['module']) ? $paths['module'] : $moduleManager->getDefaultModule()->getName();
            $controller = isset($paths['controller']) ? $paths['controller'] : '*';
            $action = isset($paths['action']) ? $paths['action'] : '*';

            $handler = $module . ':' . $controller . ':' . $action;
            unset($paths['module']);
            unset($paths['controller']);
            unset($paths['action']);
            $dispatch_cache = @$paths['_dispatch_cache'];
            unset($paths['_dispatch_cache']);

            $rows[] = array(
                sprintf($style, $route->getHttpMethods()),
                $route->getPattern(),
                $handler,
                $dispatch_cache,
                !empty($paths) ? var_export($paths, true) . PHP_EOL : ''
            );
//            p($route->getPaths());
        }
//        foreach ($rows as $key => $row) {
//            $volume[$key]  = 1;
//        }
//        array_multisort($volume, SORT_ASC, SORT_STRING, $rows);


        if ($this->option('sort-by') == 'pattern') {
            usort($rows, function ($a, $b) {
                return $a[1] > $b[1];
            });
        }
        $this->table($header, $rows);
    }
}