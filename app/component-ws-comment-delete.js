class WsCommentDelete extends HTMLElement {
	constructor() {
		super();
		this.initialized = false;
	}
	
	connectedCallback() {
		if (this.initialized) {
			return;
		}
		
		this.initialized = true;

		this.commentId = this.dataset.commentId;

		this.innerHTML = '<a href="#">Delete</a>';
		this.addEventListener('click', e => {
			e.preventDefault();
			const result = confirm(`Are you sure you want to delete this comment (#${this.commentId})?`);
			if (!result) {
				return;
			}
			
			const data = new URLSearchParams();
			data.append('action', 'delete_comment');
			data.append('user_id', this.dataset.userId);
			data.append('comment_id', this.commentId);
			this.querySelector('a').disabled = true;
			(async() => {
				try {
					const res = await fetch('api-internal.php', {
						method: 'post',
						credentials: 'same-origin',
						body: data,
					});
					const result = await res.json();
					if (result.success) {
						alert('Comment Deleted');
						window.location.reload();
					} else {
						alert('Comment could not be deleted: ' + result.message);
						this.querySelector('a').disabled = false;
					}
				} catch (e) {
					alert('Comment could not be deleted: ' + e.message);
					this.querySelector('a').disabled = false;
				}
			})();
		});
	}
}

customElements.define('ws-comment-delete', WsCommentDelete);