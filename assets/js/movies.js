$(function() {
$('.tip').tooltip({'placement': 'bottom'});
$('a[rel="external"]').click(function() { window.open($(this).attr('href')); return false; });
$('#go_home_you_are_drunk').click(function() { $('html,body').animate({scrollTop: 0}, 'slow'); return false; });
});