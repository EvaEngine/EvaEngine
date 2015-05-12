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
 * Class ErrorController
 * @package Eva\EvaEngine\Mvc\Controller
 */
class ErrorController extends Controller
{
    /**
     * @var string
     */
    protected $contentDefault = <<<EOF
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Server Error</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>

        * {
            line-height: 1.5;
            margin: 0;
        }

        html {
            color: #888;
            font-family: sans-serif;
            text-align: center;
        }

        body {
            left: 50%;
            margin: -43px 0 0 -150px;
            position: absolute;
            top: 50%;
            width: 300px;
        }

        h1 {
            color: #555;
            font-size: 2em;
            font-weight: 400;
        }

        p {
            line-height: 1.2;
        }

        @media only screen and (max-width: 270px) {

            body {
                margin: 10px auto;
                position: static;
                width: 95%;
            }

            h1 {
                font-size: 1.5em;
            }

        }

    </style>
</head>
<body>
    <h1>Looks like something went wrong!</h1>
    <p>
        We track these errors automatically, but if the problem persists feel free to contact us.
        In the meantime, try refreshing.
    </p>
</body>
</html>
EOF;

    protected $contentCode404 = <<<EOF
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Page Not Found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>

        * {
            line-height: 1.5;
            margin: 0;
        }

        html {
            color: #888;
            font-family: sans-serif;
            text-align: center;
        }

        body {
            left: 50%;
            margin: -43px 0 0 -150px;
            position: absolute;
            top: 50%;
            width: 300px;
        }

        h1 {
            color: #555;
            font-size: 2em;
            font-weight: 400;
        }

        p {
            line-height: 1.2;
        }

        @media only screen and (max-width: 270px) {

            body {
                margin: 10px auto;
                position: static;
                width: 95%;
            }

            h1 {
                font-size: 1.5em;
            }

        }

    </style>
</head>
<body>
    <h1>Page Not Found</h1>
    <p>Sorry, but the page you were trying to view does not exist.</p>
</body>
</html>
EOF;

    /**
     * @var string
     */
    protected $contentCode401 = <<<EOF
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Permission Not Allowed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>

        * {
            line-height: 1.5;
            margin: 0;
        }

        html {
            color: #888;
            font-family: sans-serif;
            text-align: center;
        }

        body {
            left: 50%;
            margin: -43px 0 0 -150px;
            position: absolute;
            top: 50%;
            width: 300px;
        }

        h1 {
            color: #555;
            font-size: 2em;
            font-weight: 400;
        }

        p {
            line-height: 1.2;
        }

        @media only screen and (max-width: 270px) {

            body {
                margin: 10px auto;
                position: static;
                width: 95%;
            }

            h1 {
                font-size: 1.5em;
            }

        }

    </style>
</head>
<body>
    <h1>Permission Not Allowed</h1>
    <p>Sorry, you don t have permission to access this page, please try login.</p>
</body>
</html>
EOF;

    /**
     * Print error based on http status code of exception
     */
    public function indexAction()
    {
        $this->view->disable();
        $error = $this->dispatcher->getParam('error');
        $methodName = 'contentCode' . $error->statusCode();
        $content = isset($this->$methodName) ? $this->$methodName : $this->contentDefault;
        echo $content;
    }
}
