<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Tests\Unit\Core;

use oemodulenameoxorder_parent;
use oxAttribute;
use OxidEsales\Eshop\Core\Exception\SystemComponentException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Core\ShopIdCalculator;
use OxidEsales\EshopCommunity\Core\UtilsObject;
use oxNewDummyUserModule2_parent;
use oxNewDummyUserModule_parent;
use Psr\Log\Test\TestLogger;

class modOxUtilsObject_oxUtilsObject extends \oxUtilsObject
{
    public function setClassNameCache($aValue)
    {
        parent::$_aInstanceCache = $aValue;
    }

    public function getClassNameCache()
    {
        return parent::$_aInstanceCache;
    }
}

/**
 * Test class for Unit_Core_oxutilsobjectTest::testGetObject() test case
 */
class _oxutils_test
{
    /**
     * Does nothing
     *
     * @param bool $a [optional]
     * @param bool $b [optional]
     * @param bool $c [optional]
     * @param bool $d [optional]
     * @param bool $e [optional]
     *
     * @return null
     */
    public function __construct($a = false, $b = false, $c = false, $d = false, $e = false)
    {
    }
}

class oxModuleUtilsObject extends \oxUtilsObject
{
    public function getActiveModuleChain($aClassChain)
    {
        return parent::getActiveModuleChain($aClassChain);
    }
}

class UtilsobjectTest extends \OxidEsales\TestingLibrary\UnitTestCase
{

    /**
     * Tear down the fixture.
     */
    public function tearDown(): void
    {
        oxRemClassModule(\OxidEsales\EshopCommunity\Tests\Unit\Core\modOxUtilsObject_oxUtilsObject::class);

        $oArticle = oxNew('oxArticle');
        $oArticle->delete('testArticle');

        Registry::get("oxConfigFile")->setVar("sShopDir", $this->getConfigParam('sShopDir'));
        Registry::set('logger', getLogger());

        parent::tearDown();
    }

    /**
     * Test, that the method getInstance creates the object of the correct current edition namespace.
     */
    public function testEditionSpecificObjectIsCreatedCorrect()
    {
        $utilsObject = \OxidEsales\Eshop\Core\UtilsObject::getInstance();
        $expectedClass = \OxidEsales\Eshop\Core\UtilsObject::class;
        $this->assertEquals($expectedClass, get_class($utilsObject));
    }

