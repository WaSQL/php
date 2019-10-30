document.addEventListener('DOMContentLoaded', function() {
	/* Javascript to toggle the burger menu on or off */
    let burger = document.querySelector('.burger');
    if(undefined != burger){
	    burger.addEventListener('click', function() {
	        this.classList.toggle('is-active');
	        let menu=document.querySelector('#'+this.getAttribute('data-target'));
	        if(undefined != menu){
	        	menu.classList.toggle('is-active');
	        }
	    });
	}
}, false);