$(function() {
$('.tip').tooltip();
$('a[rel="external"]').click(function() { window.open($(this).attr('href')); return false; });
});