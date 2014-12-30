<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: ShellTest.php 860 2014-05-09 04:55:32Z glenn $
 */

namespace CodeaciousTest\ExternalProcess;

use Codeacious\ExternalProcess\Shell;
use PHPUnit_Framework_TestCase as TestCase;

class ShellTest extends TestCase
{
    public function setUp()
    {}
    
    /**
     * @test
     */
    public function exec()
    {
        $output = null;
        $error = null;
        $result = Shell::exec('ls', array('/'), $output, $error);
        $this->assertEquals(true, $result);
        $this->assertInternalType('string', $output);
        $this->assertInternalType('string', $error);
    }
    
    /**
     * @test
     */
    public function getResult()
    {
        $output = null;
        $error = null;
        $result = Shell::getResult('ls', array('/'), $output, $error);
        $this->assertInternalType('integer', $result);
        $this->assertEquals(0, $result);
        $this->assertInternalType('string', $output);
        $this->assertInternalType('string', $error);
    }
    
    /**
     * @test
     */
    public function getOutput()
    {
        $exitStatus = null;
        $error = null;
        $output = Shell::getOutput('ls', array('/'), $exitStatus, $error);
        $this->assertInternalType('integer', $exitStatus);
        $this->assertEquals(0, $exitStatus);
        $this->assertInternalType('string', $output);
        $this->assertInternalType('string', $error);
    }
    
    /**
     * @test
     */
    public function escapeArgs()
    {
        $result = Shell::escapeArgs('grep', array('Name: ".*"', 'my file'));
        $this->assertEquals('grep \'Name: ".*"\' \'my file\'', $result);
    }
}
