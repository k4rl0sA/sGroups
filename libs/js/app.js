/******************CREATE RULES VALIDATION************************/
function EnabDepeDynamic(classes, conditions) {
	const allConditionsMet = conditions.every(condition => {
		const element = document.getElementById(condition.id);
		if (!element) {
			console.error(`Elemento con ID ${condition.id} no encontrado.`);
			return false;
		}
		return condition.compare ? element.value === condition.value : element.value !== condition.value;
	});
	const selector = classes.map(cls => `select.${cls}, input.${cls}, textarea.${cls}`).join(', ');
	const elements = document.querySelectorAll(selector);
	elements.forEach(element => {
		enaFie(element, !allConditionsMet);
	});
}
/******************CREATE RULES VALIDATION************************/
/******************VALIDATION ENABLED OR DISABLED REQ COM************************/
function ActiRequCome(){
	const conditions = [
		{ id: 'act', value: '7', compare: true }
	];
	EnabDepeDynamic(['aCt'], conditions);
}
/******************VALIDATION ENABLED OR DISABLED REQ COM************************/