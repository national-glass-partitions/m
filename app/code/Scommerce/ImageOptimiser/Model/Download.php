<?php
/**
 * This is the main class to compress images under product, category, cms pages along with media folder
 *
 * @category   Scommerce
 * @package    Scommerce_ImageOptimiser
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\ImageOptimiser\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as MageFolderList;
use Scommerce\ImageOptimiser\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;

/**
 * Scommerce ImageOptimiser Config Model
 */
class Download
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var Media Directory List
     */
    protected $directoryList;
    /**
     * @var CoreHelper
     */
    protected $dataHelper;
    /**
     * @var FileSystem
     */
    protected $fileSystem;

    const META_FILE_POSTFIX = '.meta';
    const BACKUP_EXTENSION = '.original';

    /**
     * __construct
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param Data $dataHelper
     * @param FileSystem $fileSystem
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        Data $dataHelper,
        FileSystem $fileSystem
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->dataHelper = $dataHelper;
        $this->fileSystem = $fileSystem;
    }

    /**
     * Get the all Media Folders and files
     *
     */
    public function downloadMedia()
    {
        if (!$this->dataHelper->isEnabled()) return;

        /**
         * Get the Numbers of files with need to be processes once
         * @var $noOfImagesProcessed
         */
        $noOfImagesProcessed = $this->dataHelper->noOfImagesProcessed();

        /**
         * Get the list of included media folders from configuration for the image compression
         * @var array $mediaFolders
         */
        $mediaFolders = $this->dataHelper->getCompressMediaFolders();

        /**
         * Get list of media directories which are selected for compression
         * @var array $dirs
         */
        $dirs = $this->dataHelper->getDirectory(MageFolderList::MEDIA, $mediaFolders);

        /**
         * Give the media path directory of the Local
         * @var $dir
         */
        $dir = $this->directoryList->getPath(MageFolderList::MEDIA);

        foreach ($dirs as $dirInfo) {
            // will return all the files in the given folder in our case media folder
            $filesList = $this->scanDirectory($dir . '/' . $dirInfo, $noOfImagesProcessed);

            //print_r($filesList);

            //loop through all the files, back them up and compress them
            foreach ($filesList as $file) {
                $this->compressImage($file);
            }
        }
    }

    /**
     * @param string $file
     * @throws LocalizedException
     */
    public function compressImage($file)
    {
        /**
         * Get the Service provider name for image compress
         * @var $provider
         */
        $provider = $this->dataHelper->getProviderName();

        /**
         * Get the Service provider URL for image compress
         * @var $apiUrl
         */
        $apiUrl = str_replace(' ', '',$this->dataHelper->getProviderApiUrl());

        /**
         * Give the Quality compress image
         * @var $quality
         */
        $quality = 90;

        /**
         * Get the Service Api key for image compress
         * @var $apiKey
         */
        $apiKey = $this->dataHelper->getProviderApiKey();

        /**
         * Get the Service Api Secret key for image compress
         * @var $apiSecret
         */
        $apiSecret = $this->dataHelper->getProviderApiSecret();

        // Before doing anything with the original file lets take a back up first but not for cache folder
        if ($this->dataHelper->isBackupImagesEnabled() &&
            strpos($file, '/cache/') === false) {
            if (!file_exists($file . self::BACKUP_EXTENSION) &&
                strpos($file, self::BACKUP_EXTENSION) === false) {
                copy($file, $file . self::BACKUP_EXTENSION);
            }
        }

        //full path of the file for example var/www/website.com/pub/media/home/home-erin.png
        $destinationPath = $file;

        //converting the path to URL for example the above destination path will convert to http://website.com/pub/media/home/home-erin.png
        $imageURL = $this->dataHelper->convertPathToURL($destinationPath);
        try {
            if (isset($provider) && isset($apiUrl)) {
                //compressing and optimising image based on the provider
                if ($this->imageOptimizerProvider($provider, $apiUrl, $imageURL, $destinationPath, $quality, $apiKey, $apiSecret)) {
                    //create meta file so that you don't compress the same image again
                    $metaFile = $this->getSpecialFileName($destinationPath);
                    $this->createFile($metaFile);
                }
                else{
                    $metaFile = $this->getSpecialFileName($destinationPath);
                    $this->createFile($metaFile);
                    $this->dataHelper->addCritical('Unable to optimised image because provider could not processed the image '. $destinationPath);
                }
            } else {
                if ($this->dataHelper->isDebuggingEnabled()) {
                    $this->dataHelper->addCritical('Unable to find provider information terminating the process');
                    throw new LocalizedException(__('Unable to find provider information terminating the process'));
                }
            }
        } catch (Exception $e) {
            $this->dataHelper->addCritical('Error occured in optimising images terminating the process - '. $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
    }
    /**
     * This function returns .meta file based on the image file name given
     * @param string $imagePath
     * @param string $extension
     * @return string
     */
    private function getSpecialFileName($imagePath, $extension = self::META_FILE_POSTFIX)
    {
        if (isset($imagePath)){
            //get the path of the image
            $mainPath = dirname($imagePath);

            //get the image name only from the path
            $fileName = basename($imagePath);

            //replace extension with .meta
            $fileName = substr($fileName, 0, strpos($fileName, '.')) . $extension;

            //create the final path of save the meta file
            $imagePath = $mainPath . '/' . $fileName;
        }

        return $imagePath;
    }
    /**
     * @param string $destinationPath
     * @return void
     * @throws
     */
    private function createFile($destinationPath)
    {
        try {
            $handle = fopen($destinationPath, "w");
            fwrite($handle, '');
            fclose($handle);
        } catch (\Exception $e) {
            $this->dataHelper->addCritical('Unable to create meta file terminating the process');
            throw new LocalizedException(__('Unable to create meta file terminating the process'));
        }
    }

    /**
     *
     * @param string $file
     * @return boolean
     */
    private function checkExclusionDirectory($file)
    {
        /**
         * Get the list of folders you want to exclude for compressing the images from media directory
         * @var $excludeFolders
         */
        $excludeFolders = $this->dataHelper->getExcludeFolders();
        foreach ($excludeFolders as $folder) {
            if (strpos($file, $folder) !== false) {
                return true;
            }
        }
        return false;

    }

    /**
     * @param $dir
     * @param int $limit
     * @return array
     */
    private function scanDirectory($dir, $limit = 0)
    {
        $files = array();
        foreach (scandir($dir) as $file) {
            if (trim($file, '.') === '') {
                continue;
            }

            if(substr($file,0,1)=='.'){
                continue;
            }

            $tmp = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;

            if (!$this->dataHelper->isCompressCacheEnabled() &&
                strpos($tmp, '/cache/') !== false) {
                continue;
            }

            if (strpos($tmp,self::BACKUP_EXTENSION)!==false ||
                strpos($tmp, self::META_FILE_POSTFIX)!==false) {
                continue;
            }

            if ($this->checkExclusionDirectory($tmp)) {
                continue;
            }

            if (!is_dir($tmp)) {
                if (!is_file($this->getSpecialFileName($tmp))) {
                    $files[] = $tmp;
                }
            } else {
                $files = array_merge($files, $this->scanDirectory($tmp, $limit));
            }

            if ($limit > 0 && count($files) > $limit) {
                break;
            }
        }

        if ($limit > 0 && count($files) > $limit) {
            return array_slice($files, 0, $limit);
        }

        return $files;
    }

    /**
     * This function works as a switcher to switch image optimization provider
     *
     * @param string $provider
     * @param string $apiUrl
     * @param string $imageURL
     * @param string $destinationPath
     * @param string $quality
     * @return bool
     * @throws
     *
     */
    protected function imageOptimizerProvider($provider, $apiUrl, $imageURL, $destinationPath, $quality, $apiKey, $apiSecret)
    { 
        switch (strtolower($provider)) {
            case "resmush.it":
                return $this->reSmush($apiUrl, $imageURL, $destinationPath, $quality);
                break;
            case "imageoptim":
                return $this->imageOptim($apiUrl, $imageURL, $destinationPath);
                break;
            case "kraken.io":
                return $this->krakenIo($apiUrl, $apiKey, $apiSecret, $imageURL, $destinationPath);
                break;
        }
    }

    /**
     * This function request resmush.it api to compress the given file
     *
     * @param string $apiUrl
     * @param string $imageURL
     * @param string $destinationPath
     * @param string $quality
     * @return bool
     * @throws
     */
    protected function reSmush($apiUrl, $imageURL, $destinationPath, $quality)
    {
        try {
            $oCompressedPicture = json_decode($this->getDataFromURL($apiUrl . $imageURL . '&qlty=' . $quality));
            
            if(isset($oCompressedPicture->error)){
                    $this->dataHelper->addCritical($oCompressedPicture->error .' - '. $imageURL);
                    return false;
            }
            
            if ($oCompressedPicture == null) {
                $this->dataHelper->addCritical('Error occured in optimising images through resmush terminating the process - '. $imageURL);

            }
            if (isset($oCompressedPicture->dest)) {
                $compressedImage = $this->getDataFromURL($oCompressedPicture->dest);
                return $this->saveCompressFile($destinationPath, $compressedImage, $imageURL);
            }
            return true;
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Unable to retrieve from resmush api -> ' . $destinationPath . ' error -> ' . $e->getMessage())
            );
        }
        return false;
    }

    /**
     * This function request krakenIo.io api to compress the given file
     *
     * @param string $apiUrl
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $imageURL
     * @param string $destinationPath
     *
     * @return bool
     * @throws
     */
    protected function krakenIo($apiUrl, $apiKey, $apiSecret, $imageURL, $destinationPath)
    {
        try {
            $auth = array('auth' => array(
                'api_key' => (string)$this->dataHelper->getProviderApiKey(),
                'api_secret' => (string)$this->dataHelper->getProviderApiSecret()
            ));

            $params = array(
                "url" => (string)$imageURL,
                "wait" => true
            );

            $data = json_encode(array_merge($auth, $params));

            $response = $this->getDataFromURL($apiUrl, true, $data, 'application/json', 1);
            $response = json_decode($response, true);

            if ($response["success"]) {
                $compressedImage = file_get_contents($response["kraked_url"]);
                return $this->saveCompressFile($destinationPath, $compressedImage, $imageURL);
            } else {
                throw new LocalizedException(
                    __('Unable to retrieve from krakenIo api -> ' . $destinationPath)
                );
            }
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Unable to retrieve from krakenIo api -> ' . $destinationPath . ' error -> ' . $e->getMessage())
            );
        }
        return false;
    }

    /**
     * This function request imageoptim.io api to compress the given file
     *
     * @param string $apiUrl
     * @param string $imageURL
     * @param string $destinationPath
     *
     * @return bool
     * @throws
     */
    protected function imageOptim($apiUrl, $imageURL, $destinationPath)
    {
        try {
            $compressedImage = $this->getDataFromURL($apiUrl . $imageURL, true);
            return $this->saveCompressFile($destinationPath, $compressedImage, $imageURL);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Unable to retrieve from imageoptim api -> ' . $destinationPath . ' error -> ' . $e->getMessage()));
        }
        return false;
    }


    /**
     * Get image from the given URL
     *
     * @param string $url
     * @param bool $post
     * @param array $postData
     * @param string $contentType
     * @param bool $sslVerify
     * @return bool|string
     */
    protected function getDataFromURL($url, $post = false, $postData = array(), $contentType = '', $sslVerify = true)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_POST, $post);
        curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);

        if (isset($postData)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        if (isset($contentType)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, true);
        }

        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }


    /**
     * This function save compressed file to the file system
     *
     * @param string $destinationPath
     * @param string $compressedImage
     * @param string $imageURL
     *
     * @return bool
     * @throws
     */
    protected function saveCompressFile($destinationPath, $compressedImage, $imageURL)
    {
        try {
            $beforeSize = $this->retrieve_remote_file_size($imageURL);
            $afterSize = strlen($compressedImage);
            $bytesSaved = $this->getBytesSaved($beforeSize, $afterSize);
            $minimumSize = $this->dataHelper->minImageSizeProcessed();
            if ($afterSize > 0 && $afterSize < $beforeSize && $afterSize > $minimumSize) {
                file_put_contents($destinationPath, $compressedImage);
                $percentageSaved = $this->getPercentageSaved($bytesSaved, $beforeSize);
                if ($this->dataHelper->isDebuggingEnabled()) {
                    $this->dataHelper->addInfo($destinationPath . '-- Saved ' . $percentageSaved);
                }
            } else {                
                $percentageSaved = $this->getPercentageSaved(0, $beforeSize);
                if ($this->dataHelper->isDebuggingEnabled() ) {
                    if ($afterSize < $minimumSize) {
                        $this->dataHelper->addInfo($destinationPath . '-- couldn\'t optimise file ' . $percentageSaved);
                    } else {
                        $this->dataHelper->addInfo($destinationPath . '-- No optimisation required ' . $percentageSaved);
                    }
                }

            }
            return true;
        } catch (Exception $e) {
            throw new LocalizedException(__('Unable to save -> ' . $destinationPath . ' error -> ' . $e->getMessage()));
        }
        return false;
    }

    /**
     * Get the filesize for the specified file.
     *
     * @param SplFileInfo $file
     *
     * @return bool|int
     */
    protected function retrieve_remote_file_size($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        curl_close($ch);
        return $size;
    }

    /**
     * Calculate the bytes saved.
     *
     * @return null|string
     */
    public function getBytesSaved($sizeBefore, $sizeAfter)
    {
        return $sizeBefore - $sizeAfter;
    }

    /**
     * Calculate the percentage saved.
     * @param int $sizeSaved
     * @param int $sizeBefore
     * @return string
     */
    public function getPercentageSaved($sizeSaved, $sizeBefore)
    {
        if ($sizeSaved == 0) {
            return '0 %';
        } else {
            return round(($sizeSaved / $sizeBefore) * 100) . ' %';
        }
    }
}