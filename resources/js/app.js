import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;
Alpine.plugin(focus);

Alpine.data('searchBox', () => ({
    query: '',
    open: false,
    loading: false,
    results: { products: [], categories: [], brands: [] },
    active: -1,
    async run(value) {
        this.query = value;
        this.active = -1;

        if (value.trim().length < 2) {
            this.open = false;
            this.results = { products: [], categories: [], brands: [] };
            return;
        }

        this.loading = true;

        try {
            const response = await fetch('/search/autocomplete?q=' + encodeURIComponent(value), {
                headers: { Accept: 'application/json' },
            });

            if (response.ok) {
                this.results = await response.json();
                this.open = true;
            }
        } catch (error) {
            this.open = false;
        } finally {
            this.loading = false;
        }
    },
    close() {
        this.open = false;
        this.active = -1;
    },
    move(direction) {
        const count = this.results.products.length;
        if (!this.open || count === 0) return;
        this.active = (this.active + direction + count) % count;
    },
    submit() {
        if (this.query.trim() !== '') {
            window.location.href = '/search?q=' + encodeURIComponent(this.query.trim());
        }
    },
    hasResults() {
        return this.results.products.length + this.results.categories.length + this.results.brands.length > 0;
    },
}));

Alpine.data('mobileMenu', () => ({
    open: false,
    categoriesOpen: false,
    brandsOpen: false,
    toggle() {
        this.open = !this.open;
        document.documentElement.classList.toggle('overflow-hidden', this.open);
    },
    close() {
        this.open = false;
        document.documentElement.classList.remove('overflow-hidden');
    },
}));

Alpine.data('gallery', (zoomEnabled = true) => ({
    active: 0,
    zoom: false,
    zoomEnabled,
    images: [],
    init() {
        this.images = Array.from(this.$el.querySelectorAll('[data-gallery-image]')).map((node) => ({
            src: node.getAttribute('data-src'),
            alt: node.getAttribute('data-alt') || '',
        }));
    },
    select(index) {
        this.active = index;
        this.zoom = false;
    },
    toggleZoom() {
        if (this.zoomEnabled) this.zoom = !this.zoom;
    },
}));

Alpine.data('checkoutForm', () => ({
    county: '',
    deliveryType: 'delivery',
    methods: [],
    loading: false,
    ready: false,
    selected: '',
    async init() {
        this.county = this.$refs.county?.value || '';
        this.deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'delivery';
        this.selected = document.querySelector('input[name="shipping_method_id"]:checked')?.value || '';
        if (this.county) await this.loadMethods();
    },
    async loadMethods() {
        if (!this.county) return;
        this.loading = true;
        try {
            const url = `/checkout/shipping?county=${encodeURIComponent(this.county)}&delivery_type=${encodeURIComponent(this.deliveryType)}`;
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (response.ok) {
                const data = await response.json();
                this.methods = data.methods;
                if (!this.methods.some((method) => String(method.id) === String(this.selected))) {
                    this.selected = this.methods.length ? String(this.methods[0].id) : '';
                }
            }
        } catch (error) {
            this.methods = [];
        } finally {
            this.loading = false;
            this.ready = true;
        }
    },
    setDelivery(type) {
        this.deliveryType = type;
        this.loadMethods();
    },
    formatPrice(minor) {
        return 'KES ' + new Intl.NumberFormat('en-KE').format(Math.round(minor / 100));
    },
}));

Alpine.data('consentBanner', () => ({
    visible: false,
    init() {
        if (!this.$el.dataset.required) return;
        this.visible = localStorage.getItem('tajira_analytics_consent') === null;
    },
    accept() {
        localStorage.setItem('tajira_analytics_consent', 'granted');
        this.visible = false;
        window.dispatchEvent(new CustomEvent('tajira-consent-granted'));
    },
    decline() {
        localStorage.setItem('tajira_analytics_consent', 'denied');
        this.visible = false;
    },
}));

document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-analytics-item]');

    if (!target) return;

    try {
        const item = JSON.parse(target.getAttribute('data-analytics-item'));
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'select_item',
            ecommerce: {
                item_list_name: target.getAttribute('data-analytics-list') || 'catalogue',
                items: [item],
            },
        });
    } catch (error) {
        // Analytics must never block navigation.
    }
});

Alpine.start();
