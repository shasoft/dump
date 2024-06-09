<?php

namespace Shasoft\Dump;

class DebugHtml
{
    // Получить значение в виде HTML
    static public function tag_color(string $value, string $color, string $tag = 'span'): string
    {
        return "<{$tag} style=\"color:{$color}\">" . $value . "</{$tag}>";
    }
    // Получить значение в виде HTML
    static public function value(mixed $value): string
    {
        $ret = var_export($value, true);
        if (is_callable($value)) {
            $refFunc = new \ReflectionFunction($value);
            $X = self::tag_color('false', 'red');
            $source =
                $refFunc->getFileName() === false ? $X : self::tag_color(basename($refFunc->getFileName()), 'Blue', 'strong') .
                '[' .
                ($refFunc->getStartLine() === false ? $X : self::tag_color($refFunc->getStartLine(), 'Blue', 'strong')) .
                '-' .
                ($refFunc->getEndLine() === false ? $X : self::tag_color($refFunc->getEndLine(), 'Blue', 'strong')) .
                ']';
            $ret = self::tag_color("Closure(" . $source . ")", 'RoyalBlue');
        } else {
            switch (gettype($value)) {
                case 'string': {
                        $str = self::tag_color(substr($ret, 1, -1), 'green');
                        $quote = self::tag_color('&#34', '#FF8400');
                        $ret = $quote . $str . $quote;
                    }
                    break;
                case 'integer': {
                        $ret = self::tag_color($ret, '#1299DA');
                    }
                    break;
                case 'array': {
                        //
                        $objects = [];
                        //
                        $arr = [];
                        foreach ($value as $key => $val) {
                            if (is_object($val)) {
                                $classId = bin2hex(random_bytes(16));
                                $objects[$classId] = '<strong title="' . htmlspecialchars(get_class($val)) . '">obj#' . spl_object_id($val) . '</strong>';
                                $arr[$key] = $classId;
                            } else {
                                $arr[$key] = $val;
                            }
                        }
                        //
                        $str = json_encode($arr);
                        //
                        foreach ($objects as $classId => $name) {
                            $str = str_replace('"' . $classId . '"', $name, $str);
                        }
                        //
                        $ret =
                            self::tag_color('&#91;', '#FF8400')
                            .
                            self::tag_color(substr($str, 1, -1), 'gray')
                            .
                            self::tag_color('&#93;', '#FF8400')
                            .
                            self::tag_color('.' . count($value), '#FF8400');
                    }
                    break;
                case 'NULL': {
                        $ret =
                            self::tag_color('null', '#FF8400');
                    }
                    break;
                case 'object': {
                        $ret = s_dump_html($value);
                    }
                    break;
                case 'boolean': {
                        $ret = self::tag_color($ret, $value ? 'DarkGreen' : 'red');
                    }
                    break;
                default: {
                        s_dd($value, gettype($value));
                    }
                    break;
            }
        }
        return $ret;
    }
}
