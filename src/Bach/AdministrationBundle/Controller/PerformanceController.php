<?php
/**
 * Bach performance controller
 *
 * PHP version 5
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */

namespace Bach\AdministrationBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Bach\AdministrationBundle\Entity\Helpers\FormBuilders\PerformanceForm;
use Bach\AdministrationBundle\Entity\Helpers\FormObjects\Performance;
use Bach\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Bach performance controller
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class PerformanceController extends Controller
{

    /**
     * Refresh
     *
     * @return void
     */
    public function refreshAction()
    {
        $session = $this->getRequest()->getSession();
        $configreader = $this->container->get('bach.administration.configreader');
        $sca = new SolrCoreAdmin($configreader);
        $form = $this->createForm(
            new PerformanceForm(),
            new Performance($sca, $session->get('coreName'))
        );
        return $this->render(
            'AdministrationBundle:Default:performance.html.twig',
            array(
                'form'      => $form->createView(),
                'coreName'  => $session->get('coreName'),
                'coreNames' => $session->get('coreNames')
            )
        );
    }

    /**
     * Submit
     *
     * @return void
     */
    public function submitAction()
    {
        $configreader = $this->container->get('bach.administration.configreader');
        $sca = new SolrCoreAdmin($configreader);
        $session = $this->getRequest()->getSession();
        $perf = new Performance($sca, $session->get('coreName'));
        $form = $this->createForm(
            new PerformanceForm(),
            $perf
        )->bind($this->getRequest());
        $perf->saveAll($session->get('coreName'));

        if ($form->isValid()) {
            $perf->saveAll($session->get('coreName'));
        }
        return $this->render(
            'AdministrationBundle:Default:performance.html.twig',
            array(
                'form'      => $form->createView(),
                'coreName'  => $session->get('coreName'),
                'coreNames' => $session->get('coreNames')
            )
        );
    }
}
