<?php

/**
 * Bach Kernel
 *
 * PHP version 5
 *
 * Copyright (c) 2014, Anaphore
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *     (1) Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *     (2) Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *     (3)The name of the author may not be used to
 *    endorse or promote products derived from this software without
 *    specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category Core
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

if ( file_exists(__DIR__ . '/config/constants.php') ) {
    include_once __DIR__ . '/config/constants.php';
}

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Aws\S3\S3Client;

/**
 * Bach Kernel
 *
 * @category Core
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class AppKernel extends Kernel
{


    public function boot()
    {
        parent::boot();

        $container = $this->getContainer();
        if ($container->getParameter('aws.s3') == true) {
            $s3 = new S3Client(
                [
                    'version' => $container->getParameter('aws.version'),
                    'region'  => $container->getParameter('aws.region'),
                    'credentials' => array(
                        'key' => $container->getParameter('aws.credentials.key'),
                        'secret' => $container->getParameter('aws.credentials.secret')
                        )
                ]
            );
            $s3->registerStreamWrapper('s3');
        }
    }

    /**
     * Returns an array of bundles to registers.
     *
     * @return BundleInterface[] An array of bundle instances.
     */
    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new JMS\AopBundle\JMSAopBundle(),
            new JMS\DiExtraBundle\JMSDiExtraBundle($this),
            new JMS\SecurityExtraBundle\JMSSecurityExtraBundle(),
            new Nelmio\SolariumBundle\NelmioSolariumBundle(),
            new Lsw\GettextTranslationBundle\LswGettextTranslationBundle(),
            new Bach\IndexationBundle\BachIndexationBundle(),
            new Bach\AdministrationBundle\AdministrationBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new Fp\OpenIdBundle\FpOpenIdBundle(),
            //Sonata dependencies
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Sonata\jQueryBundle\SonatajQueryBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Sonata\AdminBundle\SonataAdminBundle(),
            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
            new Sonata\UserBundle\SonataUserBundle('FOSUserBundle'),
            new Application\Sonata\UserBundle\ApplicationSonataUserBundle(),
            new Bach\HomeBundle\BachHomeBundle(),
            //new Lsw\MemcacheBundle\LswMemcacheBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new Pix\SortableBehaviorBundle\PixSortableBehaviorBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Bach\ExposBundle\ExposBundle(),
            new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
            new FM\ElfinderBundle\FMElfinderBundle(),
            new Presta\SitemapBundle\PrestaSitemapBundle(),
            new Liip\DoctrineCacheBundle\LiipDoctrineCacheBundle(),
            new Liip\ThemeBundle\LiipThemeBundle(),
            new Jns\Bundle\XhprofBundle\JnsXhprofBundle(),
            new Aws\Symfony\AwsBundle(),
            new Snc\RedisBundle\SncRedisBundle()
        );

        if (in_array($this->getEnvironment(), array('dev', 'test', 'console'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
            $bundles[] = new atoum\AtoumBundle\AtoumAtoumBundle();
            //$bundles[] = new Jns\Bundle\XhprofBundle\JnsXhprofBundle();
            $bundles[] = new Elao\WebProfilerExtraBundle\WebProfilerExtraBundle();
        }

        return $bundles;
    }

    /**
     * Loads the container configuration
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     *
     * @return void
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(
            __DIR__ . '/config/config_' . $this->getEnvironment() . '.yml'
        );
    }

    /**
     * Gets the cache directory.
     *
     * @return string The cache directory
     */
    public function getCacheDir()
    {
        if ( defined('BACH_CACHE_DIR') ) {
            return BACH_CACHE_DIR . $this->environment;
        } else {
            return parent::getCacheDir();
        }
    }

    /**
     * Gets the log directory.
     *
     * @return string The log directory
     */
    public function getLogDir()
    {
        if ( defined('BACH_LOG_DIR') ) {
            return BACH_LOG_DIR;
        } else {
            return parent::getLogDir();
        }
    }
}
