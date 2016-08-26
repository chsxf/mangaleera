function changeZoom(factor)
{
	if (factor <= 0)
		return;
	
	document.zoomFactor = document.zoomFactor * factor;
	
	$$('[rel="zoomable"]').each(function(item) {
			var fontSize = item.getStyle('font-size');
			var re = /^([0-9.]+)px$/;
			var reResult = re.exec(fontSize);
			if (reResult != null)
				item.setStyle('font-size', (reResult[1] * factor) + 'px');
	});
}

window.addEvent('domready', function() {
		document.zoomFactor = 1;
		
		$$('[rel="zoom-increase"]').each(function(item) {
				item.addEvent('click', function(event) {
							new Event(event).stop();
							changeZoom(1.2);
				});
		});
		
		$$('[rel="zoom-reset"]').each(function(item) {
				item.addEvent('click', function(event) {
							new Event(event).stop();
							changeZoom(1 / document.zoomFactor);
				});
		});
		
		$$('[rel="zoom-decrease"]').each(function(item) {
				item.addEvent('click', function(event) {
							new Event(event).stop();
							changeZoom(1 / 1.2);
				});
		});
});