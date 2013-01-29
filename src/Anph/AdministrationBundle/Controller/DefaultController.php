<?php

namespace Anph\AdministrationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;
use Anph\AdministrationBundle\Entity\SolrShema\XMLProcess;

//use Anph\AdministrationBundle\Controller\XMLProcess;

class DefaultController extends Controller
{
	public function indexAction()
	{
		//$process = new XMLProcess(__DIR__.'/../Resources/config/schema.xml');
		
		//$process->importXML();
		//$this->get("anph.administration.xmlimport")->importXML(__DIR__.'/../Resources/config/schema.xml');
		return $this->render('AdministrationBundle:Default:index.html.twig');
	}
	
	public function fieldsAction()
	{
		return $this->render('AdministrationBundle:Default:fields.html.twig');
	}

	public function dynamicfieldsAction()
	{
		return $this->render('AdministrationBundle:Default:dynamicfields.html.twig');
	}
	
	public function fieldstypeAction()
	{
		return $this->render('AdministrationBundle:Default:fieldstype.html.twig');
	}
	
	public function tockenizersAction()
	{
		return $this->render('AdministrationBundle:Default:tockenizers.html.twig');
	}
	
	public function analyzersAction()
	{
		return $this->render('AdministrationBundle:Default:analyzers.html.twig');
	}
	
	public function filtersAction()
	{
		return $this->render('AdministrationBundle:Default:filters.html.twig');
	}
	
	public function coreadminAction()
	{
		return $this->render('AdministrationBundle:Default:coreadmin.html.twig');
	}
	
	public function performanceAction()
	{
		return $this->render('AdministrationBundle:Default:performance.html.twig');
	}
	
	public function dashboardAction()
	{
		return $this->render('AdministrationBundle:Default:dashboard.html.twig');
	}



	
}
