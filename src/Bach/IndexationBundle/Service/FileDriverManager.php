<?php
/**
 * Convert an input file into a UniversalFileFormat object
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

namespace Bach\IndexationBundle\Service;

use Bach\IndexationBundle\Entity\FileDriver;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\EntityManager;
use Bach\IndexationBundle\Entity\UniversalFileFormat;
use Bach\IndexationBundle\Entity\DataBag;

/**
 * Convert an input file into a UniversalFileFormat object
 *
 * PHP version 5
 *
 * @category Indexation
 * @package  Bach
 * @author   Anaphore PI Team <uknown@unknown.com>
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class FileDriverManager
{
    private $_drivers = array();
    private $_conf = array();
    private $_fileFormatFactory;
    private $_preProcessorFactory;
    private $_entityManager;

    /**
     * Constructor
     *
     * @param UniversalFileFormatFactory $fileFormatFactory   Universal file
     *                                                          format Factory
     * @param PreProcessorFactory        $preProcessorFactory Pre processor factory
     * @param EntityManager              $entityManager       The entity manager
     */
    public function __construct(UniversalFileFormatFactory $fileFormatFactory,
        PreProcessorFactory $preProcessorFactory, EntityManager $entityManager
    ) {
        $this->_importConfiguration();
        $this->_searchDrivers();
        $this->_fileFormatFactory = $fileFormatFactory;
        $this->_preProcessorFactory = $preProcessorFactory;
        $this->_entityManager = $entityManager;
    }

    /**
     * Convert an input file into UniversalFileFormat object
     *
     * @param DataBag $bag          Data bag
     * @param string  $format       File format
     * @param string  $preprocessor Preprocessor, if any (defaults to null)
     *
     * @return UniversalFileFormat the normalized file object
     */
    public function convert(DataBag $bag, $format, $preprocessor = null)
    {
        if ( !array_key_exists($format, $this->_drivers) ) {
            throw new \DomainException('Unsupported file format: ' . $format);
        } else {
            $mapper = null;
            $universalFileFormatClass = null;
            $doctrine_entity = null;

            //Importation configuration du driver
            if (array_key_exists('drivers', $this->_conf)) {
                if (array_key_exists($format, $this->_conf['drivers'])) {
                    if (array_key_exists('mapper', $this->_conf['drivers'][$format])) {
                        try {
                            $reflection = new \ReflectionClass($this->_conf['drivers'][$format]['mapper']);
                            if ( in_array('Bach\IndexationBundle\DriverMapperInterface', $reflection->getInterfaceNames())) {
                                $mapper = $reflection->newInstance();
                            }
                        } catch (\RuntimeException $e) {
                            throw $e;
                        }
                    }

                    if ( array_key_exists('universalfileformat', $this->_conf['drivers'][$format]) ) {
                        try {
                            $reflection = new \ReflectionClass($this->_conf['drivers'][$format]['universalfileformat']);
                            $universalFileFormatClass = $this->_conf['drivers'][$format]['universalfileformat'];
                            $doctrine_entity = $this->_conf['drivers'][$format]['doctrine'];
                        }
                        catch (\RuntimeException $e) {
                            throw $e;
                        }
                    }

                    if ( array_key_exists('preprocessor', $this->_conf['drivers'][$format])
                        && is_null($preprocessor)
                    ) {
                        $preprocessor = $this->_conf['drivers'][$format]['preprocessor'];
                    }
                }
            }

            $driver = $this->_drivers[$format];

            if ( !is_null($preprocessor) ) {
                $bag = $this->_preProcessorFactory->preProcess(
                    $bag,
                    $preprocessor
                );
            }

            $results = $driver->process($bag);
            if (!is_null($mapper)) {
                foreach ($results as $key=>$result) {
                    $results[$key] = $mapper->translate($result);
                }
            }
        }

        $output = array();
        $description = null;
        foreach ($results as $result) {
            if ( $description !== null ) {
                $result['archdesc'] = $description;
            }

            $repo = $this->_entityManager->getRepository($doctrine_entity);

            $exists = null;
            if ( isset($result['fragmentid']) ) {
                $exists = $repo->findOneByFragmentid($result['fragmentid']);
            } else {
                $exists = $repo->findOneById($result['id'][0]['value']);
            }

            $out = $this->_fileFormatFactory->build(
                $result,
                $universalFileFormatClass,
                $exists
            );

            $removed = $out->getRemoved();
            if ( count($removed) > 0 ) {
                foreach ( $removed as $r ) {
                    $this->_entityManager->remove($r);
                }
            }

            if ( isset($result['fragmentid'])
                && $result['fragmentid'] === $result['headerId'] . '_description'
            ) {
                $description = $out;
            }
            $output[] = $out;
        }
        return $output;
    }

    /**
     * Register a FileDriver into the manager
     *
     * @param FileDriver $driver Driver to register
     *
     * @return void
     */
    private function _registerDriver(FileDriver $driver)
    {
        if ( !array_key_exists($driver->getFileFormatName(), $this->_drivers) ) {
            $this->_drivers[$driver->getFileFormatName()] = $driver;
        } else {
            throw new \RuntimeException(
                "A driver for this file format is already loaded"
            );
        }
    }

    /**
     * Perform a research of available drivers
     *
     * @return void
     */
    private function _searchDrivers()
    {
        $finder = new Finder();
        $finder->directories()->in(__DIR__.'/../Entity/Driver')->depth('== 0');

        foreach ($finder as $file) {
            try {
                $reflection = new \ReflectionClass(
                    'Bach\IndexationBundle\Entity\Driver\\'.
                    $file->getBasename().'\\Driver'
                );
                if ('Bach\IndexationBundle\Entity\FileDriver' == $reflection->getParentClass()->getName()) {
                    $configuration = array();
                    if ( array_key_exists('drivers', $this->_conf) ) {
                        if ( array_key_exists(strtolower($file->getBasename()), $this->_conf['drivers'])) {
                            $configuration = $this->_conf['drivers'][strtolower($file->getBasename())];
                        }
                    }
                    $this->_registerDriver(
                        $reflection->newInstanceArgs(array($configuration))
                    );
                }
            } catch(\RuntimeException $e) {
                throw $e;
            }
        }
    }

    /**
     * Import drivers configuration file
     *
     * @return void
     */
    private function _importConfiguration()
    {
        $this->_conf = Yaml::parse(
            __DIR__.'/../Resources/config/drivers.yml'
        );
    }
}
