<?php

namespace Shasoft\Dump;

use Shasoft\Console\Console;
use Shasoft\Filesystem\FsTransform;

class Log
{
    // Установить обработчик не фатальных ошибок
    public static function set_warning_handler(): void
    {
        set_error_handler(
            function ($errno, $message, $errorFile, $errorLine) { //Пользовательская функция обработчика ошибок PHP
                static $errors = array( //Формирования массива констант ошибок
                    E_WARNING => 'E_WARNING',
                    E_NOTICE => 'E_NOTICE',
                    E_CORE_WARNING => 'E_CORE_WARNING',
                    E_COMPILE_WARNING => 'E_COMPILE_WARNING',
                    E_USER_ERROR => 'E_USER_ERROR',
                    E_USER_WARNING => 'E_USER_WARNING',
                    E_USER_NOTICE => 'E_USER_NOTICE',
                    E_STRICT => 'E_STRICT',
                    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
                    E_DEPRECATED => 'E_DEPRECATED',
                    E_USER_DEPRECATED => 'E_USER_DEPRECATED'
                );
                if (Console::is()) {
                    Console::writeLn('<warning>' . $errors[$errno] . '</>:  ' . $message);
                    Console::writeLn('    <file>' . FsTransform::get($errorFile) . '</>:' . $errorLine);
                } else {
                    s_dump($errors[$errno], FsTransform::get($errorFile));
                }
            }
        );
    }
    // Вывести ошибку
    public static function error(string $text, int $skip = 0): void
    {
        $hasConsole = Console::is();
        // Добавить пропуск текущего вызова
        $skip++;
        $traces = debug_backtrace();
        foreach ($traces as $trace) {
            if ($skip <= 0) {
                //
                $file = self::trace_file($trace);
                $line = self::trace_line($trace);
                //
                if ($skip == 0) {
                    $fileError = $file;
                    $lineError = $line;
                    if ($hasConsole) {
                        Console::writeLn('<error>Ошибка!</>');
                        Console::writeLn("\n" . $text . "\n");
                        Console::writeLn('  Файл: <file>' . $file . '</>:' . $line);
                        Console::writeLn('Стек вызовов:');
                    }
                }
                if ($hasConsole) {
                    Console::writeLn('  Файл: <file>' . $file . '</>:' . $line);
                }
            }
            $skip--;
        }
        // Вывести справочное сообщение
        /*
        if ($hasConsole) {
            Console::writeLn('');
            Console::writeLn('В <title>VSCode</>:');
            Console::writeLn(' 1. Нажмите <info>Ctrl+P</> и <info>Ctrl+V</> для перехода к файлу');
            Console::writeLn(' 2. Нажмите <info>Ctrl+G</> и введите номер строки <info>' . $lineError . '</> для перехода к строке');
            // Копировать в буфер обмена
            Console::copyToClipboard($fileError);
        }
        //*/
        // Завершить выполнение программы
        exit(1);
    }
    // Вывод
    protected static function _print(\Exception|\Error $e)
    {
        if (Console::is()) {
            Console::writeLn('Ошибка: <error>' . $e->getMessage() . '</>');
            Console::writeLn('  Файл: <file>' . $e->getFile() . '</>:' . $e->getLine());
            Console::copyToClipboard($e->getFile());
            Console::writeLn('Стек вызовов:');
            foreach ($e->getTrace() as $trace) {
                Console::writeLn('  <file>' . self::trace_file($trace) . '</>:' . self::trace_line($trace));
            }
            /*
            // Вывести справочное сообщение
            Console::writeLn('');
            Console::writeLn('В <title>VSCode</>:');
            Console::writeLn(' 1. Нажмите <info>Ctrl+P</> и <info>Ctrl+V</> для перехода к файлу');
            Console::writeLn(' 2. Нажмите <info>Ctrl+G</> и введите номер строки <info>' . $e->getLine() . '</> для перехода к строке');
            // Копировать в буфер обмена
            Console::copyToClipboard(FsTransform::get($e->getFile()));
            //*/
        } else {
            s_dump($e);
        }
        // Завершить выполнение программы
        exit(1);
    }
    // Установить обработчик ошибок
    public static function  set_error_handler(\Closure $cb): void
    {
        try {
            // Вызвать работу программы
            $cb();
        } catch (\Error $e) {
            self::_print($e);
        } catch (\Exception $e) {
            self::_print($e);
        }
    }
    // Имя файла trace
    public static function  trace_file(array $trace): string
    {
        return array_key_exists('file', $trace) ? FsTransform::get($trace['file']) : '/-/';
    }
    // Номер строки trace
    public static function  trace_line(array $trace): string
    {
        return $trace['line'] ?? '/-/';
    }
}
