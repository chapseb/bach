<?php

namespace Bach\AdministrationBundle\Controller;

use Bach\AdministrationBundle\Entity\Helpers\ViewObjects\CoreStatus;
use Bach\AdministrationBundle\Entity\Dashboard\Dashboard;
use Bach\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;

use Bach\AdministrationBundle\Entity\Helpers\FormBuilders\FieldsForm;
use Bach\AdministrationBundle\Entity\Helpers\FormObjects\Fields;
use Bach\AdministrationBundle\Entity\SolrSchema\XMLProcess;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * Administration index
     *
     * @return void
     */
    public function indexAction()
    {
        return $this->render('AdministrationBundle:Default:index.html.twig');
    }

    /**
     * Solr cores administration interface
     *
     * @return void
     */
    public function coreadminAction()
    {
        return $this->render('AdministrationBundle:Default:coreadmin.html.twig');
    }

    /**
     * Solr performance interface
     *
     * @return void
     */
    public function performanceAction()
    {
        return $this->render('AdministrationBundle:Default:performance.html.twig');
    }

    /**
     * Displays dashboard
     *
     * @return void
     */
    public function dashboardAction()
    {
        $coreName = $this->getRequest()->request->get('selectedCore');
        if (!isset($coreName)) {
            $coreName = 'none';
        }
        $configreader = $this->container->get('bach.administration.configreader');
        $sca = new SolrCoreAdmin($configreader);
        $coreNames = $sca->getStatus()->getCoreNames();
        $coresInfo = array();
        foreach ($coreNames as $cn) {
            $coresInfo[$cn] = new CoreStatus($sca, $cn);
        }
        $session = $this->getRequest()->getSession();
        $session->set('coreNames', $coreNames);
        $session->set('coreName', $coreName);
        if ($coreName == 'none') {
            $session->set('xmlP', null);
        } else {
            $session->set('xmlP', new XMLProcess($sca, $coreName));
        }

        $db = new Dashboard();

        $SystemFreeVirtualMemory=$db->getSystemFreeVirtualMemory();
        $SystemUsedVirtualMemory = $db->getSystemTotalVirtualMemory()-$SystemFreeVirtualMemory;
        $SystemFreeSwapMemory=$db->getSystemFreeSwapMemory();
        $SystemUsedSwapMemory=$db->getSystemTotalSwapMemory()-$SystemFreeSwapMemory;

        $tmpCoreNames = $sca->getTempCoresNames();

        return $this->render(
            'AdministrationBundle:Default:dashboard.html.twig',
            array(
                'coreName'                  => $coreName,
                'coreNames'                 => $coreNames,
                'tmpCoresNames'             => $tmpCoreNames,
                'SystemUsedVirtualMemory'   => $SystemUsedVirtualMemory,
                'SystemFreeVirtualMemory'   => $SystemFreeVirtualMemory,
                'SystemUsedSwapMemory'      => $SystemUsedSwapMemory,
                'SystemFreeSwapMemory'      => $SystemFreeSwapMemory,
                'coresInfo'                 => $coresInfo
            )
        );
    }
}
