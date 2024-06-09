<?php

namespace Shasoft\Dump;

class DebugInvoke
{
    // Режим работы
    static private array $traces = [];
    // Очистить
    static public function clear(): void
    {
        self::$traces = [];
    }
    // Все значения
    static public function all(): array
    {
        return self::$traces;
    }
    // Все значения
    static public function allStr(): array
    {
        $ret = [];
        foreach (self::$traces as $trace) {
            $ret[] = $trace['str'];
        }
        return $ret;
    }
    // Логирование вызовов
    static public function trace(string $name, array $args, ...$retValue): string
    {
        $flag = JSON_UNESCAPED_SLASHES;
        $ret = $name . '(' . substr(json_encode($args, $flag), 1, -1) . ')';
        if (!empty($retValue)) {
            $ret = $ret . ':' . json_encode($retValue[0], $flag);
        }
        return $ret;
    }
    // Логирование вызовов
    static public function log(callable $cb, int $index = 0): mixed
    {
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2 + $index);
        //s_dump($traces);
        $trace = array_pop($traces);
        // Есть возврат результата
        $hasReturn = true;
        // Имя
        $name = $trace['function'];
        if (array_key_exists('class', $trace)) {
            $name = $trace['class'] . $trace['type'] . $name;
            //
            $refClass = new \ReflectionClass($trace['class']);
            $refMethod = $refClass->getMethod($trace['function']);
            if ($refMethod->hasReturnType()) {
                $typeReturn = (string)$refMethod->getReturnType();
                if ($typeReturn == 'void') {
                    $hasReturn = false;
                }
            }
        }
        //
        $index = count(self::$traces);
        self::$traces[] = [
            'str' => self::trace($name, $trace['args']),
            'name' => $name, 'args' => $trace['args']
        ];
        //
        $ret = $cb();
        //
        if ($hasReturn) {
            self::$traces[$index]['return'] = $ret;
            self::$traces[$index]['str'] = self::trace($name, $trace['args'], $ret);
        }
        //
        return $ret;
    }
    // Сравнить
    static public function compare(...$traces): bool|array
    {
        $ret = false;
        if (count(self::$traces) == count($traces)) {
            $ret = true;
            foreach (self::$traces as $index => $trace) {
                //s_dump($index, $traces);
                if ($trace['str'] != $traces[$index]) {
                    return [$trace['str'], $traces[$index]];
                }
            }
        } else {
            $ret = ['count=' . count(self::$traces), 'count=' . count($traces)];
        }
        return $ret;
    }
}
