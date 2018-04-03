function setupFillMore(d) {
	setupEmptyAll();
	setText(d + '_more', getText(d + '_more_content'));
}

function setupEmptyAll() {
	setText('blank_more', '');
	setText('sample_more', '');
	setText('clone_more', '');
}

function setupSubmitForm(frm) {
	if (!submitForm(frm)) {
		return false;
	}
	setProcessing('begin_button');
	return true;
}
