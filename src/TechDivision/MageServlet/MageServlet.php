<?php

/**
 * TechDivision\MageServlet\MageServlet
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Library
 * @package   TechDivision_MageServlet
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\MageServlet;

use TechDivision\Http\HttpProtocol;
use TechDivision\Servlet\ServletConfig;
use TechDivision\Servlet\Http\HttpServlet;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Servlet\Http\HttpServletResponse;
use TechDivision\MageServlet\Service\Locator\PhpResourceLocator;

/**
 * A servlet implementation for Magento.
 *
 * @category  Library
 * @package   TechDivision_MageServlet
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class MageServlet extends HttpServlet
{

    /**
     * The servlet specific server variables.
     *
     * @var array
     */
    protected $serverVars = array();

    /**
     * The base directory of the actual webapp.
     *
     * @var string
     */
    protected $webappPath;

    /**
     * Initializes the servlet with the passed configuration.
     *
     * @param \TechDivision\ServletContainer\Interfaces\ServletConfig $config The configuration to initialize the servlet with
     *
     * @throws \TechDivision\ServletContainer\Exceptions\ServletException Is thrown if the configuration has errors
     * @return void
     */
    public function init(ServletConfig $config)
    {
        parent::init($config);
        $this->locator = new PhpResourceLocator($this);
        $this->webappPath = $this->getServletConfig()->getWebappPath();
    }

    /**
     * Returns the base directory of the actual webapp.
     *
     * @return string The base directory
     */
    protected function getWebappPath()
    {
        return $this->webappPath;
    }

    /**
     * Returns the array with the $_FILES vars.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return array The $_FILES vars
     */
    protected function initFileGlobals(HttpServletRequest $servletRequest)
    {

        // init query str
        $queryStr = '';

        // iterate all files
        foreach ($servletRequest->getParts() as $part) {
            // check if filename is given, write and register it
            if ($part->getFilename()) {
                // generate temp filename
                $tempName = tempnam(ini_get('upload_tmp_dir'), 'php');
                // write part
                $part->write($tempName);
                // register uploaded file
                $this->registerFileUpload($tempName);
                // init error state
                $errorState = UPLOAD_ERR_OK;
            } else {
                // set error state
                $errorState = UPLOAD_ERR_NO_FILE;
                // clear tmp file
                $tempName = '';
            }
            // check if file has array info
            if (preg_match('/^([^\[]+)(\[.+)?/', $part->getName(), $matches)) {

                // get first part group name and array definition if exists
                $partGroup = $matches[1];
                $partArrayDefinition = '';
                if (isset($matches[2])) {
                    $partArrayDefinition = $matches[2];
                }
                $queryStr .= $partGroup . '[name]' . $partArrayDefinition . '=' . $part->getFilename() .
                    '&' . $partGroup . '[type]' . $partArrayDefinition . '=' . $part->getContentType() .
                    '&' . $partGroup . '[tmp_name]' . $partArrayDefinition . '=' . $tempName .
                    '&' . $partGroup . '[error]' . $partArrayDefinition . '=' . $errorState .
                    '&' . $partGroup . '[size]' . $partArrayDefinition . '=' . $part->getSize() . '&';
            }
        }
        // parse query string to array
        parse_str($queryStr, $filesArray);

        // return files array finally
        return $filesArray;
    }

    /**
     * Register's a file upload on internal php hash table for being able to use core functions
     * like move_uploaded_file or is_uploaded_file as usual.
     *
     * @param string $filename The filename to register
     *
     * @return bool
     */
    public function registerFileUpload($filename)
    {
        return appserver_register_file_upload($filename);
    }

    /**
     * Returns the array with the $_COOKIE vars.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return array The $_COOKIE vars
     */
    protected function initCookieGlobals(HttpServletRequest $servletRequest)
    {
        $cookie = array();
        foreach (explode('; ', $servletRequest->getHeader(HttpProtocol::HEADER_COOKIE)) as $cookieLine) {
            list ($key, $value) = explode('=', $cookieLine);
            $cookie[$key] = $value;
        }
        return $cookie;
    }

    /**
     * Returns the array with the $_REQUEST vars.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return array The $_REQUEST vars
     */
    protected function initRequestGlobals(HttpServletRequest $servletRequest)
    {
        return $servletRequest->getParameterMap();
    }

    /**
     * Returns the array with the $_POST vars.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return array The $_POST vars
     */
    protected function initPostGlobals(HttpServletRequest $servletRequest)
    {
        if ($servletRequest->getMethod() == HttpProtocol::METHOD_POST) {
            return $servletRequest->getParameterMap();
        } else {
            return array();
        }
    }

    /**
     * Returns the array with the $_GET vars.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return array The $_GET vars
     */
    protected function initGetGlobals(HttpServletRequest $servletRequest)
    {
        // check post type and set params to globals
        if ($servletRequest->getMethod() == HttpProtocol::METHOD_POST) {
            parse_str($servletRequest->getQueryString(), $parameterMap);
        } else {
            $parameterMap = $servletRequest->getParameterMap();
        }
        return $parameterMap;
    }

    /**
     * Initialize the PHP globals necessary for legacy mode and backward compatibility
     * for standard applications.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return void
     */
    protected function initGlobals(HttpServletRequest $servletRequest)
    {

        // prepare the request before initializing the globals
        $this->prepareGlobals($servletRequest);

        // initialize the globals
        $_SERVER = $this->initServerGlobals($servletRequest);
        $_REQUEST = $this->initRequestGlobals($servletRequest);
        $_POST = $this->initPostGlobals($servletRequest);
        $_GET = $this->initGetGlobals($servletRequest);
        $_COOKIE = $this->initCookieGlobals($servletRequest);
        $_FILES = $this->initFileGlobals($servletRequest);
    }

    /**
     * Tries to load the requested file and adds the content to the response.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest  $servletRequest  The request instance
     * @param \TechDivision\ServletContainer\Http\ServletResponse $servletResponse The response instance
     *
     * @return void
     */
    public function doGet(HttpServletRequest $servletRequest, HttpServletResponse $servletResponse)
    {
        // load \Mage
        $this->load();

        // init globals
        $this->initGlobals($servletRequest);

        // run \Mage and set content
        $servletResponse->appendBodyStream($this->run($servletRequest));

        // add the status code we've catched from the legacy app
        $servletResponse->setStatusCode(appserver_get_http_response_code());

        // add this header to prevent .php request to be cached
        $servletResponse->addHeader(HttpProtocol::HEADER_EXPIRES, '19 Nov 1981 08:52:00 GMT');
        $servletResponse->addHeader(HttpProtocol::HEADER_CACHE_CONTROL, 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $servletResponse->addHeader(HttpProtocol::HEADER_PRAGMA, 'no-cache');

        // set per default text/html mimetype
        $servletResponse->addHeader(HttpProtocol::HEADER_CONTENT_TYPE, 'text/html');

        // grep headers and set to response object
        foreach (appserver_get_headers(true) as $i => $h) {
            // set headers defined in sapi headers
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                // load header key and value
                $key = trim($h[0]);
                $value = trim($h[1]);
                // if no status, add the header normally
                if ($key === HttpProtocol::HEADER_STATUS) {
                    // set status by Status header value which is only used by fcgi sapi's normally
                    $servletResponse->setStatus($value);
                } else {
                    $servletResponse->addHeader($key, $value);
                }
            }
        }
    }

    /**
     * Tries to load the requested file and adds the content to the response.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest  $servletRequest  The request instance
     * @param \TechDivision\ServletContainer\Http\ServletResponse $servletResponse The response instance
     *
     * @throws \TechDivision\ServletContainer\Exceptions\PermissionDeniedException Is thrown if the request tries to execute a PHP file
     * @return void
     */
    public function doPost(HttpServletRequest $servletRequest, HttpServletResponse $servletResponse)
    {
        $this->doGet($servletRequest, $servletResponse);
    }

    /**
     * Prepares the passed request instance for generating the globals.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return void
     */
    protected function prepareGlobals(HttpServletRequest $servletRequest)
    {

        // check if a XHttpRequest has to be handled
        if (($xRequestedWith = $servletRequest->getHeader(HttpProtocol::HEADER_X_REQUESTED_WITH)) != null) {
            $servletRequest->setServerVar('HTTP_X_REQUESTED_WITH', $xRequestedWith);
        }

        // if the application has not been called over a vhost configuration append application folder name
        if ($servletRequest->getContext()->isVhostOf($servletRequest->getServerName()) === true) {
            $directoryIndex = 'index.do';
        } else {
            $directoryIndex = $servletRequest->getContextPath() . DIRECTORY_SEPARATOR . 'index.do';
        }

        // initialize the server variables
        $this->serverVars['SCRIPT_FILENAME'] = $servletRequest->getServerVar('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . $directoryIndex;
        $this->serverVars['SCRIPT_NAME'] = $directoryIndex;
        $this->serverVars['PHP_SELF'] = $directoryIndex;

        // ATTENTION: This is necessary because of a Magento bug!!!!
        $this->serverVars['SERVER_PORT'] = null;
    }

    /**
     * Returns the array with the $_SERVER vars.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return array The $_SERVER vars
     */
    protected function initServerGlobals(HttpServletRequest $servletRequest)
    {
        return array_merge($servletRequest->getServerVars(), $this->serverVars);
    }

    /**
     * Loads the necessary files needed.
     *
     * @return void
     */
    public function load()
    {
        require_once $this->getServletConfig()->getWebappPath() . '/app/Mage.php';
    }

    /**
     * Runs the WebApplication
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return string The web applications content
     */
    public function run(HttpServletRequest $servletRequest)
    {

        try {

            // register the Magento autoloader as FIRST autoloader
            spl_autoload_register(array(new \Varien_Autoload(), 'autoload'), true, true);

            // Varien_Profiler::enable();
            if (isset($_SERVER['MAGE_IS_DEVELOPER_MODE'])) {
                \Mage::setIsDeveloperMode(true);
            }

            ini_set('display_errors', 1);
            umask(0);

            // store or website code
            $mageRunCode = isset($_SERVER['MAGE_RUN_CODE']) ? $_SERVER['MAGE_RUN_CODE'] : '';

            // run store or run website
            $mageRunType = isset($_SERVER['MAGE_RUN_TYPE']) ? $_SERVER['MAGE_RUN_TYPE'] : 'store';

            // set headers sent to false and start output caching
            appserver_set_headers_sent(false);
            ob_start();

            // reset and run Magento
            \Mage::reset();
            \Mage::run();

            // write the session back after the request
            session_write_close();

            // grab the contents generated by Magento
            $content = ob_get_clean();

        } catch (\Exception $e) {
            error_log($content = $e->__toString());
        }

        // return the content
        return $content;
    }
}
