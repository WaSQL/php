var postportal = {
	switchTab: function(link, tabId) {
		var tabs = document.querySelectorAll('.postportal-tab-content');
		for(var i = 0; i < tabs.length; i++){
			tabs[i].style.display = 'none';
		}
		var navItems = link.parentNode.parentNode.querySelectorAll('li');
		for(var i = 0; i < navItems.length; i++){
			navItems[i].classList.remove('active');
		}
		document.getElementById(tabId).style.display = 'block';
		link.parentNode.classList.add('active');
		return false;
	},

	switchResponseTab: function(link, tabId) {
		var tabs = document.querySelectorAll('.postportal-response-tab');
		for(var i = 0; i < tabs.length; i++){
			tabs[i].style.display = 'none';
		}
		var navItems = link.parentNode.parentNode.querySelectorAll('li');
		for(var i = 0; i < navItems.length; i++){
			navItems[i].classList.remove('active');
		}
		document.getElementById(tabId).style.display = 'block';
		link.parentNode.classList.add('active');
		return false;
	},

	addParam: function() {
		var container = document.getElementById('params_container');
		var row = document.createElement('div');
		row.className = 'param-row';
		row.style.cssText = 'display:flex;gap:10px;margin-bottom:5px;';
		row.innerHTML = '<input type="text" class="wacss_input" placeholder="Key" style="flex:1;">' +
						'<input type="text" class="wacss_input" placeholder="Value" style="flex:1;">' +
						'<button type="button" class="wacss_button is-small" onclick="postportal.removeParam(this);">Remove</button>';
		container.appendChild(row);
	},

	removeParam: function(button) {
		button.parentNode.remove();
	},

	buildUrl: function(baseUrl) {
		var params = [];
		var urlObj = new URL(baseUrl, window.location.origin);
		var rows = document.querySelectorAll('#params_container .param-row');
		for(var i = 0; i < rows.length; i++){
			var inputs = rows[i].querySelectorAll('input');
			var key = inputs[0].value.trim();
			var value = inputs[1].value.trim();
			if(key && value && !urlObj.searchParams.has(key)){
				params.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
			}
		}
		if(params.length > 0){
			var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
			return baseUrl + separator + params.join('&');
		}
		return baseUrl;
	},

	sendRequest: function(form, div) {
		var urlInput = form.querySelector('input[name="url"]');
		urlInput.value = this.buildUrl(urlInput.value);
		return wacss.ajaxPost(form, div);
	}
};
