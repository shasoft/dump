<?php
// https://javascript.ru/ui/tree
namespace Shasoft\Dump\Tree\HtmlRender;

use Shasoft\Dump\Tree\Node;

class Tree7Steps extends Html
{
    protected function html(Node $node): string
    {
        return $this->_html(null, [$node], 0);
    }

    private function _html(?Node $parent, array $children, int $level): string
    {
        $html = '';
        $assetPath = __DIR__ . '/../../../assets/TreeLite/';
        if (is_null($parent)) {
            $filepathCss =  $assetPath . "grid.css";
            $cssContent = preg_replace_callback('/(background-image.?:.?url)\((.*)\)/', function ($matches) use ($filepathCss) {
                //dbg_dump($matches);
                $filepathImage = dirname($filepathCss) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $matches[2]);
                //$matches[1] = "background-image: url";
                return $matches[1] . '("data:image/' . ((strtolower(substr($filepathImage, -4)) == ".png") ? "png" : "gif") . ";base64," . base64_encode(file_get_contents($filepathImage)) . '")';
            }, file_get_contents($filepathCss));
            $html .= '<script type="text/javascript">' . file_get_contents($assetPath . 'tree_toggle.js') . PHP_EOL . '</script>' . PHP_EOL;
            $html .= '<style type="text/css" media="all">' . $cssContent . '</style>' . PHP_EOL;
        }
        $onclick = (is_null($parent) ?  ' onclick="TreeLite_tree_toggle(arguments[0])"' : '');
        $classRoot = is_null($parent) ? 'TreeLite_IsRoot ' : '';
        $html .= $this->prefix($level) . '<ul class="TreeLite_Container"' . $onclick . '>' . PHP_EOL;
        $cnt = count($children);
        /** @var Node $node */
        foreach ($children as $node) {
            $cnt--;
            $classIsLast = ($cnt == 0 ? 'TreeLite_IsLast ' : '');
            $classExpand = ($node->expand() ? 'TreeLite_ExpandOpen' : 'TreeLite_ExpandClosed');
            $classLeaf = empty($node->children()) ? 'TreeLite_ExpandLeaf ' : '';
            $li = $this->prefix($level + 1) . '<li class="' . $classRoot . 'TreeLite_Node ' . $classIsLast . $classLeaf . $classExpand . '">' . PHP_EOL;
            $li .= $this->prefix($level + 2) . '<div class="TreeLite_Expand"></div>' . PHP_EOL;
            $li .= $this->prefix($level + 2) . '<div class="TreeLite_Content">' . $node->content() . '</div>' . PHP_EOL;
            if (!empty($node->children())) {
                $li .= $this->_html($node, $node->children(), $level + 2);
            }
            $li .= $this->prefix($level + 1) . '</li>' . PHP_EOL;
            $html .= $li;
        }
        $html .= $this->prefix($level) . '</ul>' . PHP_EOL;
        return $html;
    }
}
