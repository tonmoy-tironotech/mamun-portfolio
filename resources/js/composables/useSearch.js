import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { url } from '../url';

// Keeps a search box in sync with the ?search= query, shortly after typing stops.
// `params` adds other query values to keep, such as the selected tab.
export function useSearch(initial, path, params = () => ({})) {
    const term = ref(initial);
    let timer;

    watch(term, (value) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            router.get(url(path), { ...params(), ...(value.trim() ? { search: value.trim() } : {}) }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);
    });

    return term;
}
