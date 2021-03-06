<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<link href="//fonts.googleapis.com/css?family=Raleway:600,600italic,800,800italic|Roboto:300,300italic,700,700italic" rel="stylesheet"
	 type="text/css">
	<link href="https://ldjam.com/-/all.min.css?v=4033-24c3fc0" rel="stylesheet" type="text/css">
	<script href="https://cdn.jsdelivr.net/npm/promise-polyfill@6/dist/promise.min.js"></script>
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<title>ldjam.com</title>
	<style>
		html,
		body {
			height: 100%;
		}

		body {
			background: transparent;
			top: 0;
		}

		.container {
			max-height: 720px;
		}

		iframe.itch {
			max-width: 552px;
			width: 100%;
		}
	</style>

	<script type="text/javascript">
		/* Taken from https://gist.github.com/hagenburger/500716 */
		var JavaScript = {
			load: function (src, callback) {
				var script = document.createElement('script'),
					loaded;
				script.setAttribute('src', src);
				if (callback) {
					script.onreadystatechange = script.onload = function () {
						if (!loaded) {
							callback();
						}
						loaded = true;
					};
				}
				document.getElementsByTagName('head')[0].appendChild(script);
			}
		};

		/* Taken from https://developer.mozilla.org/en-US/docs/Web/Events/resize */
		var optimizedResize = (function () {

			var callbacks = [],
				running = false;

			// fired on resize event
			function resize() {

				if (!running) {
					running = true;

					if (window.requestAnimationFrame) {
						window.requestAnimationFrame(runCallbacks);
					} else {
						setTimeout(runCallbacks, 66);
					}
				}

			}

			// run the actual callbacks
			function runCallbacks() {

				callbacks.forEach(function (callback) {
					callback();
				});

				running = false;
			}

			// adds callback to loop
			function addCallback(callback) {

				if (callback) {
					callbacks.push(callback);
				}

			}

			return {
				// public method to add additional callback
				add: function (callback) {
					if (!callbacks.length) {
						window.addEventListener('resize', resize);
					}
					addCallback(callback);
				}
			}
		}());

		var params = parseQueryString();

		var EMBED_TYPE = "none"
		var REGEX_JSON = {}
		var URL = params["url"]
		var AUTOPLAY = params["autoplay"] ? params["autoplay"] : 0;

		if (URL) {
			EMBED_TYPE = getEmbedType(URL);
		}

		function parseQueryString() {
			var str = window.location.search;
			var objURL = {};

			str.replace(
				new RegExp("([^?=&]+)(=([^&]*))?", "g"),
				function ($0, $1, $2, $3) {
					objURL[$1] = $3;
				}
			);
			return objURL;
		};

		function getEmbedType(url_to_parse) {
			const protocol = "(http(?:s?):\\/\\/)?";
			const safechar = `[^&\\/"']`;
			const twitter_regex = new RegExp(`^${protocol}twitter\\.com\\/(${safechar}+)\\/status(?:es)*\\/(\\d+)$`);
			const itch_regex = new RegExp(`^${protocol}(${safechar}+)\\.itch\\.io\\/(${safechar}+)$`);
			const gfycat_regex = new RegExp(`^${protocol}gfycat\\.com\\/(${safechar}+)$`);
			const youtube_regex = new RegExp(`^${protocol}(?:www\\.)?youtu(?:be\\.com\\/watch\\?v=|\\.be\\/)([\\w\\-\\_]*)(&(amp;)?‌​[\\w\\?‌​=]*)?$`);
			const sketchfab_regex = new RegExp(`^${protocol}sketchfab\\.com\\/models\\/(\\w+)$`);
			const soundcloud_regex = new RegExp(`^${protocol}soundcloud\\.com\\/${safechar}+(/${safechar}+$)?`);

			if (twitter_regex.test(url_to_parse)) {
				REGEX_JSON = twitter_regex.exec(url_to_parse);
				return "twitter";
			} else if (itch_regex.test(url_to_parse)) {
				REGEX_JSON = itch_regex.exec(url_to_parse);
				return "itch";
			} else if (gfycat_regex.test(url_to_parse)) {
				REGEX_JSON = gfycat_regex.exec(url_to_parse);
				return "gfycat";
			} else if (youtube_regex.test(url_to_parse)) {
				REGEX_JSON = youtube_regex.exec(url_to_parse);
				return "youtube";
			} else if (sketchfab_regex.test(url_to_parse)) {
				REGEX_JSON = sketchfab_regex.exec(url_to_parse);
				return "sketchfab";
			} else if (soundcloud_regex.test(url_to_parse)) {
				REGEX_JSON = soundcloud_regex.exec(url_to_parse);
				return "soundcloud";
			}
		}

		var client;

		function getHeight() {
			var target = document.getElementById('container');

			var height = Math.max(
				target.scrollHeight,
				target.offsetHeight,
				target.clientHeight
			);

			return height;
		}

		function messageClient() {
			if (client) {
				client.postMessage({ "request_height": getHeight(), "url": URL }, "*");
			}
		}

		window.addEventListener('message', function (event) {
			if (event.data == "request_height") {
				client = event.source;

				messageClient();
			}
		});

		optimizedResize.add(messageClient);

		function handleEmbed(embedType, regexJson) {
			var target = document.getElementById('container');

			switch (embedType) {
				case "twitter":
					JavaScript.load("https://platform.twitter.com/widgets.js", function () {
						twttr.events.bind('rendered', messageClient);

						twttr.widgets.createTweet(regexJson[2], target);
					});
					break;

				case "itch":
					JavaScript.load("https://static.itch.io/api.js", function () {
						Itch.getGameData({
							user: regexJson[2],
							game: regexJson[3],
							onComplete: function (data) {
								target.innerHTML = '<iframe class="itch" src="https://itch.io/embed/' + data.id + '" width="552" frameborder="0" height="167" />';
								messageClient();
							}
						});
					});
					break;
				case "soundcloud":
					JavaScript.load("https://connect.soundcloud.com/sdk/sdk-3.3.0.js", function () {
						SC.oEmbed(URL, {
							element: target,
							maxwidth: 552,
						}).then(messageClient);
					});
					break;
				case "gfycat":
					target.innerHTML = "<div style='position:relative;padding-bottom:51%'><iframe src='https://gfycat.com/ifr/" + regexJson[2] + "' frameborder='0' scrolling='no' width='100%' height='100%' style='position:absolute;top:0;left:0;' allowfullscreen></iframe></div>";
					break;
				case "youtube":
					target.innerHTML = "<div style='position:relative;padding-bottom:56.25%'><iframe class='youtube' src='https://www.youtube.com/embed/" + regexJson[2] + "?autoplay=" + AUTOPLAY + "' frameborder='0' scrolling='no' width='100%' height='100%' style='position:absolute;top:0;left:0;' allow='autoplay; encrypted-media' allowfullscreen></iframe></div>";
					break;
				case "sketchfab":
					target.innerHTML = "<div style='position:relative;padding-bottom:56.25%'><iframe class='sketchfab' src='https://sketchfab.com/models/" + regexJson[2] + "/embed?autostart=" + AUTOPLAY + "' frameborder='0' scrolling='no' width='100%' height='100%' style='position:absolute;top:0;left:0;' allowfullscreen></iframe></div>";
					break;
			}
		}
	</script>
</head>

<body>
	<div id="main">
		<div id="container">
		</div>
	</div>
	<script type="text/javascript">
		handleEmbed(EMBED_TYPE, REGEX_JSON);
	</script>
</body>

</html>
