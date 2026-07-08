/**
 * Global toast notification queue.
 * Call `$store.toast.push(type, message)` to enqueue a notification (auto-dismissed after 5 s).
 *
 * @type {{ items: Array<{id: number, type: string, message: string}>, push: Function, remove: Function }}
 */
export const toastStore = {
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
};
