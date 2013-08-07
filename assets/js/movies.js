$(function() {
$('.tip').tooltip();
$('a[rel="external"]').click(function() { window.open($(this).attr('href')); return false; });

$('span.more-content').slideToggle(0); $('div.more-button').show(0); $('span.more-dots').show(0);
$('div.more-button').click(function() {
	$(this).parent().children('p').children('span').children('span.more-content').slideToggle();
	$(this).parent().children('p').children('span').children('span.more-dots').toggle();
	if($(this).text() == 'More')
		$(this).text('Less');
	else $(this).text('More');
});
});