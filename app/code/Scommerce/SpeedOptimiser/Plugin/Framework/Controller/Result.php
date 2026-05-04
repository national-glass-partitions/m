<?php
/**
 * Scommerce SpeedOptimiser  plugin file to move all script in page bottom
 *
 * @category   Scommerce
 * @package    Scommerce_SpeedOptimiser
 * @author     Sommerce Mage <core@scommerce-mage.co.uk>
 */

namespace Scommerce\SpeedOptimiser\Plugin\Framework\Controller;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Scommerce\SpeedOptimiser\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Result
 * @package Scommerce_SpeedOptimiser
 */
class Result
{
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    
    /**
     * @var Data
     */
    protected $helper;
    
    /**
     * __construct
     * 
     * @param RequestInterface $request
     * @param Data $helper
     */
    public function __construct(
        RequestInterface $request,
        DirectoryList $directoryList,
        Data $helper
    ) {
        $this->request = $request;
        $this->directoryList = $directoryList;
        $this->helper = $helper;
    }
    
    /**
     * Render result and set to response (around plugin)
     * 
     * @param \Magento\Framework\Controller\ResultInterface $subject
     * @param \Closure $proceed
     * @param ResponseHttp $response
     * @return mixed
     */
    public function aroundRenderResult(
        \Magento\Framework\Controller\ResultInterface $subject, 
        \Closure $proceed, 
        ResponseHttp $response
    ) {
        $result = $proceed($response); 
                
        if(!$this->helper->isEnabled()) {
             return $result;
        }

        $html = $response->getBody();
        if ($html == '') {
            return $result;
        }

        if ($this->helper->isJavaScriptMoveToBottom()) {
            $html = $this->moveScriptToPageBottom($html);
        }
        
        if ($this->helper->isDeferFontsSettingEnabled()) {
            $html = $this->minifyCss($html, $response);
        }
        
        if ($this->helper->getDeferIframe()) {
            $html = $this->deferIframe($html);
        }

        $response->setBody($html);
        return $result;
    }
    
    /**
     * Add 'defer' attribute on iframe
     * 
     * @param string $html
     * @return string
     */
    protected function deferIframe($html)
    {
       return str_replace('iframe', 'iframe defer', $html);
        
    }
    
    /**
     * Move all scripts to the page bottom
     * 
     * @param string $html
     * @return string
     */
    protected function moveScriptToPageBottom($html)
    {
        $content = $html;
        $scripts = [];
        $startTag = '<script';
        $endTag = '</script>';
        $start = $i= 0;
        while (false !== ($start = stripos($html, $startTag, $start))) {
            $i++;
            if ($i > 1000) {
                return $html;
            }
            $end = stripos($html, $endTag, $start);
            if (false === $end) {
                break;
            }
            $len = $end + strlen($endTag) - $start;
            $script = substr($html, $start, $len);
            $html = str_replace($script, '', $html);
            $scripts[] = $script;
        }
        
        return $this->excludeMatchString($scripts, $content);
    }
    
    /**
     * Exclude all script by matching string 
     * 
     * @param array $scripts
     * @param string $content
     * @return string
     */
    protected function excludeMatchString($scripts, $content)
    {
        $updatedScripts = [];
        foreach ($scripts as $script) {
            if (strpos($script, 'var require') !== false || strpos($script, 'requirejs') !== false 
                    || strpos($script, 'Magento_Swatches') !== false || strpos($script, 'perfectaudience') !== false) {
                continue;
            } else {
                $content = str_replace($script, '', $content);
                $updatedScripts[] = $script;
            }
        }

        $scriptsTostring = implode(PHP_EOL, $updatedScripts);
        if ($end = stripos($content, '</body>')) {
            $content = substr($content, 0, $end) . $scriptsTostring . substr($content, $end);
        } else {
            $content .= $scriptsTostring;
        }

        return $content;
    }

    /**
     * Get static directory path
     * 
     * @return string
     */
    protected function getStaticDirectoryPath()
    {
        return $this->directoryList->getPath(DirectoryList::STATIC_VIEW);
    }
    
    /**
     * Updated the merged css file
     * 
     * @param string $html
     */
    protected function updateCssMergedFile($html)
    {
        $path = $this->getCSSPath($html);
        $content = $this->getCssContent($path);
        $newContent = preg_replace('/@font-face(.*?)}/is', "", $content);
        $hash = $this->getMergedHash($path);
        $mergePath = $this->getMergeCssPath();
        $filepath = $mergePath . $hash . '.css';
        $this->writeFile($newContent, $filepath);
    }
    
