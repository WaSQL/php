/**
 * PostPortal
 * Client-side functionality for API testing
 */

var postportal = {
	/**
	 * Switch between tabs in the request builder
	 */
	switchTab: function(link, tabId) {
		// Hide all tabs
		var tabs = document.querySelectorAll('.postportal-tab-content');
		for(var i = 0; i < tabs.length; i++){
			tabs[i].style.display = 'none';
		}

		// Remove active class from all nav items
		var navItems = link.parentNode.parentNode.querySelectorAll('li');
		for(var i = 0; i < navItems.length; i++){
			navItems[i].classList.remove('active');
		}

		// Show selected tab
		document.getElementById(tabId).style.display = 'block';

		// Set active nav item
		link.parentNode.classList.add('active');

		return false;
	},

	/**
	 * Switch between tabs in the response section
	 */
	switchResponseTab: function(link, tabId) {
		// Hide all response tabs
		var tabs = document.querySelectorAll('.postportal-response-tab');
		for(var i = 0; i < tabs.length; i++){
			tabs[i].style.display = 'none';
		}

		// Remove active class from all nav items
		var navItems = link.parentNode.parentNode.querySelectorAll('li');
		for(var i = 0; i < navItems.length; i++){
			navItems[i].classList.remove('active');
		}

		// Show selected tab
		document.getElementById(tabId).style.display = 'block';

		// Set active nav item
		link.parentNode.classList.add('active');

		return false;
	},

	/**
	 * Toggle authentication fields based on selected auth type
	 */
	toggleAuth: function(authType) {
		document.getElementById('auth_basic').style.display = (authType === 'basic') ? 'block' : 'none';
		document.getElementById('auth_bearer').style.display = (authType === 'bearer') ? 'block' : 'none';
	},

	/**
	 * Add a parameter row
	 */
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

	/**
	 * Remove a parameter row
	 */
	removeParam: function(button) {
		button.parentNode.remove();
	},

	/**
	 * Build URL with query parameters
	 */
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

	/**
	 * Send request via AJAX
	 */
	sendRequest: function(form,div) {
		// Build URL with parameters
		var urlInput = form.querySelector('input[name="url"]');
		var baseUrl = urlInput.value;
		var fullUrl = this.buildUrl(baseUrl);
		urlInput.value = fullUrl;

		// Submit form via AJAX
		return wacss.ajaxPost(form, div);
	},

	/**
	 * Save current request to collection
	 */
	saveCollection: function() {
		var form = document.forms['postportal_form'];
		var name = prompt('Enter a name for this collection:');

		if(name){
			var collectionForm = document.createElement('form');
			collectionForm.method = 'POST';
			collectionForm.action = '/php/admin.php';

			// Add form fields
			var fields = {
				'_menu': 'postportal',
				'func': 'save_collection',
				'name': name,
				'url': form.querySelector('input[name="url"]').value,
				'method': form.querySelector('select[name="method"]').value,
				'headers': form.querySelector('textarea[name="headers"]').value,
				'body': form.querySelector('textarea[name="body"]').value,
				'auth_type': form.querySelector('select[name="auth_type"]').value,
				'auth_username': form.querySelector('input[name="auth_username"]').value,
				'auth_password': form.querySelector('input[name="auth_password"]').value,
				'auth_token': form.querySelector('input[name="auth_token"]').value
			};

			for(var key in fields){
				var input = document.createElement('input');
				input.type = 'hidden';
				input.name = key;
				input.value = fields[key];
				collectionForm.appendChild(input);
			}

			document.body.appendChild(collectionForm);
			wacss.ajaxPost(collectionForm, 'main_content');
			document.body.removeChild(collectionForm);
		}

		return false;
	},

	/**
	 * Delete a collection
	 */
	deleteCollection: function(id) {
		if(confirm('Are you sure you want to delete this collection?')){
			return setActiveNav(null, '/php/admin.php', 'main_content', {
				_menu: 'postportal',
				func: 'delete_collection',
				id: id
			});
		}
		return false;
	},

	/**
	 * Clear request history
	 */
	clearHistory: function() {
		if(confirm('Are you sure you want to clear all history?')){
			return setActiveNav(null, '/php/admin.php', 'main_content', {
				_menu: 'postportal',
				func: 'clear_history'
			});
		}
		return false;
	},

	/**
	 * Delete an environment variable
	 */
	deleteEnvironment: function(id) {
		if(confirm('Are you sure you want to delete this environment variable?')){
			return setActiveNav(null, '/php/admin.php', 'main_content', {
				_menu: 'postportal',
				func: 'delete_environment',
				id: id
			});
		}
		return false;
	}
};
