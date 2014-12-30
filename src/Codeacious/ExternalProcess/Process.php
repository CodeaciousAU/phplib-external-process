<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: Process.php 1364 2014-12-30 13:16:47Z glenn $
 */

namespace Codeacious\ExternalProcess;

/**
 * An external program that is being executed.
 */
class Process
{
    const STREAM_IN = 0;
    const STREAM_OUT = 1;
    const STREAM_ERR = 2;
    
    /**
     * @var resource Process resource created with proc_open()
     */
    protected $proc;
    
    /**
     * @var array Array of resources which represent file descriptors for the process.
     */
    protected $pipes = array();
    
    /**
     * @param string $cmd
     * @param array $args
     * @param boolean $passthroughOutput If true, stdout will print to the terminal. If false, you
     *     will be able to read from stdout through the methods of this class.
     * @param boolean $passthroughErrors If true, stderr will print to the terminal. If false, you
     *     will be able to read from stderr through the methods of this class.
     * @param boolean $readFromTerminal If true, stdin will be connected to the terminal. If false,
     *    you will be able to write to stdin through the methods of this class.
     */
    public function __construct($cmd, array $args=array(), $passthroughOutput=false,
                                $passthroughErrors=false, $readFromTerminal=false)
    {
        $descriptors = array(self::STREAM_IN => array('pipe', 'r'),
                             self::STREAM_OUT => array('pipe', 'w'),
                             self::STREAM_ERR => array('pipe', 'w'));
        if ($passthroughOutput)
            $descriptors[self::STREAM_OUT] = STDOUT;
        if ($passthroughErrors)
            $descriptors[self::STREAM_ERR] = STDERR;
        if ($readFromTerminal)
            $descriptors[self::STREAM_IN] = STDIN;
        
        $this->proc = proc_open(
            Shell::escapeArgs($cmd, $args),
            $descriptors,
            $this->pipes
        );
        if (!is_resource($this->proc))
            throw new Exception('Unable to execute external program "'.$cmd.'"');
    }
    
    /**
     * @param integer $stream One of the STREAM_* constants
     * @return boolean
     */
    public function isEOF($stream=self::STREAM_OUT)
    {
        if (!isset($this->pipes[$stream]))
            throw new Exception('Invalid stream specified');
        return feof($this->pipes[$stream]);
    }
    
    /**
     * Returns true if reading from the given stream (for STREAM_OUT or STREAM_ERR) or writing to
     * the stream (for STREAM_IN) would not block.
     * 
     * @param integer $stream One of the STREAM_* constants
     * @return boolean
     */
    public function isReady($stream=self::STREAM_OUT)
    {
        if (!isset($this->pipes[$stream]))
            throw new Exception('Invalid stream specified');
        
        $r = null; $w = null; $e = null;
        if ($stream == self::STREAM_IN)
            $w = array($this->pipes[$stream]);
        else
            $r = array($this->pipes[$stream]);
        return (stream_select($r, $w, $e, 0) == 1);
    }
    
    /**
     * Wait for data to become available for reading on the given stream.
     * 
     * @param integer $timeout Maximum time to wait, in milliseconds
     * @param integer $stream Either STREAM_OUT or STREAM_ERR
     * @return boolean True if data is available, false if timeout occured
     */
    public function waitForData($timeout, $stream=self::STREAM_OUT)
    {
        if (!isset($this->pipes[$stream]))
            throw new Exception('Invalid stream specified');
        if ($stream != self::STREAM_OUT && $stream != self::STREAM_ERR)
            throw new Exception('Only STREAM_OUT or STREAM_ERR can be used with waitForData()');
        
        $secs = floor($timeout / 1000);
        $usecs = ($timeout % 1000) * 1000;
        $r = array($this->pipes[$stream]);
        $w = null; $e = null;
        return (stream_select($r, $w, $e, $secs, $usecs) == 1);
    }
    
    
    /**
     * Wait for data to become available for reading on either STREAM_OUT or STREAM_ERR.
     * 
     * @param integer $timeout Maximum time to wait, in milliseconds
     * @return integer|boolean Returns the number of the stream that has data available, or false if
     *    timeout occurred
     */
    public function waitForAnyData($timeout)
    {
        $streams = array();
        if (isset($this->pipes[self::STREAM_OUT]))
            $streams[] = $this->pipes[self::STREAM_OUT];
        if (isset($this->pipes[self::STREAM_ERR]))
            $streams[] = $this->pipes[self::STREAM_ERR];
        if (empty($streams))
        {
            throw new Exception('Neither STREAM_OUT nor STREAM_ERR is valid for this process '
                .'(disable passthrough to have access to these streams)');
        }
        
        $secs = floor($timeout / 1000);
        $usecs = ($timeout % 1000) * 1000;
        $w = null; $e = null;
        stream_select($streams, $w, $e, $secs, $usecs);
        
        if (empty($streams))
            return false;
        if ($streams[0] == $this->pipes[self::STREAM_ERR])
            return self::STREAM_ERR;
        return self::STREAM_OUT;
    }
    
    /**
     * @param integer $stream One of the STREAM_* constants
     * @param integer $length Max amount of data to read before returning
     * @return string If the stream has reached EOF, an empty string will be returned
     */
    public function read($stream=self::STREAM_OUT, $length=8192)
    {
        if (!isset($this->pipes[$stream]))
            throw new Exception('Invalid stream specified');
        
        $data = fread($this->pipes[$stream], $length);
        if ($data === false)
            throw new Exception('Unable to read from stream '.$stream);
        
        return $data;
    }
    
    /**
     * @param integer $stream One of the STREAM_* constants
     * @return string If the stream has reached EOF, an empty string will be returned
     */
    public function readLine($stream=self::STREAM_OUT)
    {
        if (!isset($this->pipes[$stream]))
            throw new Exception('Invalid stream specified');
        
        $data = fgets($this->pipes[$stream]);
        if ($data === false)
            throw new Exception('Unable to read from stream '.$stream);
        
        return rtrim($data, "\n");
    }
    
    /**
     * @param string $data The data to write
     * @param integer $stream One of the STREAM_* constants
     * @return void
     */
    public function write($data, $stream=self::STREAM_IN)
    {
        if (!isset($this->pipes[$stream]))
            throw new Exception('Invalid stream specified');
        
        if (fwrite($this->pipes[$stream], $data) === false)
            throw new Exception('Unable to write to stream');
    }
    
    /**
     * @param string $data The line to write
     * @param integer $stream One of the STREAM_* constants
     * @return void
     */
    public function writeLine($data, $stream=self::STREAM_IN)
    {
        $this->write($data."\n", $stream);
    }
    
    /**
     * @return integer The result code of the process
     */
    public function close()
    {
        foreach ($this->pipes as $fd)
            fclose($fd);
        return proc_close($this->proc);
    }
}
