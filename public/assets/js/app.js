document.addEventListener('alpine:init', () => {
    Alpine.store('confirm', {
        open: false,
        title: 'Confirmar accion',
        message: '',
        onAccept: null,
        show(message, onAccept, title = 'Confirmar accion') {
            this.open = true;
            this.message = message;
            this.title = title;
            this.onAccept = onAccept;
        },
        close() {
            this.open = false;
            this.message = '';
            this.onAccept = null;
        },
        accept() {
            if (typeof this.onAccept === 'function') {
                this.onAccept();
            }
            this.close();
        }
    });

    Alpine.store('toast', {
        items: [],
        push(type, message) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type, message });
            setTimeout(() => {
                this.remove(id);
            }, 5000);
        },
        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
        }
    });

    Alpine.data('appShell', () => ({
        sidebarOpen: window.innerWidth >= 768
    }));
});
