class WsEvidences extends HTMLElement {
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
		this.render(JSON.parse(this.dataset.evidence));
	}
	
	render(sources) {
		Object.keys(sources).forEach((sourceReference) => {
			const evidence = sources[sourceReference];
			const region = evidence.region;
			const charData = evidence.data;

			const filenameKeys = [
				"G file name of evidence image",
				"K file name of evidence image",
				"UK PNG Image",
				'File name of evidence image',
				"T File name of evidence image",
				"V Evidence File"
			];
			
			const evidenceKeys = [
				"G Ref. to Evidence doc",
				"G Optional Information",
				"K Ref. to Evidence document",
				"i1) \nPage No. in the evidence document (optional)",
				"i2) \nRow No. \nin the page \n(optional)",
				"i3) \nPosition No.\n in the row \n(optional)",
				"UK Title and Year",
				"UK Page No.",
				"SAT References to evidence documents",
				"SAT optional information",
				"T References to evidence documents A",
				"T References to evidence documents B",
				"UTC References to evidence documents",
				"UTC SJ\/T 11239-2001",
				"V Evidence",
				"V Reading",
			];
			
			const kInfoKey = 'K Revised information (2018.03.16)';

			let file = filenameKeys.map(key => charData[key]).find(file => !!file);
			if (file == null) {
				return;
			}
			
			const evidence_name = Object.keys(charData)
				.filter(key => evidenceKeys.includes(key))
				.map(key => charData[key]).join(" ");

			const div = document.createElement('div');
			div.innerText = evidence_name;
			div.style.backgroundColor = '#eee';
			div.style.padding = '10px';
			div.style.overflow = 'hidden';
			if (region === 'T') {
				let a = document.createElement('a');
				const tSourceID = sourceReference.substring(1, sourceReference.length - 5) + sourceReference.substring(sourceReference.length - 4);
				console.log(tSourceID)
				a.href = "https://www.cns11643.gov.tw/wordView.jsp?ID=" + parseInt(tSourceID, 16);
				//a.href = "https://www.cns11643.gov.tw/AIDB/query_general_view.do?page=" + sourceReference.substring(1, sourceReference.length - 5) + "&code=" + sourceReference.substring(sourceReference.length - 4);
				a.target = "_blank";
				a.textContent = "Info on CNS11643.gov.tw";
				a.style.float = 'right';
				a.style.color = 'blue';
				div.appendChild(a);
			}
			if (region === 'K') {
				let code = sourceReference.substring(3);
				let a = document.createElement('a');
				a.href = "http://www.koreanhistory.or.kr/newchar/grid_list.jsp?code_type=3&codebase=KC" + code;
				a.target = "_blank";
				a.textContent = "Info on koreanhistory.or.kr";
				a.style.float = 'right';
				a.style.color = 'blue';
				div.appendChild(a);
			}
			this.appendChild(div);

			if (kInfoKey in charData) {
				let additional_info = charData[kInfoKey];
				if (additional_info !== null && additional_info !== undefined) {
					let div = document.createElement('div');
					if (additional_info === 'Deleted') {
						div.style.color = 'red';
						div.style.fontWeight = 'bold';
						additional_info = 'Withdrawn in IRGN2229R (WS2017 ROK Revised Submission).';
						file = '';
					}
					div.innerText = additional_info;
					div.style.whiteSpace = 'pre-wrap';
					div.style.backgroundColor = '#ff0';
					div.style.padding = '10px';
					this.appendChild(div);
				}
			}
			let separator = region === 'T' ? "\n" : ';'
			file.split(separator).forEach((file) => {
				file = file.trim();
				if (file === '') {
					return;
				}
				
				if (file.startsWith('TCA_CJK_2015')) {
					let page_number = file.split(' ')[2];
					page_number = page_number.padStart(3, '0');
					file = 'https://raw.githubusercontent.com/hfhchan/irg-ws2015/5d22fba4/data/t-evidence/IRGN2128A4Evidences-' + page_number + '.png';
				}
				if (file.startsWith('1292')) {
					let page_number = file.split(' ')[2];
					file = 'https://raw.githubusercontent.com/hfhchan/irg-ws2015/5d22fba4/data/g-evidence/IRGN2115_Appendix7_1268_Zhuang_Evidences_page1268_image' + page_number + '.jpg';
				}

				if (!file.startsWith('https://')) {
					file = "https://hc.jsecs.org/irg/ws2017/data/" + region.toLowerCase() + '-evidence/' + file;
				}
				if (file.startsWith('..')) {
					file = window.location.origin + window.location.pathname.replace('index.php', '') + file;
				}
				if (file.endsWith('.pdf')) {
					let iframe = document.createElement('iframe');
					//iframe.src = 'https://mozilla.github.io/pdf.js/web/viewer.html?file=' + encodeURIComponent(file);
					iframe.src = 'https://hc.jsecs.org/pdf/web/viewer.html?file=' + encodeURIComponent(file);
					iframe.width = this.clientWidth;
					iframe.height = 1200 * this.clientWidth / 800;
					iframe.className = 'full';
					this.appendChild(iframe);
					// Adding an iframe may cause scrollbars to appear. Try again using new width
					iframe.width = this.clientWidth;
				} else {
					let a = document.createElement('a');
					a.className = 'evidence_image';
					a.href = file;
					a.target = '_blank';
					let img = document.createElement('img');
					img.src = file;
					if (sourceReference.startsWith('GHC-') || sourceReference.startsWith('GKJ') || sourceReference.startsWith('UK-') || sourceReference.startsWith('USAT') || sourceReference.startsWith('KC') || sourceReference.startsWith('V-') || true) {
						img.className = 'full';
					}
					a.appendChild(img);
					this.appendChild(a);
				}
			});
		});
	}
}

customElements.define('ws-evidences', WsEvidences);