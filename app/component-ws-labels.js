class WsLabels extends HTMLElement {
	constructor() {
		super();
		this.initialized = false;
	}
	
	connectedCallback() {
		if (this.initialized) {
			return;
		}
		this.initialized = true;
		this.style.display = 'block';
		this.style.margin = '5px 0';
		
		const addedLabels = JSON.parse(this.dataset.addedLabels);
		const userLabels = JSON.parse(this.dataset.userLabels);
		
		const b = document.createElement('b');
		b.textContent = 'Labels: ';
		this.appendChild(b);
		
		addedLabels.map(label => {
			const wrapper = document.createElement('div');
			wrapper.style.margin = '5px 0';
			
			buildHTML(wrapper, [
				['div', {}, [
					['a', {
						href: '?label=' + encodeURIComponent(label.comment),
						target: '_blank',
						style: 'display:block;background:#39f;padding:4px 8px;border-radius:4px;font-weight:bold;text-decoration:none;color:#fff'
					}, label.comment]
				]],
				['div', { style: "font-size:13px;color:#999;margin-top:3px"}, [
					"Added in WS2017 v" + label.version + ' review on ',
					['span', { className: 'comment_date' }, label.date]
				]]
			]);
			
			return wrapper;
		}).forEach(el => this.appendChild(el));
		
		const labelsList = document.createElement('div');
		labelsList.className = 'labels_list';
		userLabels.forEach(label => {
			buildHTML(labelsList, [
				['label', {}, [
					['input', { type: 'radio', name: 'comment', value: label }],
					label
				]]
			]);
		});
		
		buildHTML(this, [
			['details.add_label_details', {}, [
				['summary', {}, 'Add Label'],
				['form.labels_block', { method: 'post' }, [
					labelsList,
					['input', { type: 'hidden', name: 'action', value: 'comment' }],
					['input', { type: 'hidden', name: 'sq_number', value: this.dataset.sqNumber }],
					['input', { type: 'hidden', name: 'user_id', value: this.dataset.userId }],
					['input', { type: 'hidden', name: 'type', value: 'LABEL' }],
					['div', { style: 'margin-top:3px' }, [
						['input.label_block_new', { type: 'button', value: "Create New Label" }],
						' ',
						['input', { type: 'submit', value: "Add Label" }],
						' ',
						['input', { type: 'reset', value: "Reset" }],
					]]
				]]
			]]
		]);
		
		this.querySelector('.label_block_new').addEventListener('click', e => {
			const newLabel = prompt("Enter New Label");
			if (!newLabel) {
				return;
			}

			const labelTag = document.createElement('label');

			const inputTag = document.createElement('input');
			inputTag.type = 'radio';
			inputTag.name = 'comment';
			inputTag.value = newLabel;
			inputTag.textContent = newLabel;
			inputTag.checked = true;
			labelTag.appendChild(inputTag);

			const textNode = document.createTextNode(newLabel);
			labelTag.appendChild(textNode);
			
			this.querySelector('.labels_list').append(labelTag);
		});
	}
}

customElements.define('ws-labels', WsLabels);