$(function() {
$('.tip').tooltip();
$('a[rel="external"]').click(function() { window.open($(this).attr('href')); return false; });
$('#search').focus(function(){ $(this).animate({width: '+=100'}); });
$('#search').blur(function(){ $(this).animate({width: '-=100'}); });
$('#go_home_you_are_drunk').click(function() { $('html,body').animate({scrollTop: 0}, 'slow'); return false; });
});