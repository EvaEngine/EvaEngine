\<\?php

namespace <?=$moduleNamespace?>\Controllers;

use Eva\EvaEngine\Mvc\Controller\ControllerBase as EngineControllerBase;
use Eva\EvaEngine\Mvc\View;

class ControllerBase extends EngineControllerBase
{
    public function initialize()
    {
        /** @var View $this->view */
        $this->view->setModuleLayout('<?=$moduleName?>', '/views/layouts/default');
        $this->view->setModuleViewsDir('<?=$moduleName?>', '/views');
        $this->view->setModulePartialsDir('<?=$moduleName?>', '/views');
    }

}
