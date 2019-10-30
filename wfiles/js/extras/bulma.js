// The following code is based off a toggle menu by @Bradcomp
// source: https://gist.github.com/Bradcomp/a9ef2ef322a8e8017443b626208999c1
(function() {
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
})();