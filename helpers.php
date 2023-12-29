<?php
// Дополнительные функции
use Shasoft\Dump\Log;
use Shasoft\Dump\Browse;
use Shasoft\Console\Console;
use Shasoft\Filesystem\Path;
use Shasoft\Filesystem\FsTransform;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

if (!function_exists('s_dd')) {
    // Установить обработку ошибок
    function s_set_catch_handler(): void
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
    // Получить место вызова
    function s_get_trace_call(int $index = 0): array|false
    {
        $ret = [];
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 0);
        if (array_key_exists($index, $traces)) {
            $trace = $traces[$index];
            // Имя файла
            if (array_key_exists('file', $trace)) {
                $ret['file'] = Path::normalize(FsTransform::get($trace['file']));
            }
            // Номер строки
            if (array_key_exists('line', $trace)) {
                $ret['line'] = $trace['line'];
            }
        }
        if (empty($ret)) {
            $ret = false;
        } else {
            // Имя файла
            if (array_key_exists('file', $ret)) {
                $ret['all'] = $ret['file'];
            } else {
                $ret['all'] = '';
            }
            // Имя строки
            if (array_key_exists('line', $ret)) {
                if (!empty($ret['all'])) {
                    $ret['all'] .= ':';
                }
                $ret['all'] .= $ret['line'];
            }
        }
        // Трансформировать имя
        return $ret;
    }

    // Вызов функции и вывод на экран строки из которой она вызвана
    function s_call_fn(string|\Closure|null $fn, array $args): void
    {
        // Получить номер строки
        $trace = s_get_trace_call(2);
        if ($trace !== false) {

            $isConsole = Console::is();
            if ($isConsole) {
                /*
                if (is_string($fn) && $fn == 'dd') {
                    // Вывести сообщение
                    Console::writeLn('<title>Имя файла скопировано в буфер обмена</>');
                    // Копировать имя файла в буфер обмена
                    Console::copyToClipboard($filepath);
                }
                //*/
                // Вывести на экран имя файла
                Console::writeLn('file: <file>' . $trace['file'] . ":" . $trace['line'] . '</>');
            } else {
                echo "<div style='border:1px solid red;padding:0'>";
                echo '<div>file: <b style="color:green">' . $trace['file'] . '</b>:<b style="color:green">' . $trace['line'] . '</b></div>';
            }
        }
        if (is_callable($fn)) {
            call_user_func_array($fn, $args);
        }
        if (!$isConsole) {
            echo "</div>";
        }
    }
    // Функция отладки
    /*
    function s_dd_log(...$args)
    {
        $dbg = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $dbg = $dbg[0];
        //
        $fname = File::fname($dbg['file']);
        $fname = str_replace(['/', '\\', '.'], '_', $fname) . '-' . $dbg['line'];
        //
        ob_start();
        call_user_func_array('var_dump', $args);
        $text = ob_get_contents();
        ob_end_clean();
        // Сохранить
        File::save(base_path(s_generic_path("log/" . $fname . '.log')), $text);
        // Завершить
        exit(1);
    }
    //*/
    // Функция отладки
    function s_print(...$args): void
    {
        s_call_fn(function (...$args) {
            foreach ($args as $arg) {
                Console::writeLn("\t" . (string)$arg);
            }
        }, $args);
    }
    // Функция отладки
    function s_dd(...$args): void
    {
        s_call_fn('dd', $args);
    }
    // Функция отладки по условию
    function s_dump_has($has, ...$args): void
    {
        if ($has) {
            s_call_fn('dump', $args);
        }
    }
    // Функция отладки
    function s_dump(...$args): void
    {
        s_call_fn('dump', $args);
    }
    // Получить переменные в виде HTMl кода
    function s_dump_html(...$args): string
    {
        $ret = '';
        if (!empty($args)) {
            $cloner = new VarCloner();
            $dumper = new HtmlDumper();
            $output = fopen('php://memory', 'r+b');
            //
            $data = count($args) == 1 ? $args[0] : $args;
            //
            $dumper->dump($cloner->cloneVar($data), $output);
            //
            $ret = stream_get_contents($output, -1, 0);
        }
        return $ret;
    }

    // Функция маркировки строки
    function s_mark(...$args)
    {
        if (empty($args)) {
            s_call_fn(null, []);
        } else {
            s_call_fn('dump', $args);
        }
    }
    // Функция остановки
    function s_stop()
    {
        s_call_fn(function () {
            Console::writeLn('<fg=red>stop!</>');
            exit(1);
        }, []);
    }
    // Вывести стек вызовов
    function s_trace()
    {
        $traces = \debug_backtrace(0);
        //s_dd($traces);
        if (Console::is()) {
            foreach ($traces as $trace) {
                if (array_key_exists('file', $trace)) {
                    Console::writeLn('<file>' . FsTransform::get($trace['file']) . ':' . $trace['line'] . '</>');
                } else {
                    if (array_key_exists('class', $trace)) {
                        Console::writeLn($trace['class'] . $trace['type'] . $trace['function']);
                    }
                }
            }
        } else {
            s_dump($traces);
        }
    }
    /*
    // Вставить в HTML имя файла со строкой + кнопку копирования в буфер обмена
    function s_filename_line_html(string $filename, string|int|null $line = null): string
    {
        //
        static $is_include_js = false;
        // Если код ещё не включался
        if ($is_include_js === false) {
            // то включить его
            echo "<script>";
            echo file_get_contents(__DIR__ . '/dump/copyToClipboard.js');
            echo "</script>";
            // Выставить флаг что код был включен
            $is_include_js = true;
        }
        $filename = str_replace('\\', '/', $filename);
        $ret = $filename;
        if (!is_null($line)) {
            $ret .= ':<strong>' . $line . "</strong>";
        }
        $ret .= "&nbsp;<img src=\"data:image/ТИП;base64," .
            base64_encode(file_get_contents(__DIR__ . '/dump/copyToClipboard.png')) .
            "\" style=\"cursor:pointer\" onclick=\"copyToClipboard('" . str_replace('\\', '/', $filename) . ":" . $line . "')\">";
        return $ret;
    }    
    */
    // Вывод ошибки
    function s_dump_error(string $title, $e)
    {
        if (Console::is()) {
            Console::writeLn('<title>' . $title . '</>');
            Console::writeLn('Сообщение: <error>' . $e->getMessage() . '</>');
            Console::writeLn('  Файл: <file>' . $e->getFile() . '</>:' . $e->getLine());
            Console::writeLn('Стек вызовов:');
            foreach ($e->getTrace() as $trace) {
                Console::writeLn('  <file>' . Log::trace_file($trace) . '</>:' . Log::trace_line($trace));
            }
            // Вывести справочное сообщение
            Console::writeLn('');
            Console::writeLn('В <title>VSCode</>:');
            Console::writeLn(' 1. Нажмите <info>Ctrl+P</> и <info>Ctrl+V</> для перехода к файлу');
            Console::writeLn(' 2. Нажмите <info>Ctrl+G</> и введите номер строки <info>' . $e->getLine() . '</> для перехода к строке');
            // Копировать в буфер обмена
            Console::copyToClipboard(FsTransform::get($e->getFile()));
        } else {
            echo '<h1 style="color:red">' . $title . '</h1>';
            echo "<div><strong>Сообщение</strong>: <strong style=\"color:#DE3163\">" . $e->getMessage() . "</strong></div><br/>";
            //echo "<div><strong>Место возникновения</strong>: " . s_filename_line_html($e->getFile(), $e->getLine()) . "</div>";
            echo "<div><strong>Место возникновения</strong>: " . Browse::copyToClipboard($e->getFile() . ':' . $e->getLine()) . "</div>";
            $items = $e->getTrace();
            echo "<ul>";
            foreach ($items as $item) {
                if (array_key_exists('file', $item)) {
                    //echo "<li>" . s_filename_line_html($item['file'], $item['line']) . "</li>";
                    echo "<li>" . Browse::copyToClipboard($item['file'] . ':' . $item['line']) . "</li>";
                } else {
                    s_dump($item);
                }
            }
            echo "</ul>";
            s_dump($items);
        }
        s_dump($e);
    }
    // Запуск с выводом ошибок
    function s_dump_run(\Closure $cb)
    {
        try {
			$cb();
        } catch (\Exception $e) {
            s_dump_error('Исключение ' . get_class($e), $e);
            exit(1);
        } catch (\Error $e) {
            s_dump_error('Ошибка ' . get_class($e), $e);
            exit(2);
        }
    }	
}
