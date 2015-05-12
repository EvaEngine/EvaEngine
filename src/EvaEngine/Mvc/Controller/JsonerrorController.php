<?php
/**
 * EvaEngine (http://evaengine.com/)
 * A development engine based on Phalcon Framework.
 *
 * @copyright Copyright (c) 2014-2015 EvaEngine Team (https://github.com/EvaEngine/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Eva\EvaEngine\Mvc\Controller;

use Phalcon\Mvc\Controller;

/**
 * Class JsonerrorController
 * @package Eva\EvaEngine\Mvc\Controller
 */
class JsonerrorController extends Controller
{
    public function indexAction()
    {
        $error = $this->dispatcher->getParam('error');
        $this->response->setContentType('application/json', 'utf-8');
        $this->response->setJsonContent(
            array(
            'errors' => array(
                array(
                    'code' => $error->type(),
                    'message' => $error->message()
                )
            ),
            )
        );
        $callback = $this->request->getQuery('callback');
        if ($callback) {
            $this->response->setContent($callback . '(' . $this->response->getContent() . ')');
        }
        echo $this->response->getContent();
    }
}
