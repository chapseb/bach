<?php
/**
 * Bach default administration controller
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
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski  <johan.cwiklinski@anaphore.eu>
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */

namespace Bach\AdministrationBundle\Controller;

use Bach\AdministrationBundle\Entity\Helpers\ViewObjects\CoreStatus;
use Bach\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;
use Bach\AdministrationBundle\Entity\SolrAdmin\Infos;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

/**
 * Bach default administration controller
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski  <johan.cwiklinski@anaphore.eu>
 * @author   Sebastien Chaptal <sebastien.chaptal@anaphore.eu>
 * @license  BSD 3-Clause http://opensource.org/licenses/BSD-3-Clause
 * @link     http://anaphore.eu
 */
class DefaultController extends Controller
{
    /**
     * Displays dashboard
     *
     * @return void
     */
    public function dashboardAction()
    {
        $aws = $this->container->getParameter('aws.s3');
        return $this->render(
            'AdministrationBundle:Default:dashboard.html.twig',
            array(
                'template' => 'main',
                'aws'      => $aws
            )
        );
    }

    /**
     * Displays remaining time for generated thumbs
     *
     * @return void
     */
    public function generateImagesAction()
    {
        $queueUrl = $this->container->getParameter('aws.sqs_url');

        $logger = $this->get('logger');
        $aws = $this->container->getParameter('aws.s3');
        if ($aws) {
            $version      = $this->container->getParameter('aws.version');
            $region       = $this->container->getParameter('aws.region');
            $nbImageByMin = $this->container->getParameter('aws.sqs_nbimages');

            $client = new SqsClient(
                [
                    'region'  => $region,
                    'version' => $version,
                    'credentials' => array(
                        'key' =>
                            $this->container->getParameter('aws.credentials.key'),
                        'secret' =>
                            $this->container->getParameter('aws.credentials.secret')
                    )
                ]
            );
            try {
                $result = $client->getQueueAttributes(
                    [
                        'AttributeNames' => array('ApproximateNumberOfMessages'),
                        'QueueUrl' => $queueUrl // REQUIRED
                    ]
                );
                $date = new \DateTime();
                $nbImageToTreat = intval(
                    $result->get('Attributes')['ApproximateNumberOfMessages']
                );
                $numberMin = $nbImageToTreat/$nbImageByMin;
                $numberSec = $numberMin * 60;
                $add = 'PT'.strval($numberSec).'S';
                $date->add(new \DateInterval($add));
                $resultDate = $date->format('Y/m/d H:i:s');
            } catch (AwsException $e) {
                // output error message in logs if fails
                $logger->error('Exception Aws SQS : '.  $e->getMessage(). "\n");
            }
        } else {
            $resultDate = null;
        }

        return $this->render(
            'AdministrationBundle:Default:dashboard.html.twig',
            array(
                'template'    => 'generateImages',
                'aws'         => $aws,
                'clockending' => $resultDate
            )
        );
    }
}
