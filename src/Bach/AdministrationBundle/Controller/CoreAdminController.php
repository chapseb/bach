<?php
/**
 * Bach core administration controller
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

use Bach\AdministrationBundle\Entity\Helpers\ViewObjects\CoreStatus;
use Bach\AdministrationBundle\Entity\SolrSchema\XMLProcess;
use Bach\AdministrationBundle\Entity\Helpers\FormBuilders\CoreCreationForm;
use Symfony\Component\HttpFoundation\Request;
use Bach\AdministrationBundle\Entity\Helpers\FormObjects\CoreCreation;
use Bach\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Bach core administration controller
 *
 * PHP version 5
 *
 * @category Administration
 * @package  Bach
 * @author   Johan Cwiklinski <johan.cwiklinski@anaphore.eu>
 * @license  Unknown http://unknown.com
 * @link     http://anaphore.eu
 */
class CoreAdminController extends Controller
{

    /**
     * Refresh core informations
     *
     * @param Request $request Request
     *
     * @return void
     */
    public function refreshAction(Request $request)
    {
        $session = $request->getSession();
        if ($request->isMethod('GET')) {
            $form = $this->createForm(
                new CoreCreationForm($this->_getAvailableCores())
            );
        } else {
            $btn = $request->request->get('createCoreOk');
            if (isset($btn)) {
                $form = $this->_createCore($request, $session->get('xmlP'));
            } elseif (isset($btn)) {
                echo 'ELSIF';
            }
        }
        return $this->render(
            'AdministrationBundle:Default:coreadmin.html.twig',
            array(
                'form' => $form->createView(),
                'coreName' => $session->get('coreName'),
                'coreNames' => $session->get('coreNames'),
                'coreStatus' => new CoreStatus($session->get('coreName'))
            )
        );
    }

    /**
     * Create a new core
     *
     * @param Request    $request Request
     * @param XMLProcess $xmlP    XML process
     *
     * @return Form
     */
    private function _createCore(Request $request, XMLProcess $xmlP)
    {
        $session = $request->getSession();
        $sca = new SolrCoreAdmin();
        $cc = new CoreCreation();
        $form = $this->createForm(
            new CoreCreationForm($this->_getAvailableCores()),
            $cc
        );
        $form->bind($request);
        //$fields = $this->_getFieldsFromDataBase($cc->core);

        $em = $this->getDoctrine()->getManager();
        $orm_name = 'Bach\IndexationBundle\Entity\UniversalFileFormat';
        if ( $cc->core === 'EADUniversalFileFormat' ) {
            $orm_name .= '\EADFileFormat';
        }

        $result = $sca->create(
            $cc->core,
            $cc->name,
            $cc->core,
            $orm_name,
            $em
        );

        if ( count($sca->getWarnings()) > 0) {
            foreach ( $sca->getWarnings() as $w ) {
                $this->get('session')->getFlashBag()->add('warnings', $w);
            }
        }

        if ($result != false && $result->isOk()) {
            $coreNames = $sca->getStatus()->getCoreNames();
            $session->set('coreNames', $coreNames);
        }
        return $form;
    }

    /**
     * Retrieve core fields from table
     *
     * @param array $tableName Name of the table
     *
     * @return array
     */
    private function _getFieldsFromDataBase($tableName)
    {
        $sql = "SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_NAME ='" . $tableName . "'";
        $connection = $this->getDoctrine()->getConnection();
        $result = $connection->query($sql);
        $res = array();
        while ( $row = $result->fetch() ) {
            $res[]=$row['name'];
        }
        return $res;
    }

    /**
     * Retrieve tables names form database
     *
     * @return array
     */
    private function _getTableNamesFromDataBase()
    {
        $sql = "SELECT table_name AS name FROM information_schema.tables WHERE table_schema LIKE 'bach'";
        $connection = $this->getDoctrine()->getConnection();
        $result = $connection->query($sql);
        $res = array();
        while ( $row = $result->fetch() ) {
            $res[] = $row['name'];
        }
        return $res;
    }

    /**
     * Retrieve existing core types
     *
     * @param SolrCoreAdmin $sca Core admin instance
     *
     * @return array
     */
    private function _getAvailableCores(SolrCoreAdmin $sca = null)
    {
        if ($sca == null) {
            $sca = new SolrCoreAdmin();
        }
        $tableNames = $this->_getTableNamesFromDataBase();
        $availableCores = array();
        foreach ($tableNames as $t) {
            $subStr = substr($t, strlen($t) - 6);
            if ( $subStr === 'Format' ) {
                $availableCores[$t] = $t;
            }
        }
        return $availableCores;
    }

    /**
     * Get available cores
     *
     * @param SolrCoreAdmin $sca
     *
     * @deprecated
     * 
     * @return array
     */
    private function getAvailableCoreNames(SolrCoreAdmin $sca = null)
    {
        if ($sca == null) {
            $sca = new SolrCoreAdmin();
        }
        $runningCores = $sca->getStatus()->getCoreNames();
        $tableNames = $this->_getTableNamesFromDataBase();
        $availableCores = array();
        foreach ($tableNames as $t) {
            $subStr = substr($t, strlen($t) - 6);
            if (!in_array($t, $runningCores) && $subStr === 'Format') {
                $availableCores[$t] = $t;
            }
        }
        return $availableCores;
    }
}