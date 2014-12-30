<?php
/**
 * @author Glenn Schmidt <glenn@codeacious.com>
 * @version $Id: Shell.php 1364 2014-12-30 13:16:47Z glenn $
 */

namespace Codeacious\ExternalProcess;

/**
 * Performs operations with an external command shell.
 */
class Shell
{
    /**
     * Execute a shell command.
     * 
     * @param string $cmd
     * @param array $args
     * @param string $output If provided, this variable will receive the contents of stdout. If not
     *    provided, stdout will print to the terminal.
     * @param string $errStr If provided, this variable will receive the contents of stderr. If not
     *    provided, stderr will print to the terminal.
     * @return boolean True if the command succeeded (exit status 0), false if it returned an error.
     */
    public static function exec($cmd, array $args=array(), &$output=null, &$errStr=null)
    {
        if (func_num_args() >= 4)
            $res = self::getResult($cmd, $args, $output, $errStr);
        elseif (func_num_args() == 3)
            $res = self::getResult($cmd, $args, $output);
        else
            $res = self::getResult($cmd, $args);
        return ($res === 0);
    }
    
    /**
     * Execute a shell command, and return its exit status.
     * 
     * @param string $cmd
     * @param array $args
     * @param string $output If provided, this variable will receive the contents of stdout. If not
     *    provided, stdout will print to the terminal.
     * @param string $errStr If provided, this variable will receive the contents of stderr. If not
     *    provided, stderr will print to the terminal.
     * @return integer The exit status of the process.
     */
    public static function getResult($cmd, array $args=array(), &$output=null, &$errStr=null)
    {
        $printOutput = (func_num_args() < 3);
        $printErrors = (func_num_args() < 4);
        $proc = new Process($cmd, $args, $printOutput, $printErrors);
        if (!$printOutput)
        {
            $output = '';
            while (!$proc->isEOF())
                $output .= $proc->read();
            $output = rtrim($output, "\n");
        }
        if (!$printErrors)
        {
            $errStr = '';
            while (!$proc->isEOF(Process::STREAM_ERR))
                $errStr .= $proc->read(Process::STREAM_ERR);
            $errStr = rtrim($errStr, "\n");
        }
        return $proc->close();
    }

    /**
     * Execute a shell command, connected to PHP's standard input, output and error streams. If
     * running PHP as an interactive CLI command, the user will be able to interact with the
     * external program.
     *
     * @param string $cmd
     * @param array $args
     * @return integer The exit status of the process.
     */
    public static function getResultInteractive($cmd, array $args=array())
    {
        $proc = new Process($cmd, $args, true, true, true);
        return $proc->close();
    }
    
    /**
     * Execute a shell command, and return its output.
     * 
     * @param string $cmd
     * @param array $args
     * @param integer $exitStatus If provided, this variable will receive the exit status of the
     *    process.
     * @param string $errStr If provided, this variable will receive the contents of stderr. If not
     *    provided, stderr will print to the terminal.
     * @return string The contents of stdout.
     */
    public static function getOutput($cmd, array $args=array(), &$exitStatus=null, &$errStr=null)
    {
        $output = '';
        if (func_num_args() >= 4)
            $res = self::getResult($cmd, $args, $output, $errStr);
        else
            $res = self::getResult($cmd, $args, $output);
        
        if (func_num_args() >= 3)
            $exitStatus = $res;
        return $output;
    }
    
    /**
     * Append a set of arguments to a base command and return a string that is ready to pass to a
     * shell. Special characters in each argument will be escaped as appropriate.
     * 
     * @param string $cmd
     * @param array $args
     * @return string
     */
    public static function escapeArgs($cmd, array $args=array())
    {
        $finalCmd = $cmd;
        foreach ($args as $arg)
            $finalCmd .= ' '.escapeshellarg($arg);
        return $finalCmd;
    }
}
