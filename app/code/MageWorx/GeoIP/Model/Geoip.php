<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\GeoIP\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Symfony\Component\Config\Definition\Exception\Exception;
use GeoIp2\Database\Reader as GeoIP2Reader;

/**
 * Geoip model.
 *
 * Work with customer location
 */
class Geoip
{
    const DS = DIRECTORY_SEPARATOR;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Module registry
     *
     * @var \Magento\Framework\Component\ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var \Magento\Framework\Filesystem\Directory\Write
     */
    protected $directory;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $driver;

    /**
     * @var \Laminas\Http\ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Magento\Directory\Model\ResourceModel\Country\Collection
     */
    protected $countryCollection;

    /**
     * @var \MageWorx\GeoIP\Helper\Info
     */
    protected $helperInfo;

    /**
     * @var \MageWorx\GeoIP\Helper\Data
     */
    protected $helperData;

    /**
     * @var \MageWorx\GeoIP\Helper\Database
     */
    protected $helperDatabase;

    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var File
     */
    protected $io;

    /**
     * Geoip constructor.
     *
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Component\ComponentRegistrarInterface $componentRegistrar
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Driver\File $driver
     * @param \Laminas\Http\ClientFactory $clientFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection
     * @param \MageWorx\GeoIP\Helper\Info $helperInfo
     * @param \MageWorx\GeoIP\Helper\Data $helperData
     * @param \MageWorx\GeoIP\Helper\Database $helperDatabase
     * @param \Magento\Framework\Escaper $escaper
     * @param File $io
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Customer\Model\Session                           $customerSession,
        \Magento\Framework\ObjectManagerInterface                 $objectManager,
        \Magento\Framework\Component\ComponentRegistrarInterface  $componentRegistrar,
        \Magento\Framework\Filesystem                             $filesystem,
        \Magento\Framework\Filesystem\Driver\File                 $driver,
        \Laminas\Http\ClientFactory                               $clientFactory,
        \Magento\Directory\Model\ResourceModel\Country\Collection $countryCollection,
        \MageWorx\GeoIP\Helper\Info                               $helperInfo,
        \MageWorx\GeoIP\Helper\Data                               $helperData,
        \MageWorx\GeoIP\Helper\Database                           $helperDatabase,
        \Magento\Framework\Escaper                                $escaper,
        File                                                      $io
    ) {
        $this->customerSession    = $customerSession;
        $this->objectManager      = $objectManager;
        $this->componentRegistrar = $componentRegistrar;
        $this->directory          = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->driver             = $driver;
        $this->clientFactory      = $clientFactory;
        $this->countryCollection  = $countryCollection;
        $this->helperInfo         = $helperInfo;
        $this->helperData         = $helperData;
        $this->helperDatabase     = $helperDatabase;
        $this->escaper            = $escaper;
        $this->io                 = $io;
    }

    /**
     * Get customer location data by IP
     *
     * @param string $ip
     * @return array
     */
    protected function getGeoIpLocation(string $ip): array
    {
        $dbPath       = $this->helperDatabase->getDatabasePath();
        $isCityDbType = $this->helperDatabase->isCityDbType();

        if (!$this->driver->isExists($dbPath)) {
            return [];
        }

        $data   = ['ip' => $ip];
        $reader = new GeoIP2Reader($dbPath, ['en']);

        try {
            if ($isCityDbType) {
                $record = $reader->city($ip);
                if ($record) {
                    $data['code']        = $record->registeredCountry->isoCode;
                    $data['country']     = $record->registeredCountry->name;
                    $data['region']      = $record->mostSpecificSubdivision->name;
                    $data['region_code'] = $record->mostSpecificSubdivision->isoCode;
                    $data['city']        = $record->city->name;
                    $data['postal_code'] = $record->postal->code;
                    $data['latitude']    = $record->location->latitude;
                    $data['longitude']   = $record->location->longitude;
                }
            } else {
                $record          = $reader->country($ip);
                $data['code']    = $record->registeredCountry->isoCode;
                $data['country'] = $record->registeredCountry->name;
            }
        } catch (\Exception $e) {
            $data['code']    = null;
            $data['country'] = null;
        }

        return $data;
    }

    /**
     * Loads location data by ip and puts it in object
     *
     * @param string $ip
     * @return DataObject
     */
    public function getLocation(string $ip): DataObject
    {
        $helperCountry = $this->objectManager->get('MageWorx\GeoIP\Helper\Country');
        $data          = $this->getGeoIpLocation($ip);

        if (isset($data['code'])) {
            $data['flag'] = $helperCountry->getFlagPath($data['code']);
        }

        $obj = new \Magento\Framework\DataObject($data);

        return $obj;
    }

    /**
     * Return current customer loaction
     *
     * @return DataObject|null
     */
    public function getCurrentLocation(): ?DataObject
    {
        $session = $this->customerSession;
        $ip      = $this->objectManager->get('MageWorx\GeoIP\Helper\Customer')->getCustomerIp();

        if (!$session->getCustomerLocation() ||
            !$session->getCustomerLocation()->getIp() !== $ip ||
            !$session->getCustomerLocation()->getCode()) {
            $data = $this->getLocation($ip);
            $session->setCustomerLocation($data);
        }

        return $session->getCustomerLocation() instanceof DataObject ? $session->getCustomerLocation() : null;
    }

