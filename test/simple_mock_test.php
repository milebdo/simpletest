<?php
    // $Id$
    
    class TestOfParameterList extends UnitTestCase {
        function TestOfParameterList() {
            $this->UnitTestCase();
        }
        function testEmptyMatch() {
            $list = new ParameterList(array());
            $this->assertTrue($list->isMatch(array()));
            $this->assertFalse($list->isMatch(array(33)));
        }
        function testSingleMatch() {
            $list = new ParameterList(array(0));
            $this->assertFalse($list->isMatch(array(1)));
            $this->assertTrue($list->isMatch(array(0)));
        }
        function testAnyMatch() {
            $list = new ParameterList("");
            $this->assertTrue($list->isMatch(array()));
            $this->assertTrue($list->isMatch(array(1, 2)));
        }
        function testMissingParameter() {
            $list = new ParameterList(array(0));
            $this->assertFalse($list->isMatch(array()));
        }
        function testNullParameter() {
            $list = new ParameterList(array(null));
            $this->assertTrue($list->isMatch(array(null)));
            $this->assertFalse($list->isMatch(array()));
        }
        function testWildcardParameter() {
            $list = new ParameterList(array("wild"), "wild");
            $this->assertFalse($list->isMatch(array()), "Empty");
            $this->assertTrue($list->isMatch(array(null)), "Null");
            $this->assertTrue($list->isMatch(array(13)), "Integer");
        }
        function testIdentityOnly() {
            $list = new ParameterList(array("0"));
            $this->assertFalse($list->isMatch(array(0)));
            $this->assertTrue($list->isMatch(array("0")));
        }
        function testLongList() {
            $list = new ParameterList(array("0", 0, "wild", false), "wild");
            $this->assertTrue($list->isMatch(array("0", 0, 37, false)));
            $this->assertFalse($list->isMatch(array("0", 0, 37, true)));
            $this->assertFalse($list->isMatch(array("0", 0, 37)));
        }
    }
    
    class TestOfCallMap extends UnitTestCase {
        function TestOfCallMap() {
            $this->UnitTestCase();
        }
        function testEmpty() {
            $map = new CallMap("wild");
            $this->assertFalse($map->isMatch("any", array()));
            $this->assertNull($map->findFirstMatch("any", array()));
        }
        function testExactValue() {
            $map = new CallMap("wild");
            $map->addValue(array(0), "Fred");
            $map->addValue(array(1), "Jim");
            $map->addValue(array("1"), "Tom");
            $this->assertTrue($map->isMatch(array(0)));
            $this->assertEqual($map->findFirstMatch(array(0)), "Fred");
            $this->assertTrue($map->isMatch(array(1)));
            $this->assertEqual($map->findFirstMatch(array(1)), "Jim");
            $this->assertEqual($map->findFirstMatch(array("1")), "Tom");
        }
        function testExactReference() {
            $map = new CallMap("wild");
            $ref = "Fred";
            $map->addReference(array(0), &$ref);
            $this->assertEqual($map->findFirstMatch(array(0)), "Fred");
            $this->assertReference($map->findFirstMatch(array(0)), $ref);
        }
        function testWildcard() {
            $map = new CallMap("wild");
            $map->addValue(array("wild", 1, 3), "Fred");
            $this->assertTrue($map->isMatch(array(2, 1, 3)));
            $this->assertEqual($map->findFirstMatch(array(2, 1, 3)), "Fred");
        }
        function testAllWildcard() {
            $map = new CallMap("wild");
            $this->assertFalse($map->isMatch(array(2, 1, 3)));
            $map->addValue("", "Fred");
            $this->assertTrue($map->isMatch(array(2, 1, 3)));
            $this->assertEqual($map->findFirstMatch(array(2, 1, 3)), "Fred");
        }
        function testOrdering() {
            $map = new CallMap("wild");
            $map->addValue(array(1, 2), "1, 2");
            $map->addValue(array(1, 3), "1, 3");
            $map->addValue(array(1), "1");
            $map->addValue(array(1, 4), "1, 4");
            $map->addValue(array("wild"), "Any");
            $map->addValue(array(2), "2");
            $map->addValue("", "Default");
            $map->addValue(array(), "None");
            $this->assertEqual($map->findFirstMatch(array(1, 2)), "1, 2");
            $this->assertEqual($map->findFirstMatch(array(1, 3)), "1, 3");
            $this->assertEqual($map->findFirstMatch(array(1, 4)), "1, 4");
            $this->assertEqual($map->findFirstMatch(array(1)), "1");
            $this->assertEqual($map->findFirstMatch(array(2)), "Any");
            $this->assertEqual($map->findFirstMatch(array(3)), "Any");
            $this->assertEqual($map->findFirstMatch(array()), "Default");
        }
    }
    
    class Dummy {
        function Dummy() {
        }
        function aMethod() {
        }
        function anotherMethod() {
        }
    }
    
    Stub::generate("Dummy");
    Stub::generate("Dummy", "AnotherStubDummy");
    
    class SpecialSimpleStub extends SimpleStub {
        function SpecialSimpleStub($wildcard) {
            $this->SimpleStub($wildcard);
        }
    }
    Stub::setStubBaseClass("SpecialSimpleStub");
    Stub::generate("Dummy", "SpecialStubDummy");
    Stub::setStubBaseClass("SimpleStub");
    
    class TestOfStubGeneration extends UnitTestCase {
        function TestOfStubGeneration() {
            $this->UnitTestCase();
        }
        function testCloning() {
            $stub = &new StubDummy($this);
            $this->assertTrue(method_exists($stub, "aMethod"));
            $this->assertNull($stub->aMethod());
        }
        function testCloningWithChosenClassName() {
            $stub = &new AnotherStubDummy($this);
            $this->assertTrue(method_exists($stub, "aMethod"));
        }
        function testCloningWithDifferentBaseClass() {
            $stub = &new SpecialStubDummy($this);
            $this->assertIsA($stub, "SpecialSimpleStub");
            $this->assertTrue(method_exists($stub, "aMethod"));
        }
    }
    
    class TestOfServerStubReturns extends UnitTestCase {
        function TestOfServerStubReturns() {
            $this->UnitTestCase();
        }
        function testDefaultReturn() {
            $stub = &new StubDummy();
            $stub->setReturnValue("aMethod", "aaa");
            $this->assertIdentical($stub->aMethod(), "aaa");
            $this->assertIdentical($stub->aMethod(), "aaa");
        }
        function testParameteredReturn() {
            $stub = &new StubDummy();
            $stub->setReturnValue("aMethod", "aaa", array(1, 2, 3));
            $this->assertNull($stub->aMethod());
            $this->assertIdentical($stub->aMethod(1, 2, 3), "aaa");
        }
        function testReferenceReturned() {
            $stub = &new StubDummy();
            $object = new Dummy();
            $stub->setReturnReference("aMethod", $object, array(1, 2, 3));
            $this->assertReference($stub->aMethod(1, 2, 3), $object);
        }
        function testWildcardReturn() {
            $stub = &new StubDummy("wild");
            $stub->setReturnValue("aMethod", "aaa", array(1, "wild", 3));
            $this->assertIdentical($stub->aMethod(1, "something", 3), "aaa");
            $this->assertIdentical($stub->aMethod(1, "anything", 3), "aaa");
        }
        function testAllWildcardReturn() {
            $stub = &new StubDummy("wild");
            $stub->setReturnValue("aMethod", "aaa");
            $this->assertIdentical($stub->aMethod(1, 2, 3), "aaa");
            $this->assertIdentical($stub->aMethod(), "aaa");
        }
        function testCallCount() {
            $stub = &new StubDummy();
            $this->assertEqual($stub->getCallCount("aMethod"), 0);
            $stub->aMethod();
            $this->assertEqual($stub->getCallCount("aMethod"), 1);
            $stub->aMethod();
            $this->assertEqual($stub->getCallCount("aMethod"), 2);
        }
        function testMultipleMethods() {
            $stub = &new StubDummy();
            $stub->setReturnValue("aMethod", 100, array(1));
            $stub->setReturnValue("aMethod", 200, array(2));
            $stub->setReturnValue("anotherMethod", 10, array(1));
            $stub->setReturnValue("anotherMethod", 20, array(2));
            $this->assertIdentical($stub->aMethod(1), 100);
            $this->assertIdentical($stub->anotherMethod(1), 10);
            $this->assertIdentical($stub->aMethod(2), 200);
            $this->assertIdentical($stub->anotherMethod(2), 20);
        }
        function testReturnSequence() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "aMethod", "aaa");
            $stub->setReturnValueAt(1, "aMethod", "bbb");
            $stub->setReturnValueAt(3, "aMethod", "ddd");
            $this->assertIdentical($stub->aMethod(), "aaa");
            $this->assertIdentical($stub->aMethod(), "bbb");
            $this->assertNull($stub->aMethod());
            $this->assertIdentical($stub->aMethod(), "ddd");
        }
        function testReturnReferenceSequence() {
            $stub = &new StubDummy();
            $object = new Dummy();
            $stub->setReturnReferenceAt(1, "aMethod", $object);
            $this->assertNull($stub->aMethod());
            $this->assertReference($stub->aMethod(), $object);
            $this->assertNull($stub->aMethod());
        }
        function testComplicatedReturnSequence() {
            $stub = &new StubDummy("wild");
            $object = new Dummy();
            $stub->setReturnValueAt(1, "aMethod", "aaa", array("a"));
            $stub->setReturnValueAt(1, "aMethod", "bbb");
            $stub->setReturnReferenceAt(2, "aMethod", $object, array("wild", 2));
            $stub->setReturnValueAt(2, "aMethod", "value", array("wild", 3));
            $stub->setReturnValue("aMethod", 3, array(3));
            $this->assertNull($stub->aMethod());
            $this->assertEqual($stub->aMethod("a"), "aaa");
            $this->assertReference($stub->aMethod(1, 2), $object);
            $this->assertEqual($stub->aMethod(3), 3);
            $this->assertNull($stub->aMethod());
        }
        function testMultipleMethodSequences() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "aMethod", "aaa");
            $stub->setReturnValueAt(1, "aMethod", "bbb");
            $stub->setReturnValueAt(0, "anotherMethod", "ccc");
            $stub->setReturnValueAt(1, "anotherMethod", "ddd");
            $this->assertIdentical($stub->aMethod(), "aaa");
            $this->assertIdentical($stub->anotherMethod(), "ccc");
            $this->assertIdentical($stub->aMethod(), "bbb");
            $this->assertIdentical($stub->anotherMethod(), "ddd");
        }
        function testSequenceFallback() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "aMethod", "aaa", array('a'));
            $stub->setReturnValueAt(1, "aMethod", "bbb", array('a'));
            $stub->setReturnValue("aMethod", "AAA");
            $this->assertIdentical($stub->aMethod('a'), "aaa");
            $this->assertIdentical($stub->aMethod('b'), "AAA");
        }
        function testMethodInterference() {
            $stub = &new StubDummy();
            $stub->setReturnValueAt(0, "anotherMethod", "aaa");
            $stub->setReturnValue("aMethod", "AAA");
            $this->assertIdentical($stub->aMethod(), "AAA");
            $this->assertIdentical($stub->anotherMethod(), "aaa");
        }
    }
    
    Mock::generate("Dummy");
    Mock::generate("Dummy", "AnotherMockDummy");
    
    class SpecialSimpleMock extends SimpleMock {
        function SpecialSimpleMock(&$test, $wildcard) {
            $this->SimpleMock($test, $wildcard);
        }
    }
    Mock::setMockBaseClass("SpecialSimpleMock");
    Mock::generate("Dummy", "SpecialMockDummy");
    Mock::setMockBaseClass("SimpleMock");
    
    class TestOfMockGeneration extends UnitTestCase {
        function TestOfMockGeneration() {
            $this->UnitTestCase();
        }
        function testCloning() {
            $mock = &new MockDummy($this);
            $this->assertTrue(method_exists($mock, "aMethod"));
            $this->assertNull($mock->aMethod());
        }
        function testCloningWithChosenClassName() {
            $mock = &new AnotherMockDummy($this);
            $this->assertTrue(method_exists($mock, "aMethod"));
        }
        function testCloningWithDifferentBaseClass() {
            $mock = &new SpecialMockDummy($this);
            $this->assertIsA($mock, "SpecialSimpleMock");
            $this->assertTrue(method_exists($mock, "aMethod"));
        }
    }
    
    class TestOfMockReturns extends UnitTestCase {
        function TestOfMockReturns() {
            $this->UnitTestCase();
        }
        function testParameteredReturn() {
            $mock = &new MockDummy($this);
            $mock->setReturnValue("aMethod", "aaa", array(1, 2, 3));
            $this->assertNull($mock->aMethod());
            $this->assertIdentical($mock->aMethod(1, 2, 3), "aaa");
        }
        function testReferenceReturned() {
            $mock = &new MockDummy($this);
            $object = new Dummy();
            $mock->setReturnReference("aMethod", $object, array(1, 2, 3));
            $this->assertReference($mock->aMethod(1, 2, 3), $object);
        }
        function testWildcardReturn() {
            $mock = &new MockDummy($this, "wild");
            $mock->setReturnValue("aMethod", "aaa", array(1, "wild", 3));
            $this->assertIdentical($mock->aMethod(1, "something", 3), "aaa");
            $this->assertIdentical($mock->aMethod(1, "anything", 3), "aaa");
        }
        function testCallCount() {
            $mock = &new MockDummy($this);
            $this->assertEqual($mock->getCallCount("aMethod"), 0);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 1);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 2);
        }
        function testReturnReferenceSequence() {
            $mock = &new MockDummy($this);
            $object = new Dummy();
            $mock->setReturnReferenceAt(1, "aMethod", $object);
            $this->assertNull($mock->aMethod());
            $this->assertReference($mock->aMethod(), $object);
            $this->assertNull($mock->aMethod());
        }
    }
    
    Mock::generate("SimpleTestCase");
    
    class TestOfMockTally extends UnitTestCase {
        function TestOfMockTally() {
            $this->UnitTestCase();
        }
        function testZeroCallCount() {
            $mock = &new MockDummy($this);
            $mock->expectCallCount("aMethod", 0);
            $this->assertTrue($mock->tally(), "Tally");
        }
        function testClearHistory() {
            $mock = &new MockDummy($this);
            $mock->expectCallCount("aMethod", 0);
            $mock->aMethod();
            $this->assertEqual($mock->getCallCount("aMethod"), 1);
            $mock->clearHistory();
            $this->assertTrue($mock->tally(), "Tally");
        }
        function testExpectedCallCount() {
            $mock = &new MockDummy($this);
            $mock->expectCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $this->assertTrue($mock->tally(), "Tally");
        }
        function testFailedCallCount() {
            $mock = &new MockDummy(new MockSimpleTestCase($this));
            $mock->expectCallCount("aMethod", 2);
            $this->assertFalse($mock->tally(), "Empty tally");
            $mock->aMethod();
            $this->assertFalse($mock->tally(), "Bad tally");
            $mock->aMethod();
            $this->assertTrue($mock->tally(), "Good tally");
            $mock->aMethod();
            $this->assertFalse($mock->tally(), "Overrun tally");
        }
    }
    
    class TestOfMockExpectations extends UnitTestCase {
        function TestOfMockExpectations() {
            $this->UnitTestCase();
        }
        function testMaxCalls() {
            $test = &new MockSimpleTestCase($this);
            $test->expectCallCount("assertTrue", 1);
            $mock = &new MockDummy($test);
            $mock->expectMaximumCallCount("aMethod", 2);
            $mock->aMethod();
            $mock->aMethod();
            $mock->aMethod();
            $test->tally();
        }
        function testZeroArguments() {
            $mock = &new MockDummy($this);
            $mock->expectArguments("aMethod", array());
            $mock->aMethod();
        }
        function testExpectedArguments() {
            $mock = &new MockDummy($this);
            $mock->expectArguments("aMethod", array(1, 2, 3));
            $mock->aMethod(1, 2, 3);
        }
        function testFailedArguments() {
            $test = &new MockSimpleTestCase($this, "*");
            $test->expectArguments("assertTrue", array(false, "*"));
            $test->expectCallCount("assertTrue", 1);
            $mock = &new MockDummy($test);
            $mock->expectArguments("aMethod", array("this"));
            $mock->aMethod("that");
            $test->tally();
        }
        function testWildcardArguments() {
            $mock = &new MockDummy($this, "wild");
            $mock->expectArguments("aMethod", array("wild", 123, "wild"));
            $mock->aMethod(100, 123, 101);
        }
        function testSpecificSequence() {
            $mock = &new MockDummy($this);
            $mock->expectArgumentsAt(1, "aMethod", array(1, 2, 3));
            $mock->expectArgumentsAt(2, "aMethod", array("Hello"));
            $mock->aMethod();
            $mock->aMethod(1, 2, 3);
            $mock->aMethod("Hello");
            $mock->aMethod();
        }
        function testFailedSequence() {
            $test = &new MockSimpleTestCase($this);
            $test->expectArguments("assertTrue", array(false, "*"));
            $test->expectCallCount("assertTrue", 2);
            $mock = &new MockDummy($test);
            $mock->expectArgumentsAt(0, "aMethod", array(1, 2, 3));
            $mock->expectArgumentsAt(1, "aMethod", array("Hello"));
            $mock->aMethod(1, 2);
            $mock->aMethod("Goodbye");
            $test->tally();
        }
    }
?>