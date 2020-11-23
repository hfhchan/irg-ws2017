class WsCommentEditor extends HTMLElement {
	constructor() {
		super();
		this.initialized = false;
	}
	
	connectedCallback() {
		if (this.initialized) {
			return;
		}

		this.action = this.dataset.action;

		this.sn = this.dataset.sn;

		this.initialized = true;

		this.innerHTML = `
	<form method=post class=comment_block>
		<div style="font-size:16px">Submitting as: <span class=user_name></span></div>
		<div><select name=type class=comment_type></select></div>
		<div>
			<div style="font-size:12px"><small>Note 1: For submitting UNIFICATION comment, the code chart screenshot of any CJK Unified Ideographs (character, codepoint, or combination in format <span style="color:#ccc">一 (U+4E00)</span> or <span style="color:#ccc">U+4E00 一</span>) will be automatically added. It is not necessary to attach any screenshot.</small></div>
			<div style="font-size:12px"><small>Note 2: For submitting UNIFICATION comment, please also separate explanation with a new line (if any).</small></div>
			<div style="font-size:12px"><small>Note 3: To attach photo, copy the photo from Microsoft Word or Microsoft Paint, and paste directly into the text box.  Please paste text and images separately.  The system supports pasting only one (1) image each time.</small></div>
			<div style="font-size:12px"><small>Note 4: To include screenshot of any CJK Unified Ideograph, specify in format <span style="color:#ccc">{{U+4E00}}</span></small></div>
			<textarea name=comment class=comment_content data-sq-number="${this.sn}"></textarea>
		</div>
		<div class=comment_suggest></div>
		<div class=comment_suggest_radical></div>
		${
			this.action === 'add_comment' ?
				`<div><input type=submit value="Add Comment" class=comment_submit></div>` :
				`<div>
					<input type=submit value="Edit Comment" class=comment_submit>
					<input type=button value="Cancel" class=comment_cancel>
				</div>`
		}
		`;
		
		this.querySelector('span.user_name').textContent = this.dataset.userName;
		
		const types = this.querySelector('select[name="type"]');
		const commentTypes = JSON.parse(this.dataset.commentTypes);
		Object.keys(commentTypes).forEach(key => {
			const optGroup = document.createElement('optgroup');
			optGroup.label = key;
			commentTypes[key].forEach(type => {
				const option = document.createElement('option');
				option.textContent = type;
				option.value = type;
				optGroup.appendChild(option);
			})
			types.appendChild(optGroup);
		});
		
		const suggest = this.querySelector('.comment_suggest');
		const suggestedComments = JSON.parse(this.dataset.suggestedComments);
		suggestedComments.forEach(key => {
			const span = document.createElement('span');
			span.tabIndex = '0';
			span.textContent = key;
			suggest.appendChild(span);
		});
		
		const radicals = this.querySelector('.comment_suggest_radical');
		const suggestedRadicals = JSON.parse(this.dataset.radicals);
		suggestedRadicals.forEach(key => {
			const div = document.createElement('div');
			div.textContent = key;
			radicals.appendChild(div);
		});
		
		const parent = $(this);

		parent.find('.comment_type').on('change', this.toggleCommentLabels.bind(this));
		this.toggleCommentLabels();

		parent.find('.comment_suggest span').on('click', function() {
			parent.find('.comment_content').val($(this).text());
		}).on('keydown', function(e) {
			if (e.originalEvent.code === 'Enter' || e.originalEvent.code === 'Space') {
				e.preventDefault();
				parent.find('.comment_content').val($(this).text()).focus();
			}
		})
		parent.find('.comment_suggest_radical div').on('click', function() {
			const newText = parent.find('.comment_content').val() + "\nChange Radical to " + $(this).text();
			parent.find('.comment_content').val(newText.trim());
		}).on('keydown', function(e) {
			if (e.originalEvent.code === 'Enter' || e.originalEvent.code === 'Space') {
				e.preventDefault();
				parent.find('.comment_content').val($(this).text()).focus();
			}
		});
		parent.on('submit', (e) => {
			if (parent.find('.comment_content').val() === '') {
				e.preventDefault();
				alert('Please fill in comment.')
			}

			e.preventDefault();
			this.submit();
		});
	}
	
	async submit() {
		const formData = new FormData(this.querySelector('form'));
		const data = new URLSearchParams();

		data.append('type', formData.get('type'));
		data.append('comment', formData.get('comment'));

		data.append('user_id', this.dataset.userId);

		if (this.action === 'add_comment') {
			data.append('action', 'add_comment');
			data.append('sq_number', this.dataset.sn);
		} else {
			data.append('action', 'edit_comment');
			data.append('comment_id', this.dataset.commentId);
		}

		this.querySelector('.comment_submit').disabled = true;
		try {
			const res = await fetch('api-internal.php', {
				method: 'post',
				credentials: 'same-origin',
				body: data,
			});
			const result = await res.json();
			if (result.success) {
				alert('Comment Saved');
				window.location.reload();
			} else {
				alert('Comment could not be saved: ' + result.message);
				this.querySelector('.comment_submit').disabled = false;
			}
		} catch (e) {
			alert('Comment could not be saved: ' + e.message);
			this.querySelector('.comment_submit').disabled = false;
		}
	}
	
	toggleCommentLabels() {
		const e = this.querySelector('.comment_type');
		const val = e.options[e.selectedIndex].value;
		this.querySelector('.comment_suggest_radical').style.display = val === 'ATTRIBUTES_RADICAL' ? '' : 'none';
		this.querySelector('.comment_suggest').style.display = val === 'UNIFICATION' ? '' : 'none';
	}
}

customElements.define('ws-comment-editor', WsCommentEditor);