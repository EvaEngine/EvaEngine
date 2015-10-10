<?php
/**
 * @author    AlloVince
 * @copyright Copyright (c) 2015 EvaEngine Team (https://github.com/EvaEngine)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */


namespace Eva\EvaEngine\Dev;

trait TemplateSupportTrait
{
    /**
     * @var string
     */
    protected $templatesDir;

    /**
     * @param $templatesDir
     * @return $this
     */
    public function setTemplatesDir($templatesDir)
    {
        $this->templatesDir = $templatesDir;
        return $this;
    }

    /**
     * Load template and extract parameters
     * @param string $path
     * @param array $vars
     * @return string
     */
    public function loadTemplate($path, array $vars = [])
    {
        ob_start();
        extract($vars);
        include $path;
        $content = ob_get_clean();
        $content = str_replace('\\<\\?', '<?', $content);
        return $content;
    }
}
