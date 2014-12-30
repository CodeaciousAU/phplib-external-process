<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: ProcessTest.php 858 2014-05-08 14:58:20Z glenn $
 */

namespace CodeaciousTest\ExternalProcess;

use Codeacious\ExternalProcess\Process;
use PHPUnit_Framework_TestCase as TestCase;

class ProcessTest extends TestCase
{
    public function setUp()
    {}
    
    /**
     * @test
     */
    public function readOnly()
    {
        $process = new Process('/bin/ls', array('/'));
        $this->assertFalse($process->isEOF());
        while (!$process->isEOF())
        {
            $data = $process->read();
            $this->assertInternalType('string', $data);
        }
        $exitCode = $process->close();
        $this->assertInternalType('integer', $exitCode);
        $this->assertEquals(0, $exitCode);
    }
    
    /**
     * @test
     */
    public function interactive()
    {
        $process = new Process('ftp');
        $process->writeLine("help");
        
        $result = $process->waitForAnyData(1000);
        $this->assertInternalType('integer', $result);
        $this->assertEquals(Process::STREAM_OUT, $result);
        
        $data = '';
        while ($process->isReady())
            $data .= $process->readLine();
        $this->assertTrue(strpos($data, 'open') !== false);
        
        $result = $process->waitForData(500);
        $this->assertFalse($result);
        
        $process->close();
    }
}
