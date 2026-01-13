/**
* Gestore cambio lingua NexiPress (frontend).
* Espone un metodo `switch(lang)` che reindirizza
* l’utente alla rotta di cambio lingua.
*
* Uso:
*   npLang.switch('it');
*/
window.npLang = {
	switch(lang) {
		location.href = '/change-language/' + lang;
	}
};

// Reagisce all'evento click quando scegli una lingua
// di solito associato a: <a data-np-lang="it"...
document.addEventListener('click', e => {
	const el = e.target.closest('[data-np-lang]');
	if (el) {
		e.preventDefault();
		npLang.switch(el.getAttribute('data-np-lang'));
	}
});
// Reagisce all'evento change quando scegli una lingua
// di solito associato a: <select data-np-lang><option value="it">...
document.addEventListener('change', e => {
	if (e.target.matches('select[data-np-lang]')) {
		npLang.switch(e.target.value);
	}
});
