$(document).ready(function(){

    $(".badge_details_link").click(function(){
        var id = $(this).closest("li").attr("badge_id");
        $('#badge_id').val(id);
        f_act('/seo/show_badge_form/');
        $("#f").submit();
        return false;
    });

});