function appendNotification(el) {
    jQuery("#ohsnap").detach().appendTo(el);
}
function removeNotification(el) {
    jQuery(el+" #ohsnap").detach().empty().appendTo('body');
}
