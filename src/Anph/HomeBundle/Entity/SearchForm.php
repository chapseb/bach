<?php

namespace Anph\HomeBundle\Entity;

class SearchForm
{
	protected $query;
		
	public function setQuery($query){
		$this->query = $query;
		
		return $this;
	}
	
	public function getQuery(){
		return $this->query;
	}
}