    /**
     * Write the file
     * 
     * @param string $content
     * @param string $filepath
     */
    protected function writeFile($content, $filepath)
    {

        $handle = fopen($filepath, "w");
        fwrite($handle, $content);
        fclose($handle);
    }
    
    /**
     * Get the merged directory
     * 
     * @return string
     */
    protected function getMergeCssPath()
    {
        $staticPath = $this->getStaticDirectoryPath();
        return $staticPath . "/_cache/merged/";
    }
    
    /**
     * Get the merged hash number
     * 
     * @param string $path
     * @return string
     */
    protected function getMergedHash($path)
    {
        return substr($path, strrpos($path, '/') + 1);
    }

    /**
     * Get css
     *
     * @param string $path
     * @return string
     */
    protected function getCssContent($path)
    {
        try {
            if($path) {
                $arrContextOptions=array(
                    "ssl"=>array(
                        "verify_peer"=>false,
                        "verify_peer_name"=>false,
                    ),
                );
                $pathWithExtension = $path . '.css';
                return file_get_contents($pathWithExtension,false,stream_context_create($arrContextOptions));
            }
        }
        catch (\Exception $e){
            throw new LocalizedException(
                __($e->getMessage())
            );
        }
    }
    
    /**
     * Get css file path
     * 
     * @param string $html
     * @return string
     */
    protected function getCSSPath($html)
    {
        $csss = $this->getBetweenMultiple($html, 'href="', '.css"');

        foreach($csss as $css ) {
            if (strpos($css, 'merged') !== false){
                return $css;
            }
        }
    }
    
    /**
     * Minify Css 
     * 
     * @param string $html
     * @return string
     */
    protected function minifyCss($html)
    {

        $path = $this->getCSSPath($html);
        if(!$path) {
            return $html;
        }
        $hash = $this->getMergedHash($path);


        if (!$this->checkIfFontFileExists($hash)) {
            $content = $this->getCssContent($path);

            $datas = $this->getBetweenMultiple($content, '@font-face', '}');

            $string = '';
            foreach ($datas as $key => $value) {
                if (!$key == 0) {
                    $string .= '@font-face ' . $value . '}' . PHP_EOL;
                }
            }

            if ($string) {
                $this->createFontMergedFile($string, $hash);
            }

            $this->updateCssMergedFile($html);
        }

        return $this->addFontFileBodyEnd($html, $path);
    }
    
    /**
     * Add font file link in the page end
     * 
     * @param string $html
     * @param string $path
     * @return string
     */
    protected function addFontFileBodyEnd($html, $path)
    {
        $fontPath = '<link rel="stylesheet" type="text/css" media="all" href="' .$path. '_font.css">';
        $match = '</body>';
        $content = str_replace($match, $fontPath . $match , $html);

        return $content;
    }

    /**
     * Checked, if font file already exists
     * 
     * @param string $hash
     * @return boolean
     */
    protected function checkIfFontFileExists($hash)
    {

        $mergePath = $this->getMergeCssPath();
        $filepath = $mergePath . $hash . '_font.css';
        return file_exists($filepath);
    }

    /**
     * Creat font file
     * 
     * @param string $string
     * @param string $hash
     */
    protected function createFontMergedFile($string, $hash)
    {

        $mergePath = $this->getMergeCssPath();
        $filepath = $mergePath . $hash . '_font.css';
        $this->writeFile($string, $filepath);
    }
    
    /**
     * Get first matched between two character
     * 
     * @param string $content
     * @param string $start
     * @param string $end
     * @return string
     */
    protected function getBetween($content, $start, $end)
    {
        $r = explode($start, $content);

        if (isset($r[1])) {
            $r = explode($end, $r[1]);
            return $r[0];
        }
        return '';
    }
    
    /**
     * Get all matched string between two character
     * 
     * @param string $content
     * @param string $start
     * @param string $end
     * @return string
     */
    protected function getBetweenMultiple($content, $start, $end)
    {
        $n = explode($start, $content);
        $result = Array();
        foreach ($n as $val) {
            $pos = strpos($val, $end);
            if ($pos !== false) {
                $result[] = substr($val, 0, $pos);
            }
        }
        return $result;
    }

}
