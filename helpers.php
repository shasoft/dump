<?php
// Дополнительные функции
use Shasoft\Dump\Log;
use Shasoft\Dump\Browse;
use Shasoft\Filesystem\Path;
use Shasoft\Terminal\Terminal;
use Shasoft\Filesystem\FsTransform;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
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
                if (Terminal::has()) {
                    Terminal::writeLn('<warning>' . $errors[$errno] . '</>:  ' . $message);
                    Terminal::writeLn('    <File>' . FsTransform::get($errorFile) . '</>:' . $errorLine);
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
    function s_call_fn(string|\Closure|null $fn, array $args, int $skipTraces = 0): void
    {
        //
        $colorConsole = '<Info>';
        $isConsole = Terminal::has();
        if ($isConsole) {
            Terminal::writeLn($colorConsole . '*' . str_repeat('>', 80) . '</>');
        }
        // Получить номер строки
        $trace = s_get_trace_call(2 + $skipTraces);
        if ($trace !== false) {
            if ($isConsole) {
                /*
                if (is_string($fn) && $fn == 'dd') {
                    // Вывести сообщение
                    Terminal::writeLn('<title>Имя файла скопировано в буфер обмена</>');
                    // Копировать имя файла в буфер обмена
                    Console::copyToClipboard($filepath);
                }
                //*/
                // Вывести на экран имя файла
                Terminal::writeLn('file: <File>' . $trace['file'] . ":" . $trace['line'] . '</>');
            } else {
                echo "<div style='border:1px solid red;padding:0'>";
                echo '<div>file: <b style="color:green">' . $trace['file'] . '</b>:<b style="color:green">' . $trace['line'] . '</b></div>';
            }
        }
        if (is_callable($fn)) {
            if (empty($args)) {
                if ($isConsole) {
                    Terminal::writeLn('<Fail>Не указаны переменные для вывода</>');
                    Terminal::writeLn('<Fail>No variables specified for output</>');
                } else {
                    echo '<div style="color:red">Не указаны переменные для вывода</div>';
                    echo '<div style="color:red">No variables specified for output</div>';
                }
            } else {
                call_user_func_array($fn, $args);
            }
        }
        if ($isConsole) {
            Terminal::writeLn($colorConsole . '*' . str_repeat('<', 80) . '</>');
        } else {
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
            $isConsole = Terminal::has();
            foreach ($args as $arg) {
                if ($isConsole) {
                    Terminal::writeLn("\t" . (string)$arg);
                } else {
                    echo '<div style="padding:4px">' . (string)$arg . '</div>';
                }
            }
        }, $args);
    }

    class SCliDumper extends CliDumper
    {
        public function s_supportsColors(): bool
        {
            return $this->supportsColors();
        }
    }
    function s_my_dump(...$args): void
    {
        $isConsole = Terminal::has();
        //$isConsole = true;
        if ($isConsole) {
            // "Танцы с бубном" чтобы буферизировать вывод
            // Цвета поддерживаются в режиме по умолчанию?
            $envHasRemove = false;
            $envName = 'TERM_PROGRAM';
            $sdumper = new SCliDumper;
            if ($sdumper->s_supportsColors()) {
                $envHasRemove = (getenv($envName) === false);
                putenv($envName . '=Hyper');
            }
            // Установить переменную чтобы вывродилось "в цвете"
            $cloner = new VarCloner();
            $dumper = new CliDumper();
            foreach ($args as $arg) {
                $str = $dumper->dump($cloner->cloneVar($arg), true);
                echo $str;
            }
            if ($envHasRemove) {
                putenv($envName);
            }
        } else {
            dump(...$args);
        }
    }
    // Функция отладки
    function s_dd(...$args): void
    {
        s_call_fn('s_my_dump', $args);
        exit(1);
    }
    // Функция отладки по условию
    function s_dump_has($has, ...$args): void
    {
        if ($has) {
            s_call_fn('s_my_dump', $args);
        }
    }
    // Функция отладки
    function s_dump(...$args): void
    {
        s_call_fn('s_my_dump', $args);
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
            Terminal::writeLn('<Error>stop!</>');
        }, []);
        exit(1);
    }
    // Вывести стек вызовов
    function s_trace(int $index = 0, bool $onlyFileLine = false)
    {
        $traces = \debug_backtrace(0);
        if ($index > 0) {
            $traces = array_slice($traces, $index);
        }
        //s_dd($traces);
        if (Terminal::has()) {
            foreach ($traces as $trace) {
                if (array_key_exists('file', $trace)) {
                    Terminal::writeLn('<File>' . FsTransform::get($trace['file']) . ':' . $trace['line'] . '</>');
                } else {
                    if (array_key_exists('class', $trace)) {
                        Terminal::writeLn($trace['class'] . $trace['type'] . $trace['function']);
                    }
                }
            }
        } else {
            array_shift($traces);
            if ($onlyFileLine) {
                $traces = array_map(function (array $item) {
                    return ($item['file'] ?? '?') . ':' . ($item['line'] ?? '?');
                }, $traces);
            }
            s_call_fn('dump', [$traces]);
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
        if (Terminal::has()) {
            Terminal::writeLn('<title>' . $title . '</>');
            Terminal::writeLn('Сообщение: <error>' . $e->getMessage() . '</>');
            Terminal::writeLn('  Файл: <File>' . $e->getFile() . '</>:' . $e->getLine());
            Terminal::writeLn('Стек вызовов:');
            foreach ($e->getTrace() as $trace) {
                Terminal::writeLn('  <File>' . Log::trace_file($trace) . '</>:' . Log::trace_line($trace));
            }
            // Вывести справочное сообщение
            Terminal::writeLn('');
            Terminal::writeLn('В <title>VSCode</>:');
            Terminal::writeLn(' 1. Нажмите <info>Ctrl+P</> и <info>Ctrl+V</> для перехода к файлу');
            Terminal::writeLn(' 2. Нажмите <info>Ctrl+G</> и введите номер строки <info>' . $e->getLine() . '</> для перехода к строке');
            Terminal::writeLn('');
            Terminal::writeLn('<title>Trace</>:');
            s_trace();
            // Копировать в буфер обмена
            //Console::copyToClipboard(FsTransform::get($e->getFile()));
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