    /**
     * Set current customer location
     *
     * @param string $countryCode
     * @return void
     */
    public function changeCurrentLocation(string $countryCode): void
    {
        $session = $this->customerSession;
        if ($location = $session->getCustomerLocation()) {
            $location->setCode($countryCode);
            $session->setCustomerLocation($location);
        }
    }

    /**
     * Return all available countries and regions
     *
     * @return array
     */
    public function getAvailableCountriesAndRegions(): array
    {
        $maxmindData        = $this->helperInfo->getMaxmindData();
        $countries          = $this->countryCollection->loadByStore();
        $availableCountries = [];

        foreach ($countries as $country) {
            if (!isset($maxmindData[$country->getId()])) {
                continue;
            }
            $availableCountries[$country->getId()] = $maxmindData[$country->getId()];
        }

        return $availableCountries;
    }

    /**
     * Downloads file from remote server
     *
     * @param string $source Ex:
     *     https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=sVSKTOS0WxEQ4mi4&suffix=tar.gz
     * @param string $destination
     * @return Geoip
     * @throws LocalizedException
     */
    public function downloadFile(string $source, string $destination, ?bool $createBackupFlag = null): Geoip
    {
        $dir = \MageWorx\GeoIP\Helper\Database::DB_PATH;
        if (!$this->directory->isExist($dir)) {
            $this->directory->create($dir);
        }

        $newFile = $this->directory->getDriver()->fileOpen($destination, "wb");

        if (!$newFile) {
            throw new LocalizedException(
                __("Can't create new file. Check that folder %s has write permissions", $dir)
            );
        }

        /** @var \Laminas\HTTP\Client $client */
        $client = $this->clientFactory->create();
        $client->setUri($source);

        /**
         * See https://dev.maxmind.com/geoip/updating-databases
         * In January 2024, ... We will redirect requests using HTTPS on the following hostname:
         * mm-prod-geoip-databases.a2649acb697e2c09b632799562c076f2.r2.cloudflarestorage.com
         */
        $client->setOptions(['maxredirects' => 1, 'timeout' => 120]);

        try {
            $response = $client->setMethod('GET')
                               ->send();

            $status = $response->getStatusCode();
            $body   = $response->getBody();
            if ($status !== 200) {
                throw new LocalizedException(__($this->escaper->escapeHtml($body)));
            }

            $this->directory->getDriver()->fileWrite($newFile, $body);

            if (!$this->directory->getDriver()->isExists($destination)) {
                throw new LocalizedException(
                    __('DataBase source is temporary unavailable')
                );
            }

            $this->directory->getDriver()->fileClose($newFile);

            if ($createBackupFlag) {
                $backupDestination = $destination . \MageWorx\GeoIP\Helper\Database::ARCHIVE_SUFFIX . time();
                $this->directory->getDriver()->copy($destination, $backupDestination);
            }

            $this->unCompressFile($destination, $this->helperDatabase->getDatabasePath());

        } catch (Exception $e) {
            throw $e;
        }

        $this->directory->getDriver()->deleteFile($this->helperDatabase->getTempUpdateFile());

        return $this;
    }

    /**
     * Unpack .tar.gz archive
     *
     * @param string $source
     * @param string $destination
     * @return $this
     * @throws LocalizedException
     */
    protected function unCompressFile(string $source, string $destination): Geoip
    {
        try {
            if (!in_array('phar', \stream_get_wrappers(), true)) {
                stream_wrapper_restore('phar');
            }

            $phar             = new \PharData($source, \RecursiveDirectoryIterator::SKIP_DOTS);
            $pathInfo         = $this->io->getPathInfo($destination);
            $internalFilePath = $this->getInternalFilePath($phar, $pathInfo['basename']);
            $dirName          = $this->driver->getParentDirectory($destination);

            $phar->extractTo($dirName, $internalFilePath, true);

            if (!$this->driver->rename($dirName . self::DS . $internalFilePath, $destination)) {
                throw new LocalizedException();
            } else {
                $this->directory->getDriver()->deleteDirectory(
                    $dirName . self::DS . $this->getInternalDirectory($phar)
                );
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__("We can't extract archive from MaxMind"));
        }

        return $this;
    }

    /**
     * @param \PharData $phar
     * @param string $name
     * @return string
     */
    protected function getInternalFilePath(\PharData $phar, string $name): string
    {
        return $this->getInternalDirectory($phar) . '/' . $this->getInternalFileName($name);
    }

    /**
     * @param \PharData $phar
     * @return string
     */
    protected function getInternalDirectory(\PharData $phar): string
    {
        return $phar->getFileInfo()->getFilename();
    }

    /**
     * Use this function for mapping
     *
     * @param string $name
     * @return string
     */
    protected function getInternalFileName(string $name): string
    {
        return $name;
    }
}
