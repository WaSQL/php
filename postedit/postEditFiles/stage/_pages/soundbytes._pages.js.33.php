/* post functions */
function postExpand(id){
	document.getElementById('postmore'+id).style.display='none';
	document.getElementById('postmore_body'+id).style.display='inline';
	return false;
}
function postCollapse(id){
	document.getElementById('postmore'+id).style.display='block';
	document.getElementById('postmore_body'+id).style.display='none';
	return false;
}
/* comment functions */
function pageComments(id){
	postExpand(id);
	window.location.href = "#comments"+id;
	return ajaxGet('/t/1/soundbytes/comments/'+id,'comments'+id);
}
function commentExpand(id){
	document.getElementById('commentmore'+id).style.display='none';
	document.getElementById('commentmore_body'+id).style.display='inline';
	return false;
}
function commentCollapse(id){
	document.getElementById('commentmore'+id).style.display='block';
	document.getElementById('commentmore_body'+id).style.display='none';
	return false;
}
