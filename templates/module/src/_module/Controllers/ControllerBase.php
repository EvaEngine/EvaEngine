\<\?php

namespace <?=$moduleNamespace?>\Controllers;

use Eva\EvaEngine\Mvc\Controller\ControllerBase as EngineControllerBase;

class ControllerBase extends EngineControllerBase
{
    public function initialize()
    {
        $this->view->setModuleLayout('<?=$moduleName?>', '/views/layouts/default');
        $this->view->setModuleViewsDir('<?=$moduleName?>', '/views');
        $this->view->setModulePartialsDir('<?=$moduleName?>', '/views');
    }

}
