<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AuthPanel from '../../components/AuthPanel.vue';
import { url } from '../../url';

const props = defineProps({
    email: { type: String, required: true },
    resendIn: { type: Number, default: 0 },
    expiresInMinutes: { type: Number, required: true },
});

const form = useForm({ code: '' });
const secondsLeft = ref(props.resendIn);
let timer;

watch(() => props.resendIn, (value) => {
    secondsLeft.value = value;
});

onMounted(() => {
    timer = setInterval(() => {
        secondsLeft.value = Math.max(secondsLeft.value - 1, 0);
    }, 1000);
});

onBeforeUnmount(() => clearInterval(timer));

const submit = () => form.post(url('/verify-email'), { onError: () => form.reset() });

const resend = () => router.post(url('/verify-email/resend'), {}, {
    preserveScroll: true,
    onSuccess: () => form.clearErrors(),
});
</script>

<template>
    <AuthPanel title="Verify your email">
        <p class="auth-hint">
            We sent a 6 digit code to <strong>{{ email }}</strong>. It expires in {{ expiresInMinutes }} minutes.
            If you don't see it, check your spam folder.
        </p>
        <form @submit.prevent="submit">
            <label for="code">Verification code</label>
            <input
                id="code"
                v-model="form.code"
                class="code-input"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                required
                autofocus
            />
            <p v-if="form.errors.code" class="field-error">{{ form.errors.code }}</p>

            <button type="submit" class="auth-submit" :disabled="form.processing">Verify</button>
        </form>

        <p class="auth-switch">
            <button type="button" class="link-button" :disabled="secondsLeft > 0" @click="resend">
                {{ secondsLeft > 0 ? `Resend code in ${secondsLeft}s` : 'Resend code' }}
            </button>
        </p>
        <p class="auth-switch verify-links">
            <Link :href="$url('/student-corner/profile')">Wrong email? Change it</Link>
            <Link :href="$url('/logout')" method="post" as="button" class="link-button">Log out</Link>
        </p>
    </AuthPanel>
</template>
