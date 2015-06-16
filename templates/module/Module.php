\<\?php

namespace <?=$moduleNamespace?>;

use Eva\EvaEngine\Module\AbstractModule;
use Phalcon\DiInterface;
use Phalcon\Loader;
use Phalcon\Mvc\Dispatcher;

class Module extends AbstractModule
{
    public static function registerGlobalAutoloaders()
    {
        return array(
            '<?=$moduleNamespace?>' => __DIR__ . '/src/<?=$moduleName?>',
        );
    }

    /**
     * Registers the module-only services
     *
     * @param \Phalcon\DiInterface $di
     */
    public function registerServices(DiInterface $di)
    {
        $dispatcher = $di->getDispatcher();
        $dispatcher->setDefaultNamespace('<?=$moduleNamespace?>\Controllers');
    }
}
