function facebookInit(){
	var e = document.createElement('script');
    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
    e.async = true;
    document.getElementById('fb-root').appendChild(e);
}

function fb_login(){
	FB.init({
        appId   : '484727438377642',
        oauth   : true,
        status  : true, // check login status
        cookie  : true, // enable cookies to allow the server to access the session
        xfbml   : true // parse XFBML
    });

	FB.login(function(response) {
    	if (response.authResponse) {
            access_token = response.authResponse.accessToken; //get access token
            user_id = response.authResponse.userID; //get FB UID
            FB.api('/me', function(response) {
				var pre=loginPre();
				var redir='https://'+pre+'.skillsai.com/login/facebook?access_token='+encodeURI(access_token);
				redir +='&user_id='+encodeURI(user_id);
				redir +='&name='+encodeURI(name);
				if(undefined != response.email){
					redir +='&email='+encodeURI(response.email);
				}
                window.location=redir;
            });
        } else {
            //user hit cancel button
            //console.log('User cancelled login or did not fully authorize.');
        }
    },{
        scope: 'email,public_profile'
    }
	);
}
function amazonInit(){
	window.onAmazonLoginReady = function() {
		amazon.Login.setClientId('amzn1.application-oa2-client.e1bfeb1ac8f0451ab694e273c8529c95');
	};
	(function(d) {
		var a = d.createElement('script'); a.type = 'text/javascript';
		a.async = true; a.id = 'amazon-login-sdk';
		a.src = 'https://api-cdn.amazon.com/sdk/login1.js';
		d.getElementById('amazon-root').appendChild(a);
	})(document);

	document.getElementById('amazon-login').onclick = function() {
		options = { scope : 'profile postal_code' };
		var pre=loginPre();
		amazon.Login.authorize(options, 'https://'+pre+'.skillsai.com/login/amazon');
		return false;
	};
	document.getElementById('amazon-register').onclick = function() {
		options = { scope : 'profile postal_code' };
		var pre=loginPre();
		amazon.Login.authorize(options, 'https://'+pre+'.skillsai.com/login/amazon');
		return false;
	};
	//log them out of amazon if they are on this page
	try{
		amazon.Login.logout();
	}
	catch(err) {}
}
function loginPre(){
	if(undefined == document.querySelector('div.default')){
    	return 'www';
	}
	return document.querySelector('div.default').getAttribute('data-pre');
}
function loginShowRegistration(ck){
	if(ck){
		document.getElementById('register').style.display='block';
		setText('formtitle','Register');
		document.loginform.username.focus();
	}
	else{
		document.getElementById('register').style.display='none';
		setText('formtitle','Login');
		document.loginform.username.focus();

	}
}
