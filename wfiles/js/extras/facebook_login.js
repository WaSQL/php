/* Facebook login API  to add login with facebook to WaSQL */
// Load the SDK asynchronously
(function(d, s, id) {
	var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));

function facebookStatusChangeCallback(response,login) {
	//console.log('facebookStatusChangeCallback');
	var facebook_icon='<img src="/wfiles/iconsets/16/facebook.png" border="0" />';
	if (response.status === 'connected') {
    	// Logged into your app and Facebook.
      	//Add facebook info to login and Submit the login form
      	FB.api('/me', function(response) {
			if(undefined != login){
				if(undefined != document.loginform){
					document.loginform.username.value=response.email;
					document.loginform.username.name='facebook_email';
					document.loginform.password.value=response.id;
					document.loginform.submit();
					return;
				}
				else if(undefined != document.registerform){
					if(undefined != document.registerform.username){
						document.registerform.username.value=response.email;
					}
					if(undefined != document.registerform.email){
						document.registerform.email.value=response.email;
					}
				}
				//also pass in facebook_email
				var i=document.createElement('input');
				i.nme='facebook_email';
				i.value=response.email;
				document.registerform.appendChild(i);
				//pass in facebook_id
				i=document.createElement('input');
				i.name='facebook_id';
				i.value=response.id;
				document.registerform.appendChild(i);
    			document.registerform.submit();
			}
			/* response object now: first_name,last_name,gender,email,locale,name,id,timezone*/
			var facebook_status='';
			/*if the user is logged in, but his facebook email and id are blank then show link to connect */
      		if(undefined != facebook_email && undefined != facebook_id){
				var base64string=window.btoa(response.id+':'+response.email);
				if(facebook_email.length==0 && facebook_id.length==0){
					facebook_status='<a href="#" title="Link this account to the '+response.email+' facebook account" onclick="return facebookLink(\''+base64string+'\');" class="w_dblue w_link">'+facebook_icon+' Link</a>';
				}
				else if(facebook_email != response.email || facebook_id != response.id){
					facebook_status='<a href="#" title="Update this account to the '+response.email+' facebook account" onclick="return facebookUpdateLink(\''+base64string+'\');" class="w_dblue w_link">'+facebook_icon+' Update Link</a>';
				}
				else{
					facebook_status='<span class="w_dblue w_link" title="Linked to the '+response.email+' facebook account.">'+facebook_icon+' Linked</span>';
				}
			}

			  setText('facebook_status',facebook_status);
      		//console.log(response);
		});
	}
	else if (response.status === 'not_authorized') {
    	// The person is logged into Facebook, but not your app.
    	var facebook_status='<fb:login-button size="small" scope="public_profile,email" onlogin="facebookCheckLoginState();">Link Accounts</fb:login-button>';
		setText('facebook_status',facebook_status);
		facebookInit();
	} 
	else {
      // The person is not logged into Facebook, so we're not sure if they are logged into this app or not.
		if(!facebook_email.length || !facebook_id.length){
			var facebook_status='<fb:login-button size="small" scope="public_profile,email" onlogin="facebookCheckLoginState();">Link Accounts</fb:login-button>';
			setText('facebook_status',facebook_status);
			facebookInit();
		}
		else{
        	facebook_status='<span class="w_dblue w_link" title="Linked to the '+facebook_email+' facebook account.">'+facebook_icon+' Linked</span>';
			setText('facebook_status',facebook_status);
		}
    }
}
function facebookLinked(){
	var facebook_icon='<img src="/wfiles/iconsets/16/facebook.png" border="0" />';
	facebook_status='<span class="w_dblue w_link">'+facebook_icon+' Linked</span>';
	setText('facebook_status',facebook_status);
}
function facebookCheckLoginState(login){
	FB.getLoginStatus(function(response){
    	facebookStatusChangeCallback(response,login);
    });
}
function facebookLink(base64string){
	return ajaxGet(window.location.pathname,'facebook_status',{_template:1,_fblink:base64string});
}
function facebookUpdateLink(base64string){
	return ajaxGet(window.location.pathname,'facebook_status',{_template:1,_fbupdate:base64string});
}
function facebookInit(){
	FB.init({
	    appId      : facebook_appid ,
	    cookie     : true,  // enable cookies to allow the server to access the session
	    xfbml      : true,  // parse social plugins on this page
	    version    : 'v2.0' // use version 2.0
	});
}
//function facebookInitSDK(appid){
	window.fbAsyncInit = function() {
		facebookInit();
  		FB.getLoginStatus(function(response) {
    		facebookStatusChangeCallback(response);
  		});
	};
//}