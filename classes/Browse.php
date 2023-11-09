<?php

namespace Shasoft\Dump;

class Browse
{
    // Нумератор
    static int $num = 0;
    // Получить HTML для копирования строки в буфер обмена
    public static function copyToClipboard(string $textToClipboard, ?string $title = null): string
    {
        $ret = '';
        // то включить его
        $ret .= "<script>";
        $ret .=  file_get_contents(__DIR__ . '/../assets/copyToClipboard.js');
        $ret .= "</script>";
        // Имя функции в JS коде
        $fn_ = 'copyToClipboard';
        // Имя функции в текущем коде
        self::$num++;
        $fn = $fn_ . self::$num;
        // Выполнить замену
        $ret = str_replace($fn_, $fn, $ret);
        //
        if (is_null($title)) $title = $textToClipboard;
        $ret .= '<span title="Нажмите чтобы скопировать в буфер обмена" style="background-color:PaleTurquoise;padding:2px;color:black;cursor:pointer" onclick="' . $fn . '(\'' . addslashes($textToClipboard) . '\')">' . $title . '</span>';
        return $ret;
    }
}
