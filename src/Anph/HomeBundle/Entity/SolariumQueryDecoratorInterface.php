<?php 

namespace Anph\HomeBundle\Entity;

class SolariumQueryDecorator
{
	abstract protected $_targetField;
	
	public function getTargetField(){
		return $this->targetField;
	}
	
	abstract public function decorate(\Solarium_Query_Select $query, $data);
}
?>