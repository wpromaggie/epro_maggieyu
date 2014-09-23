/**
 * Javascript used by the workboard module
 * @author Matthew A Monahan
 */

jQuery(document).ready(function() {

	//Make the tasks expand when you click on them
	$("div[class=body]").hide();
	$("div[class=header]").click(function() {
		$(this).next().slideToggle();
	});

});

