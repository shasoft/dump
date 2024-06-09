<?php

namespace Shasoft\Dump;

class Debug
{
    // Режим работы
    static private bool $enable = false;
    // Установить режим работы
    static public function enable(bool $value): void
    {
        self::$enable = $value;
    }
    // Логирование вызовов
    static public function log(callable $cb, int $index = 0): mixed
    {
        if (self::$enable) {
            $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2 + $index);
            //s_dump($traces);
            $trace = array_pop($traces);
            //s_dump($trace);
            // Есть возврат результата
            $hasReturn = true;
            $typeReturnTitle = '';
            // Имя
            $name = $trace['function'];
            if (array_key_exists('class', $trace)) {
                $name = $trace['class'] . htmlspecialchars($trace['type']) . $name;
                //
                $refClass = new \ReflectionClass($trace['class']);
                $refMethod = $refClass->getMethod($trace['function']);
                $args = $refMethod->getParameters();
                if ($refMethod->hasReturnType()) {
                    $typeReturn = (string)$refMethod->getReturnType();
                    if ($typeReturn == 'void') {
                        $hasReturn = false;
                    }
                    //
                    if (!empty($typeReturn)) {
                        $typeReturnTitle = ' title="' . htmlspecialchars($typeReturn) . '" ';
                    }
                }
            }
            echo '<div>';
            echo '<div style="padding:4px;border: 1px solid Purple"><strong style="color:Purple">' . $name . '</strong>';
            if (array_key_exists('args', $trace)) {
                foreach ($trace['args'] as $index => $arg) {
                    $typeTitle = '';
                    $byRefHtml = '';
                    if ($index < count($args)) {
                        //
                        if ($args[$index]->isPassedByReference()) {
                            $byRefHtml = '<strong style="color:red" title="ByRef">&</strong>&nbsp;';
                        }
                        //
                        $type = (string)$args[$index]->getType();
                        if (!empty($type)) {
                            $typeTitle = ' title="' . htmlspecialchars($type) . '" ';
                        }
                    }
                    echo '<div style="padding-left:8px" ' . $typeTitle . '>' . $byRefHtml . DebugHtml::value($arg) . '</div>';
                }
                /*
                if ($trace['function'] == 'checkInCache') {
                    s_dd($trace['args'], $refMethod);
                }
                //*/
            }
            echo '<div style="padding-left:8">';
        }
        $ret = $cb();
        if (self::$enable) {
            echo '</div>';
            if ($hasReturn) {
                echo '<div style="padding-left:8px" ' . $typeReturnTitle . '><hr/>' . DebugHtml::value($ret) . '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        return $ret;
    }
}
