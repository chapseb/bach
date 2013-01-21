<?php
namespace Anph\AdministrationBundle\Entity\SolrSchema;

use Doctrine\ORM\Mapping as ORM;
	
/**
 * @ORM\Entity
 * @ORM\Table(name="SolrXMLFile")
 */
class SolrXMLFile
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $SolrXMLFileID;
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $core;
    
    /**
     * @ORM\OneToMany(targetEntity="SolrXMLElement", mappedBy="file", cascade={"remove", "persist"})
     */
    protected $elements;
    
    /**
     * @ORM\Column(type="text")
     */
    protected $path;
    
    /**
     * Constructor
     */
    public function __construct($core = null, $path = null)
    {
        $this->elements = new \Doctrine\Common\Collections\ArrayCollection();
        $this->core = $core;
        $this->path = $path;
    }
    
    /**
     * Get SolrXMLFileID
     *
     * @return integer 
     */
    public function getSolrXMLFileID()
    {
        return $this->SolrXMLFileID;
    }
    
    /**
     * Set name
     *
     * @param string $core
     * @return SolrXMLFile
     */
    public function setCore($core)
    {
        $this->core = $core;
    
        return $this;
    }
    
    /**
     * Get core's name
     *
     * @return string 
     */
    public function getCore()
    {
        return $this->core;
    }
    
    /**
     * Set path
     *
     * @param string $path
     * @return SolrXMLFile
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }
    
    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * Add elements
     *
     * @param \Anph\AdministrationBundle\Entity\SolrSchema\SolrXMLElement $elements
     * @return SolrXMLFile
     */
    public function addElement(\Anph\AdministrationBundle\Entity\SolrSchema\SolrXMLElement $element)
    {
        $this->elements[] = $element;
    
        return $this;
    }
    
    /**
     * Remove elements
     *
     * @param \Anph\AdministrationBundle\Entity\SolrSchema\SolrXMLElement $elements
     */
    public function removeElement(\Anph\AdministrationBundle\Entity\SolrSchema\SolrXMLElement $element)
    {
        $this->elements->removeElement($element);
    }
    
    /**
     * Get elements
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getElements()
    {
        return $this->elements;
    }
}