import {h, Component}		from 'preact/preact';

export default class UIEmbedFrame extends Component {
	constructor(props) {
		super(props);

		this.state = {
			"height": window.innerHeight + "px",
			"width": window.innerWidth + "px",
			"visible": false
		};

		this.onMessage = this.onMessage.bind(this);
	}

	componentDidMount() {
		this.iframe.onload = () => {
			this.iframe.contentWindow.postMessage('request_height', "*");
		};

		window.addEventListener('message', this.onMessage);
	}

	componentWillUnmount() {
		window.removeEventListener('message', this.onMessage);
	}

	componentWillUpdate(nextProps, nextState) {
		if(this.props.onLoad && nextState.visible && !this.state.visible) {
			this.props.onLoad();
		}
	}

	onMessage(event) {
		if (event.data.hasOwnProperty("request_height") && event.data.hasOwnProperty("url")) {
			if (event.data.request_height > 0 && event.data.url == this.props.link.url) {
				this.setState({"height": event.data.request_height, "visible": true});
				if(this.props.onLoad) {
					this.props.onLoad();
				}
			}
		}
	}

	render( props, state ) {
		let {url, info} = this.props.link;
		let {height, width, visible} = this.state;

		const sandboxProperties = [
			"allow-presentation",
			"allow-same-origin",
			"allow-popups",
			"allow-forms",
			"allow-scripts",
			"allow-top-navigation"
		];

		let src = API_ENDPOINT + "/vx/embed?";

		if (info.autoplay) {
			src += "autoplay=1&";
		}

		src += "url=" + url;

		let style;
		if (!visible) {
			style = "visibility: hidden; position: absolute;";
		}

		return (
			<iframe style={style} width="100%" height={height} allow="autoplay" sandbox={sandboxProperties.join(" ")} ref={(f) => this.iframe = f } frameborder="none" scrolling="no" src={src} />
		);
	}
}
