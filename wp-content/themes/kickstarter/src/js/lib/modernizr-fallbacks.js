/*  ********************************************************
 *   Modernizr fallback global functions
 *  ********************************************************
 */

/* Replace svg with png. Does not reqire jQuery */

if (!Modernizr.svg) {
		var imgs = document.getElementsByTagName('img');
		var endsWithDotSvg = /.*\.svg$/
		var i = 0;
		var l = imgs.length;
		for(; i != l; ++i) {
				if(imgs[i].src.match(endsWithDotSvg)) {
						imgs[i].src = imgs[i].src.slice(0, -3) + 'png';
				}
		}
}

/* Replace svg with png. jQuery version */
// if(!Modernizr.svg) {
//     $('img[src*="svg"]').attr('src', function() {
//         return $(this).attr('src').replace('.svg', '.png');
//     });
// }
