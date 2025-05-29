<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class DebugHelper
{
    /**
     * Log a debug message with context data
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log($message, array $context = [])
    {
        Log::debug($message, $context);
    }
    
    /**
     * Dump and die - prints variable and stops execution
     *
     * @param mixed $data
     * @return void
     */
    public static function dd($data)
    {
        dd($data);
    }
    
    /**
     * Dump to JSON and die
     *
     * @param mixed $data
     * @return void
     */
    public static function jsonDump($data)
    {
        header('Content-Type: application/json');
        die(json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Log to file and continue
     *
     * @param mixed $data
     * @param string $label
     * @return mixed
     */
    public static function logDump($data, $label = 'Debug')
    {
        $output = $label . ': ' . print_r($data, true);
        Log::debug($output);
        return $data;
    }
    
    /**
     * Trace the execution with a stack trace
     *
     * @param string $message
     * @return void
     */
    public static function trace($message = 'Execution trace')
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
        $traceLog = [];
        
        foreach ($trace as $i => $step) {
            if ($i === 0) continue; // Skip the current function
            
            $traceLog[] = sprintf(
                "#%d %s::%s called at [%s:%d]",
                $i,
                isset($step['class']) ? $step['class'] : '',
                $step['function'],
                isset($step['file']) ? $step['file'] : 'unknown',
                isset($step['line']) ? $step['line'] : 0
            );
        }
        
        Log::debug($message, ['trace' => $traceLog]);
    }
} 