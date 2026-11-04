<script setup>
import { usePage } from '@inertiajs/vue3';
import SiteLayout from '../layouts/SiteLayout.vue';

defineProps({ title: { type: String, required: true } });

const page = usePage();
</script>

<template>
    <SiteLayout wide>
        <div class="auth-panel">
            <img class="auth-image" :src="$url('/images/auth_side.jpg')" alt="" />
            <div class="auth-form">
                <h2>{{ title }}</h2>
                <p v-if="page.props.flash.status" class="form-success" role="status">{{ page.props.flash.status }}</p>
                <slot />
            </div>
        </div>
    </SiteLayout>
</template>
