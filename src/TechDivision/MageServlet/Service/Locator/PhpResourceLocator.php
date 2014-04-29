<?php

/**
 * TechDivision\MageServlet\Service\Locator\PhpResourceLocator
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
class PhpResourceLocator extends StaticResourceLocator
{

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
        
        // load the request URI
        $uri = $servletRequest->getUri();
        
        // initialize the path information and the directory to start with
        list ($dirname, $basename) = array_values(pathinfo($uri));
        
        // initialize the webapp path (the document root)
        $documentRoot = $servletRequest->getServerVar('DOCUMENT_ROOT');
        
        do { // descent the directory structure down to find a excecutable PHP file
            
            try {
                
                // initialize the file information
                $fileInfo = new \SplFileInfo($documentRoot . $dirname . DIRECTORY_SEPARATOR . $basename);
    
                // check if we have a directory
                if ($fileInfo->isDir()) {
                    throw new FoundDirInsteadOfFileException(sprintf("Requested file %s is a directory", $fileInfo));
                }
    
                // check if we have a real file (not a symlink for example)
                if ($fileInfo->isFile() === false) {
                    throw new FileNotFoundException(sprintf('File %s not not found', $fileInfo));
                }
                
                // check if the file is readable
                if ($fileInfo->isReadable() === false) {
                    throw new FileNotReadableException(sprintf('File %s is not readable', $fileInfo));
                }

                // initialize the server variables
                $servletRequest->setServerVar('PHP_SELF', $uri);
                $servletRequest->setServerVar('SCRIPT_NAME', $dirname . DIRECTORY_SEPARATOR . $basename);
                $servletRequest->setServerVar('SCRIPT_FILENAME', $fileInfo->getPathname());
                
                // set the script file information in the server variables
                $servletRequest->setPathInfo(
                    str_replace(
                        $servletRequest->getServerVar('SCRIPT_NAME'),
                        '',
                        $servletRequest->getServerVar('REQUEST_URI')
                    )
                );
                
                // return the file information
                return $fileInfo;

            } catch (\Exception $e) {
                // do nothing, try with the next directory index file instead
            }

            // descendent down the directory tree
            list ($dirname, $basename) = array_values(pathinfo($dirname));
            
        } while ($dirname !== '/'); // stop until we reached the root of the URI
    }
}
