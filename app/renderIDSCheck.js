Vue.component('ids-result', {
	template: '#ids-result',
	props: [
		'result',
		'isAdmin', 
		'strokeCount',
		'firstStroke',
		'totalCount',
		'radicalFound',
		'strokeCountData',
		'firstStrokeData',
		'totalCountData'
	],
	data() {
		return {};
	}
});
Vue.component('ids-result-row', {
	template: '#ids-result-row',
	props: ['part', 'depth', 'isAdmin'],
	data() {
		return {};
	},
	computed: {
		prefix() {
			return '\xa0'.repeat(this.depth * 2);
		},
		indent() {
			return { paddingLeft: `${this.depth * 50}px` };
		}
	},
});

document.querySelectorAll('.ids-parse-result').forEach(script => {
	const data = JSON.parse(script.textContent);

	const injectionPoint = document.createElement('div');
	script.insertAdjacentElement("beforebegin", injectionPoint);
	injectionPoint.innerHTML = `
		<ids-result :result="result"
		:is-admin="is_admin"
		:stroke-count="stroke_count"
		:first-stroke="first_stroke"
		:total-count="total_count"
		:radical-found="radical_found"
		:stroke-count-data="stroke_count_data"
		:first-stroke-data="first_stroke_data"
		:total-count-data="total_count_data" />
	`;

	new Vue({
		el: injectionPoint,
		data: data
	});
});
