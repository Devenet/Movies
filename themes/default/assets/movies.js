for (var a = document.getElementsByTagName('a'), i = 0, l = a.length; i < a.length; i++) { 'external' == a[i].getAttribute('rel') && (a[i].setAttribute('target', '_blank'), a[i].setAttribute('rel', 'noopener')); }

var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
for (var i = 0, l = tooltips.length; i < l; i++) { new bootstrap.Tooltip(tooltips[i]); }