<?php

namespace Anph\IndexationBundle\Entity\Driver\EAD\Parser\XML;

class EADArchDesc
{
	private $xpath;
	private $values = array();
	
	public function __construct(\DOMXPath $xpath, \DOMNode $archDescNode, $fields){
		$this->xpath = $xpath;
		$this->parse($archDescNode,$fields);
	}
	
	public function __get($name){
		if(array_key_exists(strtolower($name),$this->values)){
			return $this->values[strtolower($name)];
		}else{
			return null;
		}
	}
	
	public function getValues(){
		return $this->values;
	}
	
	private function parse(\DOMNode $archDescNode,$fields){
		$results = array();
		$results['root'] = array();
		
		$rootFields = $fields['root'];
		
		foreach($rootFields as $field){
			$nodes = $this->xpath->query($field,$archDescNode);
		
			if($nodes->length > 0){
				$results['root'][$field] = array();
				foreach($nodes as $key=>$node){
					$results['root'][$field][] = array("value"			=>	$node->nodeValue,
														"attributes"	=>	$this->parseAttributes($node->attributes));
				}
			}
		}
		
		// Let's go parsing C node recursively
		
		$results['c'] = $this->recursiveCNodeSearch($archDescNode->getElementsByTagName('dsc')->item(0),
											$fields['c']);
		
		$this->values = $results;
	}
	
	private function recursiveCNodeSearch(\DOMNode $rootNode, $fields, $parents = array()){
		$results = array();
	
		$cNodes = $this->xpath->query('c',$rootNode);
	
		foreach($cNodes as $cNode){
			$results[$cNode->getAttribute('id')] = array(	"parents"	=>	$parents);
			
			foreach($fields as $field){
				$nodes = $this->xpath->query($field,$cNode);
				$results[$cNode->getAttribute('id')][$field] = array();
				
				if($nodes->length > 0){					
					foreach($nodes as $key=>$node){
						$results[$cNode->getAttribute('id')][$field][] = array("value"			=>	$node->nodeValue,
																				"attributes"	=>	$this->parseAttributes($node->attributes));
					}
				}
			}
				
			if($this->xpath->query('c',$cNode)->length > 0){
				$results = array_merge(	$results,
										$this->recursiveCNodeSearch($cNode, 
																	$fields, 
																	array_merge($parents,
																				array($cNode->getAttribute('id'))
																				)
																	)
									);
				//$results['c'] = $this->recursiveCNodeSearch($cNode, $fields);
			}
		}
		return $results;
	}
	
	private function parseAttributes(\DOMNamedNodeMap $attributes){
		$return = array();
		
		foreach ($attributes as $key=>$attribute) {
			$return[$key] = $attribute->value;
		}
		
		return $return;
	}
}