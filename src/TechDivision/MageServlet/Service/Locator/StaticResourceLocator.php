<?php

/**
 * TechDivision\MageServlet\Service\Locator\StaticResourceLocator
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Library
 * @package    TechDivision_MageServlet
 * @subpackage Service
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */

namespace TechDivision\MageServlet\Service\Locator;

use TechDivision\Servlet\Servlet;
use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletContext;

/**
 * The static resource locator implementation, e. g. to locate files like pictures.
 *
 * @category   Library
 * @package    TechDivision_MageServlet
 * @subpackage Service
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class StaticResourceLocator extends AbstractResourceLocator
{

    /**
     * The servlet that called the locator.
     *
     * @var \TechDivision\ServletContainer\Interfaces\Servlet
     */
    protected $servlet;

    /**
     * Initializes the locator with the calling servlet.
     *
     * @param \TechDivision\ServletContainer\Interfaces\Servlet $servlet The servlet instance
     *
     * @return void
     */
    public function __construct(Servlet $servlet)
    {
        $this->servlet = $servlet;
    }

    /**
     * Returns the calling servlet instance.
     *
     * @return \TechDivision\ServletContainer\Interfaces\Servlet $servlet The servlet instance
     */
    public function getServlet()
    {
        return $this->servlet;
    }
    
    /**
     * Return's the application itself.
     * 
     * @return \TechDivision\ServletContainer\Application The application itself
     */
    public function getApplication()
    {
        return $this->getServlet()->getServletConfig()->getApplication();
    }

    /**
     * Tries to locate the file specified in the passed request instance.
     * 
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return \SplFileInfo The located file information
     * @throws \TechDivision\ServletContainer\Exceptions\FoundDirInsteadOfFileException Is thrown if the requested file is a directory
     * @throws \TechDivision\ServletContainer\Exceptions\FileNotFoundException Is thrown if the requested file has not been found or is not readable
     * @throws \TechDivision\ServletContainer\Exceptions\FileNotReadableException Is thrown if the requested file is not readable
     */
    public function locate(ServletContext $servletContext, ServletRequest $servletRequest)
    {

        // build the path from url part and base path
        $path = $this->getFilePath($servletRequest);
        
        // load file information and return the file object if possible
        $fileInfo = new \SplFileInfo($path);
        
        // check if we have a directory
        if ($fileInfo->isDir()) {
            throw new FoundDirInsteadOfFileException(sprintf("Requested file %s is a directory", $path));
        }
        
        // check if we have a real file (not a symlink for example)
        if ($fileInfo->isFile() === false) {
            throw new FileNotFoundException(sprintf('File %s not not found', $path));
        }
        
        // check if the file is readable
        if ($fileInfo->isReadable() === false) {
            throw new FileNotReadableException(sprintf('File %s is not readable', $path));
        }
        
        // return the \SplFileInfo instance
        return $fileInfo;
    }

    /**
     * Returns the absolute path in the filesystem to file without URI params.
     * 
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance
     *
     * @return string The absolute path of the requested file
     */
    public function getFilePath(ServletRequest $servletRequest)
    {
        return $servletRequest->getServerVar('DOCUMENT_ROOT') . $servletRequest->getServerVar('REQUEST_URI');
    }
}
