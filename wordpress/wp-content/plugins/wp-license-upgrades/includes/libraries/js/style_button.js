function getcheckboxes() {
	var node_list = document.getElementsByTagName('input');
	var checkboxes = [];
	for (var i = 0; i < node_list.length; i++) {
		var node = node_list[i];
		if (node.getAttribute('type') == 'checkbox') {
			checkboxes.push(node);
		}
	}
	return checkboxes;
}
function toggle(source) {
	checkboxes = getcheckboxes();
	for(var i=0, n=checkboxes.length;i<n;i++) {
		checkboxes[i].checked = source.checked;
	}
}