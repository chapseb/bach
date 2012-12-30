<?php
namespace Anph\AdministrationBundle\Tests\Entity\SolrCore;
use Anph\AdministrationBundle\Entity\SolrCore\SolrCoreAdmin;

class SolrCoreAdminTest extends \PHPUnit_Framework_TestCase
{
    private $sca;
    
    public function setUp() {
        $this->sca = new SolrCoreAdmin();
    }
    
    public function tearDown() {
        unset($this->sca);
    }
    
    public function testCreate() {
        $response = $this->sca->create('coreTestCreate');
        $this->assertTrue($response === false ? false : $response->isOk()); 
        $this->sca->delete('coreTestCreate');
    }
    
    public function testGetStatusAllCore() {
        $response = $this->sca->getStatus();
        $this->assertTrue($response->isOk());
    }
    
    public function testGetStatusOneCore() {
        $this->sca->create('coreTestStatusOneCore');
        $response = $this->sca->getStatus('coreTestStatusOneCore');
        $this->assertTrue($response->isOk());
        $this->sca->delete('coreTestStatusOneCore');
    }
    
    public function testReload() {
        $this->sca->create('coreTestReload');
        $response = $this->sca->reload('coreTestReload');
        if ($response->isOk()) {
            $this->assertTrue(true);
        } else {
            echo '#####RELOAD#####';
            echo 'MSG :' . $response->getMessage() . '#####';
            echo 'CODE :' . $response->getCode() . '#####';
            echo 'TRACE :' . $response->getTrace() . '#####';
            $this->assertTrue(false);
        }
        $this->sca->delete('coreTestReload');
    }
    
    public function testRename() {
        $this->sca->create('coreTestRename');
        $response = $this->sca->rename('coreTestRename', 'coreNewTestRename');
        $this->assertTrue($response === false ? false : $response->isOk());
        $this->sca->unload('coreNewTestRename');
        $this->sca->delete('coreTestRename');
    }
    
    public function testSwap() {
        $this->sca->create('coreTestSwap1');
        $this->sca->create('coreTestSwap2');
        $response = $this->sca->swap('core1', 'core0');
        $this->assertTrue($response->isOk());
        $this->sca->delete('coreTestSwap1');
        $this->sca->delete('coreTestSwap2');
    }
    
    public function testUnload() {
        $this->sca->create('coreTestUnload');
        $response = $this->sca->unload('coreTestUnload');
        $this->assertTrue($response->isOk());
        $this->sca->delete('coreTestUnload');
    }
    
    public function testDeleteIndex() {
        $this->sca->create('coreTestDeleteIndex');
        $response = $this->sca->delete('coreTestDeleteIndex', SolrCoreAdmin::DELETE_INDEX);
        $this->assertTrue($response === false ? false : $response->isOk());
        $this->sca->delete('coreTestDeleteIndex');
    }
    
    public function testDeleteData() {
        $this->sca->create('coreTestDeleteData');
        $response = $this->sca->delete('coreTestDeleteData', SolrCoreAdmin::DELETE_DATA);
        $this->assertTrue($response === false ? false : $response->isOk());
        $this->sca->delete('coreTestDeleteData');
    }
    
    public function testDeleteCore() {
        $this->sca->create('coreTestDeleteCore');
        $response = $this->sca->delete('coreTestDeleteCore');
        $this->assertTrue($response);
    }
}
