<?php

namespace Shasoft\Dump\Tree\HtmlRender;

use Shasoft\Dump\Tree\Node;

abstract class Html
{
    abstract protected function html(Node $node): string;

    static public function render(Node $node, int|false $skipTraces = false): static
    {
        $render = new static;
        $html = $render->html($node, 0);
        //s_dd($html);
        if ($skipTraces !== false) {
            s_call_fn(function (string $html) {
                echo $html;
            }, [$html], $skipTraces);
        } else {
            echo $html;
        }
        return $render;
    }

    protected function prefix(int $level): string
    {
        return str_repeat(' ', $level * 4);
    }
}
