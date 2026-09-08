/**
 * Grouped money input.
 *
 * HTML number inputs cannot render thousands separators, so money fields are
 * plain text inputs wearing this mask: the user sees `452.500.000`, while the
 * Livewire property keeps the raw numeric string the `numeric` rules expect.
 *
 * maryUI ships a `money` prop, but it depends on a `Currency` class from its
 * own asset bundle, which this project does not import — hence our own.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('moneyInput', (config = {}) => ({
        // 'IDR' groups with dots and takes no cents; 'USD' groups with commas.
        currency: config.currency || 'IDR',
        model: config.model,
        display: '',

        init() {
            this.display = this.format(this.$wire.get(this.model));

            // Follow the property when something else changes it: the currency
            // switch, "use as final price", or a server-side reset.
            this.$wire.$watch(this.model, (value) => {
                if (this.unformat(this.display) !== this.normalize(value)) {
                    this.display = this.format(value);
                }
            });
        },

        get decimals() {
            return this.currency === 'USD' ? 2 : 0;
        },

        get separators() {
            return this.currency === 'USD'
                ? { group: ',', decimal: '.' }
                : { group: '.', decimal: ',' };
        },

        normalize(value) {
            return value === null || value === undefined ? '' : String(value);
        },

        /** Strip grouping and normalise the decimal mark back to a dot. */
        unformat(value) {
            const text = this.normalize(value);

            if (text === '') {
                return '';
            }

            const { group, decimal } = this.separators;

            return text
                .split(group).join('')
                .replace(decimal, '.')
                .replace(/[^0-9.]/g, '');
        },

        format(value) {
            const raw = this.unformat(value);

            if (raw === '' || raw === '.') {
                return '';
            }

            const [whole, fraction] = raw.split('.');
            const { group, decimal } = this.separators;
            const grouped = (whole || '0').replace(/\B(?=(\d{3})+(?!\d))/g, group);

            // Only show a decimal part the user actually typed — never a
            // trailing ",00" they did not ask for.
            if (this.decimals === 0 || fraction === undefined) {
                return grouped;
            }

            return grouped + decimal + fraction.slice(0, this.decimals);
        },

        onInput(event) {
            const raw = this.unformat(event.target.value);

            this.$wire.set(this.model, raw, false);
            this.display = this.format(raw);
        },

        /** Commit to the server once the field is done being edited. */
        onBlur() {
            const raw = this.unformat(this.display);

            this.display = this.format(raw);
            this.$wire.set(this.model, raw);
        },
    }));
});

/**
 * Keeps the New Deal wizard alive across an accidental refresh.
 *
 * Livewire holds form state on the server and drops it when the page reloads,
 * so we mirror the fields into localStorage after every successful round trip
 * and hand them back on the next mount. Only new deals are stored — an edit is
 * already persisted, and replaying a stale draft over it would be wrong.
 *
 * Uploaded files cannot be serialised, so assets are deliberately not kept.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('dealDraft', (config = {}) => ({
        storageKey: config.key || 'apcn.deal-draft',
        enabled: config.enabled !== false,
        // Fields worth restoring. Anything else (search text, modal flags,
        // upload handles) is transient.
        fields: [
            'doctorId', 'companyName', 'brandName', 'picName', 'picContact',
            'packageId', 'currency', 'inclusion', 'finalPrice',
            'paymentTerms', 'currentStep',
        ],

        init() {
            if (!this.enabled) {
                return;
            }

            this.restore();

            // Persist after each committed Livewire update, so what we store is
            // always what the server just accepted.
            window.Livewire.hook('commit', ({ component, succeed }) => {
                if (component.id !== this.$wire.id) {
                    return;
                }

                succeed(() => queueMicrotask(() => this.persist()));
            });

            window.addEventListener('deal-draft-cleared', () => this.clear());
        },

        read() {
            try {
                const raw = window.localStorage.getItem(this.storageKey);

                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                // Private mode, quota, or corrupted JSON — a draft is a
                // convenience, never a reason to break the form.
                return null;
            }
        },

        restore() {
            const draft = this.read();

            if (!draft || typeof draft !== 'object') {
                return;
            }

            this.$wire.call('restoreDraft', draft);
        },

        persist() {
            const draft = {};

            this.fields.forEach((field) => {
                draft[field] = this.$wire.get(field);
            });

            // Only the parts of each item worth replaying.
            draft.items = (this.$wire.get('items') || [])
                .filter((row) => row.checked || row.custom_price !== '')
                .map((row) => ({
                    item_id: row.item_id,
                    checked: row.checked,
                    quantity: row.quantity,
                    custom_price: row.custom_price,
                }));

            draft.savedAt = Date.now();

            try {
                window.localStorage.setItem(this.storageKey, JSON.stringify(draft));
            } catch (e) {
                // Out of quota or storage blocked: nothing to do but carry on.
            }
        },

        clear() {
            try {
                window.localStorage.removeItem(this.storageKey);
            } catch (e) {
                // Ignore — see read().
            }
        },
    }));
});
