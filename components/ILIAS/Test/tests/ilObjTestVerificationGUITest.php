<?php

class ilObjTestVerificationGUITest extends ilTestBaseTestCase
{
    private ilObjTestVerificationGUI $testObj;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addGlobal_ilLoggerFactory();
        $this->addGlobal_ilCtrl();
        $this->addGlobal_ilTabs();
        $this->addGlobal_ilLocator();
        $this->addGlobal_ilUser();
        $this->addGlobal_ilAccess();
        $this->addGlobal_ilToolbar();
        $this->addGlobal_ilRbacAdmin();
        $this->addGlobal_rbacsystem();
        $this->addGlobal_rbacreview();
        $this->addGlobal_ilObjDataCache();

        $this->testObj = new ilObjTestVerificationGUI(
            0,
            1,
            0,
        );
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(ilObjTestVerificationGUI::class, $this->testObj);
    }

    public function testGetType(): void
    {
        $this->assertEquals('tstv', $this->testObj->getType());
    }
}