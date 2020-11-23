let buildHTML = (parent, children) => {
	if (typeof children === 'string') {
		parent.appendChild(document.createTextNode(children));
		return;
	}
	if (children instanceof Node) {
		parent.appendChild(children);
		return;
	}
	children.forEach((child) => {
		if (Array.isArray(child)) {
			let [tagname, attributes, children] = child;
			let child2;

			if (tagname.indexOf('.') !== -1) {
				const [tag, ...classnames] = tagname.split('.');
				child2 = document.createElement(tag);
				child2.className = classnames.join(' ');
			} else {
				child2 = document.createElement(tagname);
			}

			if (typeof attributes === 'object') {
				Object.keys(attributes).forEach((key) => {
					child2[key] = attributes[key];
				});
			}
			if (typeof attributes === 'function') {
				attributes(child2);
			}

			if (children !== null && children !== undefined) {
				buildHTML(child2, children);
			}
			parent.appendChild(child2);
			return;
		}
		if (typeof child === 'string') {
			parent.appendChild(document.createTextNode(child));
			return;
		}
		if (child instanceof Node) {
			parent.appendChild(child);
			return;
		}
	});
};