    /**
     * Testing oxUtilsObject object creation.
     *
     * @return null
     */
    public function testGetObject()
    {
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1, 2) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1, 2, 3) instanceof _oxutils_test);
        $this->assertTrue(oxNew(\OxidEsales\EshopCommunity\Tests\Unit\Core\_oxutils_test::class, 1, 2, 3, 4) instanceof _oxutils_test);
    }

    public function testOxNewSettingParameters()
    {
        $oArticle = oxNew('oxarticle', array('aaa' => 'bbb'));

        $this->assertTrue($oArticle instanceof \OxidEsales\EshopCommunity\Application\Model\Article);
        $this->assertTrue(isset($oArticle->aaa));
        $this->assertEquals('bbb', $oArticle->aaa);
    }

    public function testOxNewClassExtendingWhenClassesDoesNotExists()
    {
        $this->expectException(SystemComponentException::class);
        $structure = array(
            'modules' => array(
                'oxNewDummyModule.php' => '<?php class oxNewDummyModule {}',
                'oxNewDummyUserModule.php' => '<?php class oxNewDummyUserModule extends oxNewDummyUserModule_parent {}',
            )
        );
        $vfsStream = $this->getVfsStreamWrapper();
        $vfsStream->createStructure($structure);
        $fakeShopDir = $vfsStream->getRootPath();

        $aModules = array(strtolower('oxNewDummyModule') => 'oxNewDummyUserModule&notExistingClass');

        include_once $fakeShopDir . "/modules/oxNewDummyModule.php";

        $config = $this->getConfig();

        Registry::getUtilsObject()->setModuleVar("aModules", $aModules);
        $logger = new TestLogger();
        Registry::set('logger', $logger);

        $config->setConfigParam("aModules", $aModules);

        $configFile = Registry::get("oxConfigFile");
        $realShopDir = $configFile->getVar('sShopDir');
        $configFile->setVar('sShopDir', $fakeShopDir);

        $oNewDummyModule = oxNew("oxNewDummyModule");

        $configFile->setVar('sShopDir', $realShopDir);

        $this->assertTrue($oNewDummyModule instanceof \oxNewDummyModule);
        $this->assertTrue($oNewDummyModule instanceof \oxNewDummyUserModule);
        $this->assertFalse($oNewDummyModule instanceof \oxNewDummyUserModule2);

        $this->assertTrue(
            $logger->hasErrorThatContains(
                'Module class notExistingClass not found. Module ID notExistingClass'
            )
        );
    }

    public function testOxNewCreationOfNonExistingClassContainsClassNameInExceptionMessage()
    {
        $this->expectException(SystemComponentException::class);
        $this->expectExceptionMessage('non_existing_class');

        oxNew("non_existing_class");
    }

    /**
     * No real test possible, but at least generated ids should be different
     */
    public function testGenerateUid()
    {
        $id1 = Registry::getUtilsObject()->generateUid();
        $id2 = Registry::getUtilsObject()->generateUid();
        $this->assertNotEquals($id1, $id2);
    }

    public function testResetInstanceCacheSingle()
    {
        $oTestInstance = modOxUtilsObject_oxUtilsObject::getInstance();
        $aInstanceCache = array("oxArticle" => oxNew('oxArticle'), "oxattribute" => new oxAttribute());
        $oTestInstance->setClassNameCache($aInstanceCache);

        $oTestInstance->resetInstanceCache("oxArticle");

        $aGotInstanceCache = $oTestInstance->getClassNameCache();

        $this->assertEquals(1, count($aGotInstanceCache));
        $this->assertTrue($aGotInstanceCache["oxattribute"] instanceof \OxidEsales\EshopCommunity\Application\Model\Attribute);
    }

    public function testResetInstanceCacheAll()
    {
        $oTestInstance = modOxUtilsObject_oxUtilsObject::getInstance();
        $aInstanceCache = array("oxArticle" => oxNew('oxArticle'), "oxattribute" => new oxAttribute());
        $oTestInstance->setClassNameCache($aInstanceCache);

        $oTestInstance->resetInstanceCache();

        $aGotInstanceCache = $oTestInstance->getClassNameCache();

        $this->assertEquals(0, count($aGotInstanceCache));
    }

    public function testGetClassName_classExist_moduleClassReturn()
    {
        $sClassName = 'oxorder';
        $sClassNameWhichExtends = $sClassNameExpect = 'oemodulenameoxorder';
        $oUtilsObject = $this->_prepareFakeModule($sClassName, $sClassNameWhichExtends);

        $this->assertSame($sClassNameExpect, $oUtilsObject->getClassName($sClassName));
    }

    public function testGetClassName_classNotExistDoNotDisableModuleOnError_errorThrow()
    {
        $sClassName = 'oxorder';
        $sClassNameWhichExtends = 'oemodulenameoxorder_different4';
        $oUtilsObject = $this->_prepareFakeModule($sClassName, $sClassNameWhichExtends);

        $oUtilsObject->getClassName($sClassName);
    }

    public function testUtilsObjectConstructedWithCEShopId()
    {
        if ($this->getTestConfig()->getShopEdition() == 'EE') {
            $this->markTestSkipped('This test is for Community/Professional edition only.');
        }

        $expectedShopId = ShopIdCalculator::BASE_SHOP_ID;

        $utilsObject = UtilsObject::getInstance();
        $realShopId = $utilsObject->getShopId();

        $this->assertSame($expectedShopId, $realShopId);
    }

    private function _prepareFakeModule($class, $extension)
    {
        $wrapper = $this->getVfsStreamWrapper();
        Registry::get("oxConfigFile")->setVar("sShopDir", $wrapper->getRootPath());
        $wrapper->createStructure(array(
            'modules' => array(
                $extension . '.php' => "<?php class $extension extends {$extension}_parent {}"
            )
        ));

        $oUtilsObject = Registry::getUtilsObject();
        $oUtilsObject->setModuleVar('aModules', array($class => $extension));

        return $oUtilsObject;
    }

    /**
     * Make a module, which classname is not the expected one. I.e. class name does not match file name.
     * The parent class name matches the expections i.e. {$extension}_parent
     *
     * @param $class
     * @param $extension
     *
     * @return \OxidEsales\Eshop\Core\UtilsObject
     */
    private function prepareFakeModuleNonExistentClass($class, $extension)
    {
        $wrapper = $this->getVfsStreamWrapper();
        Registry::get("oxConfigFile")->setVar("sShopDir", $wrapper->getRootPath());
        $wrapper->createStructure(array(
            'modules' => array(
                $extension . '.php' => "<?php class {$extension}NonExistent extends {$extension}_parent {}"
            )
        ));

        $oUtilsObject = Registry::getUtilsObject();
        $oUtilsObject->setModuleVar('aModules', array($class => $extension));

        return $oUtilsObject;
    }
}
