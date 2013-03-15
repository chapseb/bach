<?php
namespace Anph\AdministrationBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Anph\AdministrationBundle\Entity\Helpers\FormObjects\CopyField;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Anph\AdministrationBundle\Entity\Helpers\FormObjects\CopyFields;
use Anph\AdministrationBundle\Entity\Helpers\FormBuilders\CopyFieldsForm;
use Anph\AdministrationBundle\Entity\SolrSchema\XMLProcess;

class CopyFieldsController extends Controller
{
    public function refreshAction()
    {
        $session = $this->getRequest()->getSession();
        $form = $this->createForm(new CopyFieldsForm(), new CopyFields($session->get('xmlP')));
        return $this->render('AdministrationBundle:Default:copyfields.html.twig', array(
                'form' => $form->createView(),
                'coreName' => $session->get('coreName'),
                'coreNames' => $session->get('coreNames')
        ));
    }
    
    public function addCopyFieldAction(Request $request)
    {
        $session = $this->getRequest()->getSession();
        $copyField = new CopyField();
        $form = $this->createFormBuilder($copyField)->getForm();
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                // If the data is valid, we save the new copy field into the schema.xml file of corresponding core
                $xmlP = $this->session->get('xmlP');
                $dynamicField->addField($xmlP);
                $xmlP->saveXML();
                return $this->redirect($this->generateUrl('administration_copyfields'));
            }
        }
    }
    
    public function removeCopyFieldsAction(Request $request)
    {
    
    }
    
    public function submitAction(Request $request)
    {
        $session = $this->getRequest()->getSession();
        $cf = new CopyFields();
        $form = $this->createForm(new CopyFieldsForm(), $cf);
        if ($request->isMethod('POST')) {
            $form->bind($request);
            if ($form->isValid()) {
                // If the data is valid, we save new field into the schema.xml file of corresponding core
                $cf->save($session->get('xmlP'));
            }
        }
        return $this->render('AdministrationBundle:Default:copyfields.html.twig', array(
                'form' => $form->createView(),
                'coreName' => $session->get('coreName'),
                'coreNames' => $session->get('coreNames')
        ));
    }
}
