<script setup>
import SiteLayout from '../layouts/SiteLayout.vue';

defineProps({ title: { type: String, required: true } });
</script>

<template>
    <SiteLayout wide>
        <div class="auth-panel">
            <img class="auth-image" :src="$url('/images/auth_side.jpg')" alt="" />
            <div class="auth-form">
                <h2>{{ title }}</h2>
                <slot />
            </div>
        </div>
    </SiteLayout>
</template>
