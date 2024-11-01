<?php
namespace Shasoft\Dbg;
// По мотивам http://javascript.ru/ui/tree
// Вывод дерева
class Tree {
	// Вывод данных
	protected static function _html($itemParent,$cbGetItemInto,$isLast) {
		$itemInfo = $cbGetItemInto($itemParent);
		$html = "";
		// Класс узла
		$classNode = "TreeLite_ExpandLeaf";
		$isGroup = (isset($itemInfo['items']) && count($itemInfo['items'])>0);
		if( $isGroup ) {
			$classNode = isset($itemInfo['open']) ? "TreeLite_ExpandOpen" : "TreeLite_ExpandClosed";
		} 
		if($isLast) {
			$classNode .= " TreeLite_IsLast";
		}
		$html .= '<li class="TreeLite_Node '.$classNode.'"><div class="TreeLite_Expand"></div><div class="TreeLite_Content">'.$itemInfo['content'].'</div>';
		if( $isGroup ) {
			$html .= '<ul class="TreeLite_Container">';
			$cntNodes = count($itemInfo['items']);
			$index = 0;
			foreach($itemInfo['items'] as $item) {
				// Обработать дочерний список
				$html .= self::_html($item,$cbGetItemInto, $index==$cntNodes-1 ? true : false);
				// Увеличить индекс
				$index++;
			}
			$html .= '</ul>';
		}
		$html .= '</li>';
		return $html;
	}
	static protected $is_style_script = false;
	public static function tree($obj,$cbGetItemInto,$rootText="Root") {
		if(self::$is_style_script==false) {
			// 
			$treePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "TreeLite" . DIRECTORY_SEPARATOR;
			// Прочитать CSS файл заменив в нем все картинки на base64 и поместив их в файл стилей
			$filepathCss =  $treePath."grid.css";
			$cssContent = preg_replace_callback('/(background-image.?:.?url)\((.*)\)/', function ($matches) use ($filepathCss) {
				//dbg_dump($matches);
				$filepathImage = dirname( $filepathCss ). DIRECTORY_SEPARATOR .str_replace('/',DIRECTORY_SEPARATOR,$matches[2]);
				//$matches[1] = "background-image: url";
				return $matches[1].'("data:image/'.((strtolower(substr($filepathImage,-4))==".png")?"png":"gif").";base64,".base64_encode(file_get_contents($filepathImage)).'")';
			},file_get_contents($filepathCss));
			echo "<style type='text/css' media='all'>".$cssContent."</style>";			
			// Файл скрипта
			echo "<script type='text/javascript'>".file_get_contents($treePath."tree_toggle.js")."</script>";
			//
			self::$is_style_script=true;
		}
		//
		$html = '<ul class="TreeLite_Container" onclick="TreeLite_tree_toggle(arguments[0])">';
		$html .= self::_html($obj,$cbGetItemInto,true);
		$html .= '</ul>';
		echo $html;
	}
}
