<?php

class ilTestAppEventListenerTest extends ilTestBaseTestCase
{
    public function testConstruct(): void
    {
        $ilTestAppEventListener = new ilTestAppEventListener();
        $this->assertInstanceOf(ilTestAppEventListener::class, $ilTestAppEventListener);
    }
}