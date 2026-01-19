// import { debugMode, debugLog, builtin, resolveElements, generateUID, log, warn, error  } from './np.utils.js';
import * as u from './np.utils.js';
import { actionHandlers } from './np.interactions.js';

import { initDropdowns } from './np.dropdown.js';
import { initSidebar } from './np-sidebar.js';
import { initAutoDisableButtons } from './np.autoDisableButtons.js';

// Mappa dichiarativa: nomi logici → nomi funzione
const builtinDeclaration = {
	dropdown: 'initDropdowns',
	sidebar: 'initSidebar',
	autoDisableButtons: 'initAutoDisableButtons'
};

// Mappa finale con riferimenti reali
const builtin = {};

// Loop automatico per assegnare i riferimenti
for (const [name, fnName] of Object.entries(builtinDeclaration)) {
	const fn = { initDropdowns, initSidebar, initAutoDisableButtons }[fnName];

	if (typeof fn === 'function') {
		builtin[name] = fn;
	} else {
		u.warn(`Function "${fnName}" not found for component "${name}"`);
	}
}

window.np = (function () {

	// Registro globale dei componenti
	const components = {};

	/*
	* Dispatcher centrale che riceve le azioni dichiarate negli attributi n-click/n-submit ecc.
	* Esegue un'azione registrata (es. click, submit) su un elemento DOM.
	*
	* @param {string} action        - Nome dell'azione registrata (es. 'toggleMenu')
	* @param {HTMLElement} el       - Elemento trigger (es. quello con n-click)
	* @param {string|null} targetSel - Selettore CSS per il target (può essere null)
	* @param {Event} event          - Evento DOM originale
	*/
	function dispatchAction(action, el, targetSel, event) {
		// Cerca nel DOM il target, se è stato specificato
		const target = targetSel ? document.querySelector(targetSel) : null;
		if (targetSel && !target) {
			console.warn(`dispatchAction: target "${targetSel}" not found`);
		}

		// Recupera la funzione registrata per questa azione
		const fn = np.actionHandlers?.[action];

		// Se è una funzione valida, la esegue con i parametri previsti
		if (typeof fn === 'function') {
			fn(el, target, event); // triggerElement, targetElement, eventoDOM
			if (u.debugMode) {
				console.groupCollapsed(`%c[NP] %cAction: ${action}`, 'color: #0a58ca; font-weight: bold;', 'color: #20c997;');
				console.log('START FROM:', el.outerHTML);
				console.log('TARGET:', target.outerHTML);
				console.log({ el, event, target });
				console.groupEnd();
			}
		}
	}
	// ==========================================================

	// === MutationObserver dinamic ===
	function startObserver() {

		const observer = new MutationObserver(mutations => {

			for (const m of mutations) {

				// 1. GESTIONE NODI AGGIUNTI
				if (m.type === 'childList') {
					for (const node of m.addedNodes) {
						if (node.nodeType !== 1) continue; // solo elementi HTML

						// Init su nodo singolo
						if (node.matches('.np-dropdown')) initDropdowns(node);
						if (node.matches('.section-label')) initSidebar(node);
						if (node.matches('.np-auto-disable')) initAutoDisableButtons(node);

						// Init su eventuali figli
						node.querySelectorAll?.('.np-dropdown').forEach(el => initDropdowns(el));
						node.querySelectorAll?.('.section-label').forEach(el => initSidebar(el));
						node.querySelectorAll?.('.np-auto-disable').forEach(el => initAutoDisableButtons(el));
					}
				}

				// 2. GESTIONE CAMBI ATTRIBUTI
				if (m.type === 'attributes') {
					const target = m.target;
					const attr = m.attributeName;

					// Esempio futuro (disabilitazione condizionata, aria-expanded, ecc)
					// if (attr === 'data-status') {
					//     console.log(`Attributo "${attr}" modificato su`, target);
					// }
				}

				// 3. GESTIONE CAMBIAMENTI DI TESTO
				if (m.type === 'characterData') {
					const newText = m.target.data;

					// Esempio futuro (es. aggiornamento contatore, feedback dinamici)
					// console.log('Contenuto testuale aggiornato:', newText);
				}
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true,
			attributes: true,
			characterData: true
		});
	}

	/*
	* Registra un componente nel sistema globale NP.
	*
	* @param {string} name - Nome identificativo del componente (es. 'dropdown', 'sidebar').
	* @param {Function} fn - Funzione di inizializzazione del componente (initXXX).
	* @param {HTMLElement[]} [elements=[]] - Array di elementi DOM già inizializzati da associare.
	*
	* Ogni elemento deve avere un attributo `data-np-uid` valido.
	* I dati raccolti per ogni istanza includono: riferimento all'elemento, ID, UID, attributi data-*, stato `ready`, e timestamp di registrazione.
	*
	* Se `fn` non è una funzione valida, l'operazione viene ignorata con errore in console.
	*/
	function registerComponent(name, fn, elements = []) {

		if (typeof fn !== 'function') {
			console.error(`Component "${name}" is not a valid function`);
			return;
		}

		const timestamp = Date.now(); // Timestamp di registrazione del componente
		const map = {}; // Mappa delle istanze per questo componente, indicizzate per UID

		// Cicla su tutti gli elementi da associare al componente
		elements.forEach(el => {

			// Scarta se non è un nodo valido
			if (!el || typeof el.getAttribute !== 'function') return;

			// Recupera UID, richiesto per mappare l'istanza
			let uid = el.getAttribute('data-np-uid');
			if (!uid) return;

			// Costruisce un oggetto con tutti gli attributi data-* dell'elemento
			const data = {};
			for (let attr of el.attributes) {
				if (attr.name.startsWith('data-')) {
					data[attr.name] = attr.value;
				}
			}

			// Registra l'istanza nella mappa
			map[uid] = {
				element: el,
				ready: true,
				id: el.getAttribute('data-id') || el.getAttribute('id') || null,
				uid,
				data,
				registeredAt: timestamp
			};
		});

		// Registra il componente nel registro globale
		components[name] = {
			fn,
			ready: true,
			registeredAt: timestamp,
			instances: map
		};

	}

	/*
	* Recupera un componente registrato o una sua istanza specifica.
	*
	* @param {string} name - Nome del componente (es. 'dropdown').
	* @param {string|null} [uid=null] - UID dell'istanza (es. 'np-abc123'). Se omesso, restituisce il componente completo.
	* @returns {object|null} - Oggetto istanza se UID valido, componente completo se UID omesso, altrimenti null.
	*
	* Mostra un warning se il componente o l'UID richiesto non esiste.
	*/
	function component(name, uid = null) {

		name = String(name).trim().toLowerCase(); // Normalizza il nome del componente

		// Recupera il componente dal registro
		const comp = components[name];

		// if (!comp || typeof comp !== 'object' || typeof comp.instances !== 'object') {
		// 	console.warn(`Component "${name}" unregistered.`);
		// 	return null;
		// }

		// Se non viene richiesto un UID, ritorna l'intero componente
		if (!uid) return comp;

		// Verifica che l'istanza esista
		const cleanUID = String(uid).trim(); // Normalizza l'UID
		if (!Object.prototype.hasOwnProperty.call(comp.instances, cleanUID)) {
			console.warn(`UID "${cleanUID}" not found in the component "${name}".`);
			console.warn('UID available:', Object.keys(comp.instances));
			return null;
		}

		u.log(`Component "${name}" → Instance "${cleanUID}" found`, comp.instances[cleanUID]); // Log di successo (solo in caso positivo)
		return comp.instances[cleanUID]; // Ritorna l'istanza richiesta
	}

	/*
	* Inizializza un componente registrato tramite il suo nome.
	*
	* @param {string} name - Nome del componente (es. 'dropdown', 'sidebar').
	* @param {HTMLElement|Document} [scope=document] - Elemento DOM su cui inizializzare il componente (opzionale).
	* @returns {array|false} - Array di istanze inizializzate, oppure false in caso di errore.
	*
	* La funzione invoca l'inizializzatore registrato per quel componente. Se la funzione restituisce un array di elementi,
	* questi vengono salvati in `components[name].instances` e il componente viene segnato come `ready`.
	*/
	function initComponent(name, scope) {

		// Recupera il componente dal registro globale
		const entry = components[name];

		// Se non esiste o la funzione non è valida, interrompi
		if (!entry || typeof entry.fn !== 'function') {
			u.warn(`Component "${name}" is not registered`);
			return false;
		}

		try {

			// Esegue la funzione di inizializzazione, con scope se fornito
			const result = entry.fn(scope);

			// Se restituisce un array di elementi inizializzati, salvalo
			if (Array.isArray(result)) {
				entry.instances = result;
			}

			entry.ready = true; // Segna il componente come pronto
			u.log(`Component "${name}" initialized successfully`, result); // Log di successo
			return result;

		} catch (e) {
			// In caso di errore, segna come non pronto e logga l'error
			u.error(`Component "${name}" failed to initialize:`, e);
			entry.ready = false;
			return false;
		}
	}

	/*
	* Restituisce l'elenco dei componenti registrati nel sistema np
	*
	* @function listComponents
	* @memberof np
	* @returns {string[]} Array contenente i nomi di tutti i componenti attualmente registrati
	*
	* @example
	* const all = np.listComponents();
	* console.log(all); // ['dropdown', 'sidebar', ...]
	*/
	function listComponents() {
		// Estrae le chiavi dall'oggetto interno "components" usato come registro globale
		return Object.keys(components);
	}

	/*
	* Restituisce il registro globale di tutti i componenti np
	*
	* @function getComponentRegistry
	* @memberof np
	* @returns {Object} Oggetto contenente tutti i componenti registrati con le relative informazioni
	*
	* Ogni voce rappresenta un componente identificato per nome e contiene:
	* - la funzione associata (fn)
	* - il timestamp di registrazione
	* - lo stato di inizializzazione (ready)
	* - le istanze rilevate, indicizzate per UID
	*
	* Utile per debug, strumenti di sviluppo o monitoraggio runtime
	*/
	function getComponentRegistry() {
		// Ritorna l'oggetto interno "components" che contiene tutti i metadati e istanze registrate
		return components;
	}

	/*
	* Verifica lo stato di inizializzazione di un componente
	*
	* @function isComponentReady
	* @memberof np
	* @param {string} name - Nome del componente da verificare
	* @returns {string} Stato del componente: 'missing', 'invalid-fn', 'not-ready', 'no-instance', 'ready'
	*
	* Utile per debug o ispezioni runtime dei componenti
	*/
	function isComponentReady(name) {
		const entry = components[name];
		if (!entry) return 'missing';
		if (typeof entry.fn !== 'function') return 'invalid-fn';
		if (!entry.ready) return 'not-ready';

		const instances = entry.instances;
		if (instances && typeof instances === 'object') {
			for (const uid in instances) {
				const inst = instances[uid];
				if (inst && inst.element && document.body.contains(inst.element)) {
					return 'ready';
				}
			}
		}

		return 'no-instance';
	}

	/*
	* Stampa in console una tabella riepilogativa dei componenti registrati
	*
	* @function debugComponents
	* @memberof np
	* @returns {void}
	*
	* Richiede che `debugMode` sia attivo.
	*/
	function debugComponents() {
		if (!u.debugMode) return;

		// Titolo decorativo con colore
		console.log('%c[NP] Component Buil-In Registry Debug', 'color: #0a58ca; font-weight: bold; font-size: 14px;');

		const summary = [];

		for (const [name, entry] of Object.entries(components)) {
			summary.push({
				Component: name,
				Instances: Object.keys(entry.instances || {}).length,
				Function: typeof entry.fn === 'function' ? entry.fn.name || 'anonymous' : 'invalid',
				'Registered At': new Date(entry.registeredAt).toLocaleString()
			});
		}

		console.table(summary);
	}

	return {

		dispatchAction,
		actionHandlers,
		component,
		listComponents,
		registerComponent,
		initComponent,
		isComponentReady,
		getComponentRegistry,
		debugComponents,

		generateUID: u.generateUID,
		resolveElements: u.resolveElements,
		log: u.log,
		warn: u.warn,
		error: u.error,

		setDebug: (v) => u.debugMode = !!v,
		init: function () {

			// Listener globale per tutti i click su elementi che usano n-click
			document.addEventListener('click', (e) => {

				if (!e.isTrusted) return; // Ignora eventi generati via JS (es. el.click()), accetta solo click reali dell’utente

				// Risale dalla sorgente dell'evento fino al primo elemento con attributo n-click
				const el = e.target.closest('[n-click]');
				if (!el) return; // Se non c'è, esci

				e.preventDefault(); // Evita che il browser esegua l'azione di default (es. apertura link <a>)
				e.stopPropagation();// Ferma la propagazione per evitare che altri listener vedano lo stesso evento

				// Legge il valore dell’attributo n-click, es: "click:toggleMenu"
				const def = el.getAttribute('n-click');
				if (!def) return;

				const [type, action] = def.split(':'); // Divide il valore in due parti: tipo evento (click) e nome azione (toggleMenu)
				if (type !== 'click') return; // Se il tipo di evento non è "click", esci (serve per filtrare solo i click)

				const target = el.getAttribute('n-target'); // Legge il selettore target dall’attributo n-target (opzionale)

				// Esegue l'azione registrata passando: nome azione, elemento che ha scatenato il click, selettore target (es: #menu1), oggetto evento
				np.dispatchAction(action, el, target, e);

			});

			for (const [name, fn] of Object.entries(builtin)) {
				try {
					const instances = fn(); // la funzione deve restituire gli elementi inizializzati
					registerComponent(name, fn, instances);
					u.log(`Component "${name}" initialized and registered`);
				} catch (e) {
					u.error(`Error initializing for "${name}":`, e);
				}
			}

			if (u.debugMode) {
				const totalComponents = Object.keys(components).length;
				let totalInstances = 0;

				for (const entry of Object.values(components)) {
					totalInstances += Object.keys(entry.instances || {}).length;
				}

				console.log('%cDOM READY', 'background: green; color: white');
				console.groupCollapsed('%c[NP INIT] Framework initialized', 'color: white; background:#0a58ca; padding:3px 8px; border-radius:4px;');
				console.log('%cVersion:', 'color: gray;', np.version || 'dev');
				console.log('%cComponents Built-In:', 'color: gray;', totalComponents);
				console.log('%cBuilt-In Instances in DOM:', 'color: gray;', totalInstances);
				console.log('%cObserver:', 'color: gray;', 'enabled');
				console.log('%cTime:', 'color: gray;', new Date().toLocaleTimeString());
				console.groupEnd();
			}

			startObserver(); // auto-init per elementi dinamici
			document.dispatchEvent(new Event('np:ready'));

		},
		initSidebar,
		initDropdowns,
		initAutoDisableButtons

	};

})();

// Auto-inizializzazione se DOM già pronto
if (document.readyState === 'complete' || document.readyState === 'interactive') {
	np.init();
} else {
	document.addEventListener('DOMContentLoaded', np.init);
}