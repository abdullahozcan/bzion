$(document).ready(function() {
    if (!$.fn.select2) {
        return;
    }

    $(".js-select__permission").select2();
});
