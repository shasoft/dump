function TreeLite_tree_toggle(event) {
	//
	function hasClass(elem, className) {
		return new RegExp("(^|\\s)" + className + "(\\s|$)").test(elem.className)
	}
	//
	event = event || window.event
	var clickedElem = event.target || event.srcElement

	if (!hasClass(clickedElem, 'TreeLite_Expand')) {
		return // клик не там
	}

	// Node, на который кликнули
	var node = clickedElem.parentNode
	if (hasClass(node, 'TreeLite_ExpandLeaf')) {
		return // клик на листе
	}

	// определить новый класс для узла
	var newClass = hasClass(node, 'TreeLite_ExpandOpen') ? 'TreeLite_ExpandClosed' : 'TreeLite_ExpandOpen'
	// заменить текущий класс на newClass
	// регексп находит отдельно стоящий open|close и меняет на newClass
	var re = /(^|\s)(TreeLite_ExpandOpen|TreeLite_ExpandClosed)(\s|$)/
	node.className = node.className.replace(re, '$1' + newClass + '$3')
}